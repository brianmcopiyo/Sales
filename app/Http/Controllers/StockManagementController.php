<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BranchStock;
use App\Models\RestockOrder;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Branch;
use App\Models\User;
use App\Mail\StockTransferActivityMail;
use App\Mail\StockActivityMail;
use App\Notifications\StockTransferActivityNotification;
use App\Notifications\StockActivityNotification;
use App\Services\InventoryMovementService;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use App\Helpers\ImeiHelper;
use App\Exports\RestockOrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class StockManagementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Pending stock transfers (awaiting approval)
        $pendingTransfers = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'creator'])
            ->where('status', 'pending')
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('from_branch_id', $user->branch_id)
                        ->orWhere('to_branch_id', $user->branch_id);
                });
            })
            ->latest()
            ->get();

        // Delivered stocks (received transfers) – for stats / delivered today
        $deliveredStocks = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'receiver'])
            ->where('status', 'received')
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('to_branch_id', $user->branch_id);
            })
            ->latest('received_at')
            ->limit(20)
            ->get();

        // Transfer history: all transfers user is involved in (any status) for History section
        $transferHistory = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'receiver'])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('from_branch_id', $user->branch_id)
                        ->orWhere('to_branch_id', $user->branch_id);
                });
            })
            ->latest()
            ->limit(30)
            ->get();

        // In-store stocks (current branch stocks)
        $inStoreStocks = BranchStock::with(['branch', 'product'])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->get();

        // In-transit stocks (pending and in_transit transfers)
        $inTransitStocks = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'creator'])
            ->whereIn('status', ['pending', 'in_transit'])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('from_branch_id', $user->branch_id)
                        ->orWhere('to_branch_id', $user->branch_id);
                });
            })
            ->latest()
            ->get();

        // Sold stocks (recent sales with items)
        $soldStocks = SaleItem::with(['sale.branch', 'sale.customer', 'product'])
            ->whereHas('sale', function ($q) use ($user) {
                $q->where('status', 'completed')
                    ->when($user->branch_id, function ($query) use ($user) {
                        $query->where('branch_id', $user->branch_id);
                    });
            })
            ->latest('created_at')
            ->limit(20)
            ->get();

        // Stats
        $stats = [
            'pending_approvals' => $pendingTransfers->count(),
            'delivered_today' => $deliveredStocks->where('received_at', '>=', today())->count(),
            'in_store_total' => $inStoreStocks->sum('quantity'),
            'in_transit_total' => $inTransitStocks->sum('quantity'),
            'sold_today' => $soldStocks->where('created_at', '>=', today())->sum('quantity'),
        ];

        // Pending restock orders (awaiting receipt)
        $pendingRestockOrders = RestockOrder::with(['branch', 'product', 'creator'])
            ->whereIn('status', [RestockOrder::STATUS_PENDING, RestockOrder::STATUS_RECEIVED_PARTIAL])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->latest()
            ->get();

        // Restock history (received orders for reconciliation)
        $restockHistory = RestockOrder::with(['branch', 'product', 'creator'])
            ->whereIn('status', [RestockOrder::STATUS_RECEIVED_FULL, RestockOrder::STATUS_RECEIVED_PARTIAL])
            ->where('quantity_received', '>', 0)
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->latest('received_at')
            ->limit(20)
            ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock-management.index', compact(
            'pendingTransfers',
            'deliveredStocks',
            'transferHistory',
            'inStoreStocks',
            'inTransitStocks',
            'soldStocks',
            'pendingRestockOrders',
            'restockHistory',
            'stats',
            'branches',
            'products'
        ));
    }

    /**
     * Stock reconciliation: step-by-step movement history and discrepancy report.
     */
    public function reconciliation(Request $request)
    {
        $user = auth()->user();
        $branchParam = $request->query('branch');
        $productParam = $request->query('product');
        $dateFilter = $request->query('date', 'today'); // today | yesterday | all

        $filterDate = null;
        if ($dateFilter === 'today') {
            $filterDate = Carbon::today();
        } elseif ($dateFilter === 'yesterday') {
            $filterDate = Carbon::yesterday();
        }

        $branches = Branch::where('is_active', true)
            ->when($user->branch_id, fn($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $service = app(StockReconciliationService::class);
        $result = $service->run(
            $branchParam,
            $productParam,
            $filterDate,
            true,  // showOk
            true   // stepsOnly - include all steps when date filtered
        );

        $stepRows = $result['step_rows'];
        $perPage = (int) $request->get('per_page', 25);
        $perPage = max(5, min(100, $perPage));
        $page = (int) $request->get('page', 1);
        $total = count($stepRows);
        $stepRowsPaginated = new LengthAwarePaginator(
            array_slice($stepRows, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('stock-management.reconciliation', array_merge($result, [
            'step_rows' => $stepRowsPaginated,
            'branches' => $branches,
            'products' => $products,
            'branchParam' => $branchParam,
            'productParam' => $productParam,
            'dateFilter' => $dateFilter,
        ]));
    }

    /**
     * Apply reconciliation fix for the selected date (today or yesterday).
     * Corrects quantity_before/quantity_after on movements; sets BranchStock.quantity
     * from device count per branch/product (prioritising final devices in the system).
     */
    public function applyReconciliationFix(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|in:today,yesterday',
            'branch' => 'nullable|string',
            'product' => 'nullable|string',
        ]);

        $filterDate = $validated['date'] === 'today' ? Carbon::today() : Carbon::yesterday();
        $branchParam = isset($validated['branch']) ? $validated['branch'] : null;
        $productParam = isset($validated['product']) ? $validated['product'] : null;

        $service = app(StockReconciliationService::class);
        $result = $service->applyFix(
            $filterDate,
            $branchParam ?: null,
            $productParam ?: null,
            auth()->check() ? (string) auth()->id() : null
        );

        $query = ['date' => $validated['date']];
        if ($branchParam !== null && $branchParam !== '') {
            $query['branch'] = $branchParam;
        }
        if ($productParam !== null && $productParam !== '') {
            $query['product'] = $productParam;
        }

        $message = 'Fix applied: ' . $result['movements_updated'] . ' movement(s) corrected; '
            . $result['branch_stocks_updated'] . ' branch stock balance(s) set from device count for ' . $filterDate->toDateString() . '.';

        return redirect()->route('stock-management.reconciliation', $query)->with('success', $message);
    }

    /**
     * Restock wizard (progressive web app style) for creating new stock orders step by step.
     */
    public function restockWizard()
    {
        $user = auth()->user();
        if (
            !$user->isAdmin() && !$user->isHeadBranchManager()
            && !$user->hasPermission('stock-management.restock')
            && !$user->hasPermission('stock-management.initiate-restock')
        ) {
            abort(403, 'You do not have permission to create restock orders.');
        }
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('stock-management.restock-wizard', compact('branches', 'products'));
    }

    /**
     * List all restock orders (full history with optional status filter).
     */
    public function restockOrdersIndex(Request $request)
    {
        $user = auth()->user();
        $query = RestockOrder::with(['branch', 'product', 'creator', 'rejectedBy'])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->get('date_to'));
        }

        $orders = $query->latest('ordered_at')->paginate(15)->withQueryString();

        $baseQuery = RestockOrder::when($user->branch_id, function ($q) use ($user) {
            $q->where('branch_id', $user->branch_id);
        });
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', RestockOrder::STATUS_PENDING)->count(),
            'received_partial' => (clone $baseQuery)->where('status', RestockOrder::STATUS_RECEIVED_PARTIAL)->count(),
            'received_full' => (clone $baseQuery)->where('status', RestockOrder::STATUS_RECEIVED_FULL)->count(),
            'cancelled' => (clone $baseQuery)->where('status', RestockOrder::STATUS_CANCELLED)->count(),
        ];

        return view('stock-management.restock-orders', compact('orders', 'stats'));
    }

    /**
     * Show a single restock order (or full batch when order is part of a bulk order).
     */
    public function showOrder(RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if ($user->branch_id && $restockOrder->branch_id !== $user->branch_id) {
            abort(403, 'You do not have access to this order.');
        }

        $restockOrder->load(['product', 'branch', 'creator', 'rejectedBy']);

        $batchOrders = collect();
        if ($restockOrder->order_batch) {
            $batchOrders = RestockOrder::with(['product', 'branch', 'creator', 'rejectedBy'])
                ->where('order_batch', $restockOrder->order_batch)
                ->orderBy('order_number')
                ->get();
        }

        $childBranches = Branch::where('head_branch_id', $restockOrder->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock-management.order-show', compact('restockOrder', 'batchOrders', 'childBranches'));
    }

    /**
     * Create a stock transfer from this order (quantity). Recipient receives via normal stock transfer receive flow.
     */
    public function transferCatalogToBranch(Request $request, RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isHeadBranchManager()) {
            return back()->withErrors(['permission' => 'You do not have permission to transfer catalog.']);
        }
        if ($user->branch_id && $restockOrder->branch_id !== $user->branch_id) {
            abort(403, 'You do not have access to this order.');
        }

        $validated = $request->validate([
            'target_branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1|max:99999',
        ]);
        $targetBranchId = $validated['target_branch_id'];
        $quantity = (int) $validated['quantity'];

        $sourceBranchId = $restockOrder->branch_id;
        $childBranchIds = Branch::where('head_branch_id', $sourceBranchId)->pluck('id')->all();
        if (!in_array($targetBranchId, $childBranchIds, true)) {
            return back()->withErrors(['target_branch_id' => 'Target must be a child branch of the order branch.']);
        }
        if ($targetBranchId === $sourceBranchId) {
            return back()->withErrors(['target_branch_id' => 'Target branch must be different from the order branch.']);
        }

        $productId = $restockOrder->product_id;
        $fromStock = BranchStock::where('branch_id', $sourceBranchId)->where('product_id', $productId)->first();
        if (!$fromStock || $fromStock->quantity < $quantity) {
            return back()->withErrors(['catalog' => 'Insufficient branch stock for this quantity.']);
        }

        $transfer = null;
        DB::transaction(function () use ($restockOrder, $sourceBranchId, $targetBranchId, $productId, $quantity, $user, &$transfer) {
            $transfer = StockTransfer::create([
                'from_branch_id' => $sourceBranchId,
                'to_branch_id' => $targetBranchId,
                'product_id' => $productId,
                'restock_order_id' => $restockOrder->id,
                'quantity' => $quantity,
                'status' => 'pending',
                'created_by' => $user->id,
                'notes' => 'Catalog transfer from order ' . $restockOrder->order_number,
            ]);

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);

            InventoryMovementService::recordTransferOut($sourceBranchId, $productId, $quantity, $transfer->id, $user->id);
        });

        $users = $transfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($transfer, 'created', []));
            Notification::send($users, new StockTransferActivityNotification($transfer, 'created', []));
        }

        return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Stock transfer created. Recipient can receive it via the normal transfer flow.');
    }

    /**
     * Export restock orders to Excel (respects same filters as restock-orders index).
     */
    public function exportRestockOrders(Request $request)
    {
        $filename = 'restock-orders-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new RestockOrdersExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function approve(StockTransfer $stockTransfer)
    {
        $user = auth()->user();

        // Only users from the receiving branch can approve (mark as in transit). No admin override.
        if (!$user->branch_id || $stockTransfer->to_branch_id !== $user->branch_id) {
            abort(403, 'Only users from the receiving branch can approve this transfer.');
        }

        if ($stockTransfer->status !== 'pending') {
            return back()->withErrors(['status' => 'This transfer cannot be approved.']);
        }

        $stockTransfer->update([
            'status' => 'in_transit',
            'transferred_at' => now(),
        ]);

        $users = $stockTransfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($stockTransfer, 'in_transit', []));
            Notification::send($users, new StockTransferActivityNotification($stockTransfer, 'in_transit', []));
        }

        return redirect()->route('stock-management.index')->with('success', 'Stock transfer approved and marked as in transit.');
    }

    /**
     * Create a restock order (single product or bulk multiple products).
     */
    public function storeOrder(Request $request)
    {
        $user = auth()->user();
        if (
            !$user->isAdmin() && !$user->isHeadBranchManager()
            && !$user->hasPermission('stock-management.restock')
            && !$user->hasPermission('stock-management.initiate-restock')
        ) {
            return back()->withErrors(['permission' => 'You do not have permission to create restock orders.']);
        }

        $isBulk = $request->has('product_id') && is_array($request->input('product_id'));

        $rules = [
            'reference_number' => 'nullable|string|max:128',
            'dealership_name' => 'nullable|string|max:255',
            'expected_at' => 'nullable|date',
        ];
        if ($user->branch_id) {
            $rules['branch_id'] = 'nullable';
        } else {
            $rules['branch_id'] = 'required|exists:branches,id';
        }
        if (!$isBulk) {
            $rules['product_id'] = 'required|exists:products,id';
            $rules['quantity'] = 'required|integer|min:1';
            $rules['total_acquisition_cost'] = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($rules);
        $branchId = $user->branch_id ?? $validated['branch_id'];

        if ($isBulk) {
            $rawIds = $request->input('product_id', []);
            $rawQty = $request->input('quantity', []);
            $rawCosts = $request->input('total_acquisition_cost', []);
            $productIds = [];
            $quantities = [];
            $costs = [];
            foreach (array_keys($rawIds) as $i) {
                if (!empty($rawIds[$i])) {
                    $productIds[] = $rawIds[$i];
                    $quantities[] = (int) ($rawQty[$i] ?? 1);
                    $costs[] = isset($rawCosts[$i]) && $rawCosts[$i] !== '' ? (float) $rawCosts[$i] : null;
                }
            }
            if (count($productIds) < 1) {
                return back()->withErrors(['product_id' => 'Add at least one product with a quantity.']);
            }
            if (count($productIds) !== count(array_unique($productIds))) {
                return back()->withErrors(['product_id' => 'Each product can only appear once in this order.']);
            }
            foreach ($productIds as $pid) {
                if (!\App\Models\Product::where('id', $pid)->exists()) {
                    return back()->withErrors(['product_id' => 'Invalid product selected.']);
                }
            }
            foreach ($quantities as $q) {
                if ($q < 1) {
                    return back()->withErrors(['quantity' => 'Quantity must be at least 1 for each product.']);
                }
            }
            $batchBase = RestockOrder::generateOrderNumber();
            $orders = [];
            foreach (array_keys($productIds) as $i) {
                $order = RestockOrder::create([
                    'order_number' => $batchBase . '-' . ($i + 1),
                    'order_batch' => $batchBase,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'branch_id' => $branchId,
                    'product_id' => $productIds[$i],
                    'quantity_ordered' => $quantities[$i],
                    'quantity_received' => 0,
                    'total_acquisition_cost' => $costs[$i] ?? null,
                    'status' => RestockOrder::STATUS_PENDING,
                    'dealership_name' => $validated['dealership_name'] ?? null,
                    'expected_at' => $validated['expected_at'] ?? null,
                    'ordered_at' => now(),
                    'created_by' => $user->id,
                ]);
                $orders[] = $order;
            }
            $first = $orders[0]->load(['branch', 'product']);
            $branch = $first->branch;
            $users = User::usersForStockNotifications([$branchId]);
            if ($users->isNotEmpty()) {
                $lines = collect($orders)->map(fn($o) => $o->product->name . ' × ' . $o->quantity_ordered)->implode(', ');
                $title = 'Restock order ' . $batchBase . ' created (bulk)';
                $message = $lines . ' – ordered for ' . $branch->name . '. Receive when it arrives.';
                $url = route('stock-management.orders.show', $first);
                Notification::send($users, new StockActivityNotification($title, $message, $url, 'restock_created'));
                Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $message, $url, 'View order'));
            }
            return redirect()->route('stock-management.index')->with('success', 'Restock order ' . $batchBase . ' created with ' . count($orders) . ' product(s). Receive stock when it arrives.');
        }

        $order = RestockOrder::create([
            'order_number' => RestockOrder::generateOrderNumber(),
            'reference_number' => $validated['reference_number'] ?? null,
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'quantity_ordered' => $validated['quantity'],
            'quantity_received' => 0,
            'total_acquisition_cost' => isset($validated['total_acquisition_cost']) ? (float) $validated['total_acquisition_cost'] : null,
            'status' => RestockOrder::STATUS_PENDING,
            'dealership_name' => $validated['dealership_name'] ?? null,
            'expected_at' => $validated['expected_at'] ?? null,
            'ordered_at' => now(),
            'created_by' => $user->id,
        ]);

        $order->load(['branch', 'product']);
        $users = User::usersForStockNotifications([$branchId]);
        if ($users->isNotEmpty()) {
            $title = 'Restock order ' . $order->order_number . ' created';
            $message = $order->product->name . ' – ' . $order->quantity_ordered . ' units ordered for ' . $order->branch->name . '. Receive when it arrives.';
            $url = route('stock-management.orders.show', $order);
            Notification::send($users, new StockActivityNotification($title, $message, $url, 'restock_created'));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $message, $url, 'View order'));
        }

        return redirect()->route('stock-management.index')->with('success', 'Restock order ' . $order->order_number . ' created. Receive stock when it arrives.');
    }

    /**
     * Receive stock against a restock order (updates BranchStock and InventoryMovement).
     */
    public function receiveOrder(Request $request, RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isHeadBranchManager()) {
            return back()->withErrors(['permission' => 'You do not have permission to receive restock orders.']);
        }
        if (!$restockOrder->isReceivable()) {
            return back()->withErrors(['status' => 'This order cannot receive more stock.']);
        }

        $maxReceivable = $restockOrder->quantity_outstanding;
        $validated = $request->validate([
            'quantity_received' => 'required|integer|min:1|max:' . $maxReceivable,
            'notes' => 'nullable|string|max:500',
            'imeis' => 'nullable|string|max:2000',
            'imei_file' => 'nullable|file|max:2048',
            'mark_order_complete' => 'nullable',
        ]);
        $qty = (int) $validated['quantity_received'];
        $markComplete = $request->boolean('mark_order_complete');
        $rawImeis = $this->collectImeisFromRequest($request);

        $imeis = [];
        if (!empty($rawImeis)) {
            $validation = ImeiHelper::validateImeis($rawImeis);
            if (!empty($validation['invalid'])) {
                $messages = [];
                foreach (array_slice($validation['invalid'], 0, 5, true) as $value => $reason) {
                    $messages[] = '"' . $value . '" – ' . $reason;
                }
                $msg = 'Invalid IMEI(s) (from pasted list or uploaded file). ' . implode(' ', $messages);
                if (count($validation['invalid']) > 5) {
                    $msg .= ' … and ' . (count($validation['invalid']) - 5) . ' more.';
                }
                return back()->withErrors(['imeis' => $msg]);
            }
            $imeis = $validation['valid'];
        }

        DB::transaction(function () use ($restockOrder, $qty, $validated, $markComplete, $user) {
            $branchId = $restockOrder->branch_id;
            $productId = $restockOrder->product_id;
            $newReceived = $restockOrder->quantity_received + $qty;
            $orderFullyReceived = $newReceived >= $restockOrder->quantity_ordered;

            InventoryMovementService::recordRestockReceipt(
                $branchId,
                $productId,
                $qty,
                $restockOrder->id,
                $validated['notes'] ?? null,
                $user->id
            );

            // Complete order only if user checked the box or received full quantity
            $status = ($markComplete || $orderFullyReceived)
                ? RestockOrder::STATUS_RECEIVED_FULL
                : RestockOrder::STATUS_RECEIVED_PARTIAL;

            $restockOrder->update([
                'quantity_received' => $newReceived,
                'status' => $status,
                'received_at' => now(),
            ]);
        });

        $order = $restockOrder->fresh();
        $order->load(['branch', 'product']);
        $users = User::usersForStockNotifications([$order->branch_id]);
        if ($users->isNotEmpty()) {
            if ($order->status === RestockOrder::STATUS_RECEIVED_FULL) {
                $title = 'Restock order ' . $order->order_number . ' completed';
                $msg = $order->product->name . ' – ' . $order->quantity_received . ' units received at ' . $order->branch->name . '.';
            } else {
                $title = 'Stock received for order ' . $order->order_number;
                $msg = $order->product->name . ' – ' . $qty . ' units received (partial). Order remains open.';
            }
            $url = route('stock-management.orders.show', $order);
            Notification::send($users, new StockActivityNotification($title, $msg, $url, 'restock_received'));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $msg, $url, 'View order'));
        }

        if ($order->status === RestockOrder::STATUS_RECEIVED_FULL) {
            $message = $order->quantity_received >= $order->quantity_ordered
                ? 'Stock received and order completed. ' . $order->order_number . '.'
                : 'Stock recorded and order marked complete. ' . $order->order_number . '.';
        } else {
            $message = 'Stock recorded. Order remains open for more deliveries. ' . $order->order_number . '.';
        }
        return redirect()->back()->with('success', $message);
    }

    /**
     * Approve (fully receive) a restock order – receive full outstanding quantity in one step. Optional IMEIs.
     */
    public function approveOrder(Request $request, RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isHeadBranchManager()) {
            return back()->withErrors(['permission' => 'You do not have permission to approve restock orders.']);
        }
        if (!$restockOrder->isReceivable()) {
            return back()->withErrors(['status' => 'This order cannot be approved.']);
        }
        $qty = $restockOrder->quantity_outstanding;
        if ($qty < 1) {
            return back()->withErrors(['status' => 'No outstanding quantity to receive.']);
        }

        $validated = $request->validate([
            'imeis' => 'nullable|string|max:2000',
            'imei_file' => 'nullable|file|max:2048',
        ]);
        $rawImeis = $this->collectImeisFromRequest($request);

        $imeis = [];
        if (!empty($rawImeis)) {
            $validation = ImeiHelper::validateImeis($rawImeis);
            if (!empty($validation['invalid'])) {
                $messages = [];
                foreach (array_slice($validation['invalid'], 0, 5, true) as $value => $reason) {
                    $messages[] = '"' . $value . '" – ' . $reason;
                }
                $msg = 'Invalid IMEI(s) (from pasted list or uploaded file). ' . implode(' ', $messages);
                if (count($validation['invalid']) > 5) {
                    $msg .= ' … and ' . (count($validation['invalid']) - 5) . ' more.';
                }
                return back()->withErrors(['imeis' => $msg]);
            }
            $imeis = $validation['valid'];
        }

        DB::transaction(function () use ($restockOrder, $qty, $user) {
            $branchId = $restockOrder->branch_id;
            $productId = $restockOrder->product_id;
            $newReceived = $restockOrder->quantity_received + $qty;

            InventoryMovementService::recordRestockReceipt(
                $branchId,
                $productId,
                $qty,
                $restockOrder->id,
                'Full approval',
                $user->id
            );

            $restockOrder->update([
                'quantity_received' => $newReceived,
                'status' => RestockOrder::STATUS_RECEIVED_FULL,
                'received_at' => now(),
            ]);
        });

        $order = $restockOrder->fresh();
        $order->load(['branch', 'product']);
        $users = User::usersForStockNotifications([$order->branch_id]);
        if ($users->isNotEmpty()) {
            $title = 'Restock order ' . $order->order_number . ' fully received';
            $msg = $order->product->name . ' – ' . $order->quantity_received . ' units received at ' . $order->branch->name . '.';
            $url = route('stock-management.orders.show', $order);
            Notification::send($users, new StockActivityNotification($title, $msg, $url, 'restock_approved'));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $msg, $url, 'View order'));
        }

        return redirect()->back()->with('success', 'Restock order ' . $restockOrder->order_number . ' approved and fully received.');
    }

    /**
     * Update restock order quantity (e.g. correct a mistake). Requires password confirmation.
     */
    public function updateOrderQuantity(Request $request, RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isHeadBranchManager()) {
            return back()->withErrors(['permission' => 'You do not have permission to edit restock order quantity.']);
        }
        if ($restockOrder->isRejected()) {
            return back()->withErrors(['status' => 'Cannot edit quantity for a rejected order.']);
        }

        $minQty = $restockOrder->quantity_received;
        $validated = $request->validate([
            'quantity_ordered' => 'required|integer|min:' . $minQty . '|max:99999',
            'password' => 'required|string',
        ], [
            'quantity_ordered.min' => 'Quantity cannot be less than the amount already received (' . $minQty . ').',
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'The password you entered is incorrect.']);
        }

        $previous = $restockOrder->quantity_ordered;
        $restockOrder->update(['quantity_ordered' => (int) $validated['quantity_ordered']]);

        return redirect()->back()->with('success', 'Quantity updated from ' . $previous . ' to ' . $restockOrder->quantity_ordered . ' for order ' . $restockOrder->order_number . '.');
    }

    /**
     * Normalize a single IMEI value: strip BOM, trim, and remove all whitespace to avoid wrong values.
     */
    protected function normalizeImei(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // UTF-8 BOM
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value); // remove all internal spaces/tabs/newlines
        return $value;
    }

    /**
     * Parse IMEI string (one per line or comma-separated) into trimmed, non-empty array.
     */
    protected function parseImeisFromRequest(?string $value): array
    {
        $value = $value ?? '';
        $value = $this->normalizeImei($value);
        if ($value === '') {
            return [];
        }
        $lines = preg_split('/[\r\n,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $imeis = [];
        foreach ($lines as $line) {
            $imei = $this->normalizeImei($line);
            if ($imei !== '') {
                $imeis[] = $imei;
            }
        }
        return array_values($imeis);
    }

    /**
     * Parse IMEIs from an uploaded CSV or Excel file. Expects first column or a column named "imei".
     */
    protected function parseImeisFromUploadedFile($file): array
    {
        if (!$file || !$file->isValid()) {
            return [];
        }
        $path = $file->getRealPath();
        if (!is_readable($path)) {
            return [];
        }
        $ext = strtolower($file->getClientOriginalExtension() ?? '');

        $rows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return [];
            }
            foreach ($lines as $line) {
                $rows[] = str_getcsv($line);
            }
        } else {
            try {
                $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public function array(array $array)
                    {
                        return $array;
                    }
                }, $file);
                $rows = $data[0] ?? [];
            } catch (\Throwable $e) {
                return [];
            }
        }

        if (empty($rows)) {
            return [];
        }
        $header = $rows[0];
        $imeiColIndex = 0;
        $headerIsImei = false;
        foreach ($header as $i => $cell) {
            $normalized = $this->normalizeImei((string) $cell);
            if (strtolower($normalized) === 'imei') {
                $imeiColIndex = $i;
                $headerIsImei = true;
                break;
            }
        }
        $imeis = [];
        $startRow = $headerIsImei ? 1 : 0;
        for ($r = $startRow; $r < count($rows); $r++) {
            $row = $rows[$r];
            $val = $row[$imeiColIndex] ?? null;
            if ($val !== null && $val !== '') {
                $imei = $this->normalizeImei((string) $val);
                if ($imei !== '' && $imei !== 'imei') {
                    $imeis[] = $imei;
                }
            }
        }
        return array_values($imeis);
    }

    /**
     * Collect IMEIs from request: textarea + optional file. Merges and returns unique list.
     */
    protected function collectImeisFromRequest(Request $request): array
    {
        $fromText = $this->parseImeisFromRequest($request->input('imeis'));
        $fromFile = $request->hasFile('imei_file')
            ? $this->parseImeisFromUploadedFile($request->file('imei_file'))
            : [];
        $merged = array_merge($fromText, $fromFile);
        $normalized = [];
        foreach ($merged as $imei) {
            $imei = $this->normalizeImei((string) $imei);
            if ($imei !== '' && $imei !== 'imei') {
                $normalized[] = $imei;
            }
        }
        return array_values(array_unique($normalized));
    }

    /**
     * Reject a restock order (cancel with optional reason).
     */
    public function rejectOrder(Request $request, RestockOrder $restockOrder)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isHeadBranchManager()) {
            return back()->withErrors(['permission' => 'You do not have permission to reject restock orders.']);
        }
        if (!$restockOrder->canBeRejected()) {
            return back()->withErrors(['status' => 'This order cannot be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $restockOrder->update([
            'status' => RestockOrder::STATUS_CANCELLED,
            'rejected_at' => now(),
            'rejected_by' => $user->id,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $restockOrder->load(['branch', 'product']);
        $users = User::usersForStockNotifications([$restockOrder->branch_id]);
        if ($users->isNotEmpty()) {
            $title = 'Restock order ' . $restockOrder->order_number . ' rejected';
            $reason = $validated['rejection_reason'] ?? '';
            $msg = $restockOrder->product->name . ' – Order was rejected.' . ($reason !== '' ? ' Reason: ' . Str::limit($reason, 100) : '');
            $url = route('stock-management.orders.show', $restockOrder);
            Notification::send($users, new StockActivityNotification($title, $msg, $url, 'restock_rejected'));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $msg, $url, 'View order'));
        }

        return redirect()->back()->with('success', 'Restock order ' . $restockOrder->order_number . ' has been rejected.');
    }
}
