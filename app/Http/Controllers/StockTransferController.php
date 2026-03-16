<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferReceptionAttachment;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchStock;
use App\Models\ActivityLog;
use App\Mail\StockTransferActivityMail;
use App\Notifications\StockTransferActivityNotification;
use App\Services\InventoryMovementService;
use App\Helpers\ImeiHelper;
use App\Exports\StockTransfersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'creator', 'receiver', 'rejectedByUser', 'returnedByUser'])
            ->when($user->branch_id && !$user->isAdmin(), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('from_branch_id', $user->branch_id)
                        ->orWhere('to_branch_id', $user->branch_id);
                });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('from_branch_id')) {
            $query->where('from_branch_id', $request->get('from_branch_id'));
        }
        if ($request->filled('to_branch_id')) {
            $query->where('to_branch_id', $request->get('to_branch_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $transfers = $query->latest()->paginate(15)->withQueryString();

        $baseQuery = StockTransfer::when($user->branch_id && !$user->isAdmin(), function ($q) use ($user) {
            $q->where(function ($q2) use ($user) {
                $q2->where('from_branch_id', $user->branch_id)
                    ->orWhere('to_branch_id', $user->branch_id);
            });
        });
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_transit' => (clone $baseQuery)->where('status', 'in_transit')->count(),
            'pending_sender_confirmation' => (clone $baseQuery)->where('status', 'pending_sender_confirmation')->count(),
            'received' => (clone $baseQuery)->where('status', 'received')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('stock-transfers.index', compact('transfers', 'stats', 'branches'));
    }

    /**
     * Export stock transfers to Excel (respects same filters as index).
     */
    public function export(Request $request)
    {
        $filename = 'stock-transfers-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new StockTransfersExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->route('stock-transfers.index')->withErrors(['branch' => 'You must be assigned to a branch to create stock transfers.']);
        }

        $userBranch = $user->branch;
        $branches = Branch::where('is_active', true)->where('id', '!=', $user->branch_id)->orderBy('name')->get();
        $products = Product::where('is_active', true)->get();

        return view('stock-transfers.create', compact('branches', 'products', 'userBranch'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->route('stock-transfers.index')->withErrors(['branch' => 'You must be assigned to a branch to create stock transfers.']);
        }

        $fromBranchId = $user->branch_id;

        // Multi-product: items[] with product_id and either quantity or IMEIs per item
        $items = $request->input('items', []);
        if (!is_array($items)) {
            $items = [];
        }
        $items = array_values(array_filter($items, function ($i) {
            return !empty($i['product_id']);
        }));

        if (!empty($items)) {
            return $this->storeMultiItemTransfer($request, $fromBranchId, $items);
        }

        // Legacy single transfer (and device prefill: product_id, quantity, imei)
        $validated = $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'imei' => 'nullable|string|max:20',
        ]);

        $validated['from_branch_id'] = $fromBranchId;
        if ((string) $validated['to_branch_id'] === (string) $validated['from_branch_id']) {
            return back()->withErrors(['to_branch_id' => 'Destination branch must be different from your branch.'])->withInput();
        }

        $imeis = [];
        if (!empty(trim($validated['imei'] ?? ''))) {
            $validation = ImeiHelper::validateImeis([$validated['imei']]);
            if (!empty($validation['invalid'])) {
                return back()->withErrors(['imei' => 'Invalid IMEI.'])->withInput();
            }
            $imeis = $validation['valid'];
        }

        $quantity = $imeis ? count($imeis) : (int) $validated['quantity'];
        $fromStock = BranchStock::where('branch_id', $validated['from_branch_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if (!$fromStock || $fromStock->available_quantity < $quantity) {
            return back()->withErrors(['quantity' => 'Insufficient stock available.'])->withInput();
        }

        $validated['quantity'] = $quantity;
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'pending';

        $transfer = DB::transaction(function () use ($validated, $fromStock, $imeis) {
            $transfer = StockTransfer::create([
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $validated['created_by'],
                'status' => $validated['status'],
            ]);

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);

            InventoryMovementService::recordTransferOut(
                $validated['from_branch_id'],
                $validated['product_id'],
                $validated['quantity'],
                $transfer->id,
                Auth::id()
            );

            ActivityLog::log(
                Auth::id(),
                'stock_transfer_created',
                "Created stock transfer #{$transfer->transfer_number} for {$validated['quantity']} units",
                StockTransfer::class,
                $transfer->id,
                ['transfer_number' => $transfer->transfer_number, 'quantity' => $validated['quantity']]
            );

            return $transfer;
        });

        $users = $transfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($transfer, 'created', []));
            Notification::send($users, new StockTransferActivityNotification($transfer, 'created', []));
        }

        return redirect()->route('stock-transfers.index')->with('success', 'Stock transfer created successfully.');
    }

    /**
     * Create one stock transfer for all items; each item has product_id and either quantity or IMEIs (textarea/file).
     */
    private function storeMultiItemTransfer(Request $request, $fromBranchId, array $items): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $toBranchId = $request->input('to_branch_id');
        if ((string) $toBranchId === (string) $fromBranchId) {
            return back()->withErrors(['to_branch_id' => 'Destination branch must be different from your branch.'])->withInput();
        }

        $resolved = [];
        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            if (empty($productId)) {
                continue;
            }
            $quantityInput = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            $imeis = $this->collectImeisFromItem($request, $index);

            if (!empty($imeis)) {
                $quantity = count($imeis);
            } else {
                if ($quantityInput < 1) {
                    return back()->withErrors(['items' => 'Item ' . ($index + 1) . ': enter quantity or IMEIs.'])->withInput();
                }
                $quantity = $quantityInput;
            }

            $imeisToAttach = [];
            if (!empty($imeis)) {
                $validation = ImeiHelper::validateImeis($imeis);
                if (!empty($validation['invalid'])) {
                    $messages = array_slice(array_map(fn ($v, $r) => $v . ': ' . $r, array_keys($validation['invalid']), $validation['invalid']), 0, 3);
                    return back()->withErrors(['items' => 'Item ' . ($index + 1) . ' invalid IMEI(s): ' . implode(' ', $messages) . (count($validation['invalid']) > 3 ? '…' : '')])->withInput();
                }
                $imeisToAttach = $validation['valid'];
            }

            $fromStock = BranchStock::where('branch_id', $fromBranchId)->where('product_id', $productId)->first();
            if (!$fromStock || $fromStock->available_quantity < $quantity) {
                $product = Product::find($productId);
                $name = $product ? $product->name : 'Product #' . $productId;
                return back()->withErrors(['items' => 'Item ' . ($index + 1) . ' (' . $name . '): insufficient stock (need ' . $quantity . ').'])->withInput();
            }

            $resolved[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'imeis' => $imeisToAttach,
            ];
        }

        if (empty($resolved)) {
            return back()->withErrors(['items' => 'Add at least one item with product and quantity or IMEIs.'])->withInput();
        }

        $notes = $request->input('notes');
        $createdBy = Auth::id();
        $totalQuantity = (int) array_sum(array_column($resolved, 'quantity'));
        $firstItem = $resolved[0];

        $transfer = DB::transaction(function () use ($fromBranchId, $toBranchId, $resolved, $notes, $createdBy, $totalQuantity, $firstItem) {
            $transfer = StockTransfer::create([
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'product_id' => $firstItem['product_id'],
                'quantity' => $totalQuantity,
                'notes' => $notes,
                'created_by' => $createdBy,
                'status' => 'pending',
            ]);

            foreach ($resolved as $r) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $r['product_id'],
                    'quantity' => $r['quantity'],
                ]);

                InventoryMovementService::recordTransferOut(
                    $fromBranchId,
                    $r['product_id'],
                    $r['quantity'],
                    $transfer->id,
                    Auth::id()
                );

            }

            ActivityLog::log(
                Auth::id(),
                'stock_transfer_created',
                "Created stock transfer #{$transfer->transfer_number} for {$totalQuantity} units (" . count($resolved) . ' item(s))',
                StockTransfer::class,
                $transfer->id,
                ['transfer_number' => $transfer->transfer_number, 'quantity' => $totalQuantity]
            );

            return $transfer;
        });

        $users = $transfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($transfer, 'created', []));
            Notification::send($users, new StockTransferActivityNotification($transfer, 'created', []));
        }

        return redirect()->route('stock-transfers.index')->with('success', 'Stock transfer created successfully.');
    }

    /**
     * Collect IMEIs from a single item: items[$index][imeis] text and items[$index][imei_file] file.
     */
    protected function collectImeisFromItem(Request $request, int $index): array
    {
        $text = $request->input("items.{$index}.imeis");
        $fromText = $this->parseImeisFromRequest(is_string($text) ? $text : null);
        $file = $request->file("items.{$index}.imei_file");
        $fromFile = $file ? $this->parseImeisFromUploadedFile($file) : [];
        $merged = array_merge($fromText, $fromFile);
        return array_values(array_unique($merged));
    }

    public function show(StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        // Check branch access
        if ($user->branch_id && $stockTransfer->from_branch_id !== $user->branch_id && $stockTransfer->to_branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        $stockTransfer->load(['fromBranch', 'toBranch', 'product', 'items.product', 'creator', 'receiver', 'rejectedByUser', 'senderConfirmedBy', 'returnedByUser', 'receptionAttachments.uploadedBy']);
        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        // Only users who belong to the recipient branch can receive (no sender, no other branch, no admin override)
        if (!$user->branch_id || $stockTransfer->to_branch_id !== $user->branch_id) {
            abort(403, 'You do not have permission to receive this transfer. Only users from the recipient branch can receive transfers.');
        }

        if ($stockTransfer->status !== 'pending' && $stockTransfer->status !== 'in_transit') {
            return back()->withErrors(['status' => 'This transfer cannot be received.']);
        }

        $stockTransfer->load('items');
        $totalQuantity = $stockTransfer->total_quantity;
        $isMultiItem = $stockTransfer->items->count() > 1;

        if ($isMultiItem) {
            $rules = [
                'received_notes' => ['nullable', 'string', 'max:2000'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
            ];
            foreach ($stockTransfer->items as $item) {
                $rules["items.{$item->id}.quantity_received"] = ['required', 'integer', 'min:0', 'max:' . $item->quantity];
            }
            $validated = $request->validate($rules);
            $quantityReceived = 0;
            $itemReceived = [];
            foreach ($stockTransfer->items as $item) {
                $qty = (int) (data_get($validated, "items.{$item->id}.quantity_received", 0));
                $itemReceived[$item->id] = $qty;
                $quantityReceived += $qty;
            }
            if ($quantityReceived < 1) {
                return back()->withErrors(['items' => 'Enter at least one unit received.'])->withInput();
            }
        } else {
            $validated = $request->validate([
                'quantity_received' => ['required', 'integer', 'min:1', 'max:' . $totalQuantity],
                'received_notes' => ['nullable', 'string', 'max:2000'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
            ]);
            $quantityReceived = (int) $validated['quantity_received'];
            $itemReceived = $stockTransfer->items->count() === 1
                ? [$stockTransfer->items->first()->id => $quantityReceived]
                : [];
        }

        $receivedNotes = $validated['received_notes'] ?? null;
        $isPartial = $quantityReceived < $totalQuantity;
        $files = $request->file('attachments') ?? [];

        DB::transaction(function () use ($stockTransfer, $quantityReceived, $receivedNotes, $isPartial, $files, $itemReceived) {
            $stockTransfer->update([
                'status' => $isPartial ? 'pending_sender_confirmation' : 'received',
                'received_by' => Auth::id(),
                'received_at' => now(),
                'quantity_received' => $quantityReceived,
                'received_notes' => $receivedNotes,
            ]);

            foreach ($stockTransfer->items as $item) {
                $qty = $itemReceived[$item->id] ?? 0;
                $item->update(['quantity_received' => $qty > 0 ? $qty : null]);
            }

            foreach ($files as $file) {
                $path = $file->store('transfer-reception-attachments', 'public');
                StockTransferReceptionAttachment::create([
                    'stock_transfer_id' => $stockTransfer->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }

            // Record incoming movements (service updates BranchStock to keep movements and stock in sync)
            foreach ($stockTransfer->items as $item) {
                $qty = (int) ($item->quantity_received ?? $item->quantity);
                if ($qty < 1) {
                    continue;
                }
                InventoryMovementService::recordTransferIn(
                    $stockTransfer->to_branch_id,
                    $item->product_id,
                    $qty,
                    $stockTransfer->id,
                    Auth::id()
                );
            }
            // Move attached devices to recipient branch (full and partial receive) so device branch reflects actual location
            $this->moveTransferDevicesToRecipientByItems($stockTransfer);

            $totalQty = $stockTransfer->total_quantity;
            $message = $isPartial
                ? "Partially received stock transfer #{$stockTransfer->transfer_number} ({$quantityReceived} of {$totalQty}) — awaiting sender confirmation"
                : "Received stock transfer #{$stockTransfer->transfer_number}";

            ActivityLog::log(
                Auth::id(),
                'stock_transfer_received',
                $message,
                StockTransfer::class,
                $stockTransfer->id,
                ['transfer_number' => $stockTransfer->transfer_number, 'quantity_received' => $quantityReceived]
            );
        });

        $users = $stockTransfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            $activity = $isPartial ? 'partial_received' : 'received';
            $payload = $isPartial ? ['quantity_received' => $quantityReceived, 'received_notes' => $receivedNotes ?? ''] : [];
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($stockTransfer, $activity, $payload));
            Notification::send($users, new StockTransferActivityNotification($stockTransfer, $activity, $payload));
        }

        $message = $isPartial
            ? "Partial reception recorded: {$quantityReceived} of {$totalQuantity} units. Recipient stock has been updated with the received quantity. Sender may confirm to finalise."
            : 'Stock transfer received successfully.';

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', $message);
    }

    /**
     * Confirm partial reception (sender branch only). Credits recipient stock and marks transfer as received.
     */
    public function confirmPartialReception(StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        if (!$user->branch_id || $stockTransfer->from_branch_id !== $user->branch_id) {
            abort(403, 'Only users from the sending branch can confirm partial reception.');
        }

        if ($stockTransfer->status !== 'pending_sender_confirmation') {
            return back()->withErrors(['status' => 'This transfer is not awaiting sender confirmation.']);
        }

        $quantityReceived = (int) ($stockTransfer->quantity_received ?? 0);
        if ($quantityReceived < 1) {
            return back()->withErrors(['status' => 'Invalid quantity received.']);
        }

        $stockTransfer->load('items');

        DB::transaction(function () use ($stockTransfer, $quantityReceived) {
            $stockTransfer->update([
                'status' => 'received',
                'sender_confirmed_by' => Auth::id(),
                'sender_confirmed_at' => now(),
            ]);

            // Recipient stock was already credited when they received; only move devices and finalise status.
            $this->moveTransferDevicesToRecipientByItems($stockTransfer);

            $totalQty = $stockTransfer->total_quantity;
            ActivityLog::log(
                Auth::id(),
                'stock_transfer_partial_confirmed',
                "Confirmed partial reception for transfer #{$stockTransfer->transfer_number}: {$quantityReceived} of {$totalQty} units credited to recipient.",
                StockTransfer::class,
                $stockTransfer->id,
                ['transfer_number' => $stockTransfer->transfer_number, 'quantity_received' => $quantityReceived]
            );
        });

        $users = $stockTransfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($stockTransfer, 'partial_confirmed', ['quantity_received' => $quantityReceived]));
            Notification::send($users, new StockTransferActivityNotification($stockTransfer, 'partial_confirmed', ['quantity_received' => $quantityReceived]));
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', "Partial reception confirmed. {$quantityReceived} units have been credited to the recipient branch.");
    }

    /**
     * Return / disagree with partial reception (sender branch only). Returns shortfall quantity to sender stock.
     */
    public function returnPartialReception(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        if (!$user->branch_id || $stockTransfer->from_branch_id !== $user->branch_id) {
            abort(403, 'Only users from the sending branch can return or disagree with partial reception.');
        }

        if ($stockTransfer->status !== 'pending_sender_confirmation') {
            return back()->withErrors(['status' => 'This transfer is not awaiting sender confirmation.']);
        }

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:2000'],
        ]);

        $stockTransfer->load('items');
        $quantityReceived = (int) ($stockTransfer->quantity_received ?? 0);
        $totalQuantity = $stockTransfer->total_quantity;
        $shortfall = $totalQuantity - $quantityReceived;
        if ($shortfall < 1) {
            return back()->withErrors(['status' => 'Invalid quantity.']);
        }

        DB::transaction(function () use ($stockTransfer, $validated, $shortfall) {
            $stockTransfer->update([
                'status' => 'returned',
                'return_reason' => $validated['return_reason'],
                'returned_by' => Auth::id(),
                'returned_at' => now(),
            ]);

            foreach ($stockTransfer->items as $item) {
                $received = (int) ($item->quantity_received ?? 0);
                $itemShortfall = $item->quantity - $received;
                if ($itemShortfall < 1) {
                    continue;
                }
                $fromStock = BranchStock::firstOrCreate(
                    ['branch_id' => $stockTransfer->from_branch_id, 'product_id' => $item->product_id],
                    ['quantity' => 0]
                );
                $fromStock->increment('quantity', $itemShortfall);
            }

            ActivityLog::log(
                Auth::id(),
                'stock_transfer_returned',
                "Returned partial reception for transfer #{$stockTransfer->transfer_number}: shortfall of {$shortfall} units credited back to sender.",
                StockTransfer::class,
                $stockTransfer->id,
                ['transfer_number' => $stockTransfer->transfer_number, 'shortfall' => $shortfall]
            );
        });

        $users = $stockTransfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($stockTransfer, 'returned', ['return_reason' => $validated['return_reason']]));
            Notification::send($users, new StockTransferActivityNotification($stockTransfer, 'returned', ['return_reason' => $validated['return_reason']]));
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'Partial reception returned. Shortfall quantity has been credited back to your branch.');
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        $user = Auth::user();
        if ($user->branch_id && $stockTransfer->from_branch_id !== $user->branch_id) {
            abort(403, 'Only the sending branch can cancel this transfer.');
        }
        if (in_array($stockTransfer->status, ['received', 'cancelled', 'pending_sender_confirmation'])) {
            return back()->withErrors(['status' => 'This transfer cannot be cancelled.']);
        }

        $stockTransfer->load('items');

        DB::transaction(function () use ($stockTransfer) {
            $stockTransfer->update(['status' => 'cancelled']);

            foreach ($stockTransfer->items as $item) {
                $fromStock = BranchStock::where('branch_id', $stockTransfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($fromStock) {
                    $fromStock->increment('quantity', $item->quantity);
                }
            }
        });

        $emails = $stockTransfer->getNotificationEmails();
        if (!empty($emails)) {
            Mail::to($emails)->send(new StockTransferActivityMail($stockTransfer, 'cancelled', []));
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Stock transfer cancelled successfully.');
    }

    /**
     * Reject a transfer with a reason. Only users from the recipient branch can reject (no sender, no other branch, no admin override).
     */
    public function reject(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        if (!$user->hasPermission('stock-transfers.reject')) {
            abort(403, 'You do not have permission to reject transfers.');
        }

        // Only users who belong to the recipient branch can reject
        if (!$user->branch_id || $stockTransfer->to_branch_id !== $user->branch_id) {
            abort(403, 'Only users from the recipient branch can reject this transfer.');
        }

        if (!in_array($stockTransfer->status, ['pending', 'in_transit'])) {
            return back()->withErrors(['status' => 'This transfer cannot be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $stockTransfer->load('items');

        DB::transaction(function () use ($stockTransfer, $validated) {
            $stockTransfer->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
            ]);

            foreach ($stockTransfer->items as $item) {
                $fromStock = BranchStock::where('branch_id', $stockTransfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($fromStock) {
                    $fromStock->increment('quantity', $item->quantity);
                }
            }

            ActivityLog::log(
                Auth::id(),
                'stock_transfer_rejected',
                "Rejected stock transfer #{$stockTransfer->transfer_number}: " . \Illuminate\Support\Str::limit($validated['rejection_reason'], 80),
                StockTransfer::class,
                $stockTransfer->id,
                ['transfer_number' => $stockTransfer->transfer_number]
            );
        });

        $users = $stockTransfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($stockTransfer, 'rejected', ['rejection_reason' => $validated['rejection_reason']]));
            Notification::send($users, new StockTransferActivityNotification($stockTransfer, 'rejected', ['rejection_reason' => $validated['rejection_reason']]));
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Stock transfer rejected.');
    }

    /**
     * Download a reception evidence attachment. Accessible to users from from/to branch or admins.
     */
    public function downloadReceptionAttachment(StockTransferReceptionAttachment $attachment)
    {
        $user = Auth::user();
        $transfer = $attachment->stockTransfer;

        if ($user->branch_id && $transfer->from_branch_id !== $user->branch_id && $transfer->to_branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this attachment.');
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $attachment->file_name);
    }

    /**
     * When this transfer has linked devices (catalog transfer), move the first N devices
     * to the recipient branch and mark them as received on the pivot.
     */
    private function moveTransferDevicesToRecipient(StockTransfer $stockTransfer, int $quantityToMove): void
    {
        // No-op: device model removed; stock is tracked by quantity only.
    }

    private function moveTransferDevicesToRecipientByItems(StockTransfer $stockTransfer): void
    {
        // No-op: device model removed; stock is tracked by quantity only.
    }

    protected function normalizeImei(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = trim($value);
        return preg_replace('/\D/', '', $value);
    }

    protected function parseImeisFromRequest(?string $value): array
    {
        $value = $value ?? '';
        if ($value === '') {
            return [];
        }
        $lines = preg_split('/[\r\n,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $imeis = [];
        foreach ($lines as $line) {
            $imei = $this->normalizeImei($line);
            if ($imei !== '' && strlen($imei) === 15) {
                $imeis[] = $imei;
            }
        }
        return array_values(array_unique($imeis));
    }

    protected function parseImeisFromUploadedFile($file): array
    {
        if (!$file || !$file->isValid()) {
            return [];
        }
        $path = $file->getRealPath();
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
                    public function array(array $array) { return $array; }
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
        foreach ($header as $i => $cell) {
            if (stripos(preg_replace('/\s+/', '', (string) $cell), 'imei') !== false) {
                $imeiColIndex = $i;
                break;
            }
        }
        $imeis = [];
        for ($r = 1; $r < count($rows); $r++) {
            $val = $rows[$r][$imeiColIndex] ?? null;
            if ($val !== null && $val !== '') {
                $imei = $this->normalizeImei((string) $val);
                if (strlen($imei) === 15) {
                    $imeis[] = $imei;
                }
            }
        }
        return array_values(array_unique($imeis));
    }

    protected function collectImeisFromRequest(Request $request): array
    {
        $fromText = $this->parseImeisFromRequest($request->input('imeis'));
        $fromFile = $request->hasFile('imei_file') ? $this->parseImeisFromUploadedFile($request->file('imei_file')) : [];
        $merged = array_merge($fromText, $fromFile);
        return array_values(array_unique($merged));
    }
}
