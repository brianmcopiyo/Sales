<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\BranchStock;
use App\Models\StockAdjustment;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Region;
use App\Models\Device;
use App\Models\ActivityLog;
use App\Services\InventoryMovementService;
use App\Exports\StockTakesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StockTakeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = StockTake::with(['branch.region', 'creator', 'approver', 'items.product'])
            ->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest();

        // Filter by region
        if ($request->has('region_id') && $request->region_id) {
            $query->whereHas('branch', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        // Filter by branch (admin only)
        if ($request->has('branch_id') && $request->branch_id && $user->isAdmin()) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('stock_take_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('stock_take_date', '<=', $request->date_to);
        }

        $stockTakes = $query->paginate(15)->withQueryString();

        // Stats (apply same filters)
        $statsQuery = StockTake::when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->has('region_id') && $request->region_id, function ($q) use ($request) {
                $q->whereHas('branch', function ($subQ) use ($request) {
                    $subQ->where('region_id', $request->region_id);
                });
            })
            ->when($request->has('branch_id') && $request->branch_id && $user->isAdmin(), fn($q) => $q->where('branch_id', $request->branch_id));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
        ];

        // Get regions for filter
        $regions = Region::where('is_active', true)->orderBy('name')->get();

        // Get branches for filter (restrict to allowed when user has branch_id)
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchesQuery = Branch::where('is_active', true)->with('region')->orderBy('name');
        if ($allowedBranchIds !== null) {
            $branchesQuery->whereIn('id', $allowedBranchIds);
        }
        $branches = $branchesQuery->get()->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'region_id' => $branch->region_id
            ];
        });

        return view('stock-takes.index', compact('stockTakes', 'stats', 'regions', 'branches'));
    }

    public function export(Request $request)
    {
        $filename = 'stock-takes-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new StockTakesExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create()
    {
        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        // Get all active products
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get branch stocks for the user's branch (if set) for initial product stock display
        $branchStocks = collect();
        if ($user->branch_id) {
            $branchStocks = BranchStock::where('branch_id', $user->branch_id)
                ->with('product')
                ->get()
                ->keyBy('product_id');
        }

        // Prepare products data with current stock for JavaScript
        $productsData = $products->map(function ($product) use ($branchStocks) {
            $branchStock = $branchStocks->get($product->id);
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $branchStock ? $branchStock->quantity : 0
            ];
        })->values();

        return view('stock-takes.create', compact('branches', 'products', 'branchStocks', 'productsData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'stock_take_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.opening_stock' => 'required|integer|min:0',
        ]);

        $user = Auth::user();

        // Ensure user can only create for their branch (unless admin)
        if ($user->branch_id && $validated['branch_id'] !== $user->branch_id && !$user->isAdmin()) {
            return back()->withErrors(['branch_id' => 'You can only create stock takes for your branch.'])->withInput();
        }

        DB::transaction(function () use ($validated, $user, &$stockTake) {
            $stockTake = StockTake::create([
                'branch_id' => $validated['branch_id'],
                'stock_take_date' => $validated['stock_take_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => !empty($validated['items']) ? 'in_progress' : 'draft',
                'created_by' => $user->id,
            ]);

            // Add items if provided
            if (!empty($validated['items'])) {
                $productIds = [];
                foreach ($validated['items'] as $item) {
                    // Prevent duplicate products
                    if (in_array($item['product_id'], $productIds)) {
                        continue;
                    }
                    $productIds[] = $item['product_id'];

                    // Get current system quantity from BranchStock
                    $branchStock = BranchStock::where('branch_id', $validated['branch_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();

                    $systemQuantity = $branchStock ? $branchStock->quantity : 0;

                    StockTakeItem::create([
                        'stock_take_id' => $stockTake->id,
                        'product_id' => $item['product_id'],
                        'system_quantity' => $item['opening_stock'], // Use opening stock provided
                        'physical_quantity' => null, // Closing stock - to be updated later
                        'variance' => 0,
                    ]);
                }
            }

            ActivityLog::log(
                $user->id,
                'stock_take_created',
                "Created stock take #{$stockTake->stock_take_number} for branch: {$stockTake->branch->name}",
                StockTake::class,
                $stockTake->id,
                ['stock_take_number' => $stockTake->stock_take_number, 'branch_id' => $stockTake->branch_id]
            );
        });

        return redirect()->route('stock-takes.show', $stockTake)
            ->with('success', 'Stock take session created successfully.' . (!empty($validated['items']) ? ' Products added. You can update closing stocks later.' : ' Add products to start counting.'));
    }

    public function show(StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        $stockTake->load(['branch', 'creator', 'approver', 'items.product', 'items.counter']);

        // Calculate summary statistics
        $totalItems = $stockTake->items->count();
        $countedItems = $stockTake->items->whereNotNull('physical_quantity')->count();
        $itemsWithVariance = $stockTake->items->filter(fn($item) => $item->variance !== 0)->count();
        $totalVariance = $stockTake->items->sum('variance');
        $overstockCount = $stockTake->items->filter(fn($item) => $item->variance > 0)->count();
        $shortageCount = $stockTake->items->filter(fn($item) => $item->variance < 0)->count();

        $summary = [
            'total_items' => $totalItems,
            'counted_items' => $countedItems,
            'pending_items' => $totalItems - $countedItems,
            'items_with_variance' => $itemsWithVariance,
            'total_variance' => $totalVariance,
            'overstock_count' => $overstockCount,
            'shortage_count' => $shortageCount,
        ];

        return view('stock-takes.show', compact('stockTake', 'summary'));
    }

    public function edit(StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        // Prevent editing approved or cancelled stock takes
        if (!$stockTake->canBeEdited()) {
            return redirect()->route('stock-takes.show', $stockTake)
                ->withErrors(['error' => 'This stock take cannot be edited.']);
        }

        $stockTake->load(['branch', 'items.product']);

        // Get all products that can be added: branch stock (any quantity including 0) + products with no branch stock record
        $branchStockRecords = BranchStock::where('branch_id', $stockTake->branch_id)
            ->with('product')
            ->get();

        $productIdsWithBranchStock = $branchStockRecords->pluck('product_id')->toArray();
        $productsWithNoBranchStock = Product::where('is_active', true)
            ->whereNotIn('id', $productIdsWithBranchStock)
            ->orderBy('name')
            ->get();

        $branchStocks = $branchStockRecords->concat(
            $productsWithNoBranchStock->map(fn ($p) => (object) [
                'product_id' => $p->id,
                'product' => $p,
                'quantity' => 0,
            ])
        )->sortBy(fn ($x) => $x->product->name ?? '')->values();

        // Get products already in stock take
        $existingProductIds = $stockTake->items->pluck('product_id')->toArray();

        return view('stock-takes.edit', compact('stockTake', 'branchStocks', 'existingProductIds'));
    }

    public function update(Request $request, StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        // Prevent editing approved or cancelled stock takes
        if (!$stockTake->canBeEdited()) {
            return back()->withErrors(['error' => 'This stock take cannot be edited.']);
        }

        $validated = $request->validate([
            'stock_take_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $stockTake->update([
            'stock_take_date' => $validated['stock_take_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLog::log(
            $user->id,
            'stock_take_updated',
            "Updated stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number]
        );

        return redirect()->route('stock-takes.show', $stockTake)
            ->with('success', 'Stock take updated successfully.');
    }

    public function addItem(Request $request, StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if (!$stockTake->canBeEdited()) {
            return back()->withErrors(['error' => 'Cannot add items to this stock take.']);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Check if product already exists in stock take
        if ($stockTake->items()->where('product_id', $validated['product_id'])->exists()) {
            return back()->withErrors(['product_id' => 'This product is already in the stock take.']);
        }

        // Get current system quantity from BranchStock
        $branchStock = BranchStock::where('branch_id', $stockTake->branch_id)
            ->where('product_id', $validated['product_id'])
            ->first();

        $systemQuantity = $branchStock ? $branchStock->quantity : 0;

        // Update status to in_progress if still draft
        if ($stockTake->isDraft()) {
            $stockTake->update(['status' => 'in_progress']);
        }

        StockTakeItem::create([
            'stock_take_id' => $stockTake->id,
            'product_id' => $validated['product_id'],
            'system_quantity' => $systemQuantity,
            'physical_quantity' => null,
            'variance' => 0,
        ]);

        ActivityLog::log(
            $user->id,
            'stock_take_item_added',
            "Added product to stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number, 'product_id' => $validated['product_id']]
        );

        return back()->with('success', 'Product added to stock take successfully.');
    }

    public function updateItem(Request $request, StockTake $stockTake, StockTakeItem $item)
    {
        // Verify item belongs to stock take
        if ($item->stock_take_id !== $stockTake->id) {
            abort(404);
        }

        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if (!$stockTake->canBeEdited()) {
            return back()->withErrors(['error' => 'Cannot update items in this stock take.']);
        }

        $validated = $request->validate([
            'physical_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'imeis' => 'nullable|string|max:2000',
            'imei_file' => 'nullable|file|max:2048',
        ]);

        $item->update([
            'physical_quantity' => $validated['physical_quantity'],
            'notes' => $validated['notes'] ?? null,
            'counted_by' => $user->id,
            'counted_at' => now(),
        ]);

        // Recalculate variance (will be done automatically by model boot, but ensure it)
        $item->refresh();
        $item->variance = $item->calculateVariance();
        $item->save();

        // Optional: attach IMEI numbers – register devices that don't exist (confirm at this branch)
        $imeis = $this->collectImeisFromRequest($request);
        $branchId = $stockTake->branch_id;
        $productId = $item->product_id;
        $registered = 0;
        $errors = [];
        foreach ($imeis as $imei) {
            if (Device::where('imei', $imei)->exists()) {
                continue; // already registered – confirm only, no duplicate
            }
            try {
                $deviceData = [
                    'imei' => $imei,
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'status' => 'available',
                ];
                if (Schema::hasColumn('devices', 'stock_counted')) {
                    $deviceData['stock_counted'] = true;
                }
                Device::create($deviceData);
                $registered++;
            } catch (\Throwable $e) {
                $errors[] = "IMEI {$imei}: " . $e->getMessage();
            }
        }
        if (!empty($errors)) {
            return back()->withErrors(['imeis' => implode(' ', array_slice($errors, 0, 3))])->with('success', 'Count saved.' . ($registered ? " {$registered} device(s) registered." : ''));
        }

        $existingImeis = $item->submitted_imeis ?? [];
        $item->update(['submitted_imeis' => array_values(array_unique(array_merge($existingImeis, $imeis)))]);

        $message = 'Count updated successfully.';
        if ($registered > 0) {
            $message .= " {$registered} device(s) registered.";
        }

        ActivityLog::log(
            $user->id,
            'stock_take_item_counted',
            "Counted product in stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number, 'product_id' => $item->product_id, 'variance' => $item->variance]
        );

        return back()->with('success', $message);
    }

    public function removeItem(StockTake $stockTake, StockTakeItem $item)
    {
        // Verify item belongs to stock take
        if ($item->stock_take_id !== $stockTake->id) {
            abort(404);
        }

        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if (!$stockTake->canBeEdited()) {
            return back()->withErrors(['error' => 'Cannot remove items from this stock take.']);
        }

        $item->delete();

        ActivityLog::log(
            $user->id,
            'stock_take_item_removed',
            "Removed product from stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number, 'product_id' => $item->product_id]
        );

        return back()->with('success', 'Product removed from stock take successfully.');
    }

    public function complete(Request $request, StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if (!$stockTake->canBeEdited()) {
            return back()->withErrors(['error' => 'This stock take cannot be completed.']);
        }

        // Validate item counts if provided
        if ($request->has('items')) {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.item_id' => 'required|exists:stock_take_items,id',
                'items.*.physical_quantity' => 'required|integer|min:0',
                'items.*.imeis' => 'nullable|string|max:2000',
                'items.*.imei_file' => 'nullable|file|max:2048',
            ]);

            // Update items with counts and optional IMEIs (from text and/or file, like restock)
            foreach ($validated['items'] as $index => $itemData) {
                $item = StockTakeItem::find($itemData['item_id']);

                // Verify item belongs to this stock take
                if ($item && $item->stock_take_id === $stockTake->id) {
                    $item->update([
                        'physical_quantity' => $itemData['physical_quantity'],
                        'counted_by' => $user->id,
                        'counted_at' => now(),
                    ]);

                    // Recalculate variance
                    $item->refresh();
                    $item->variance = $item->calculateVariance();
                    $item->save();

                    // Optional IMEIs for this item – from textarea and/or uploaded file (same as restock)
                    $imeiText = $itemData['imeis'] ?? '';
                    $imeiFile = $request->file("items.{$index}.imei_file");
                    $imeis = $this->collectImeisFromTextAndFile($imeiText, $imeiFile);
                    $branchId = $stockTake->branch_id;
                    $productId = $item->product_id;
                    foreach ($imeis as $imei) {
                        if (Device::where('imei', $imei)->exists()) {
                            continue;
                        }
                        try {
                            $deviceData = [
                                'imei' => $imei,
                                'product_id' => $productId,
                                'branch_id' => $branchId,
                                'status' => 'available',
                            ];
                            if (Schema::hasColumn('devices', 'stock_counted')) {
                                $deviceData['stock_counted'] = true;
                            }
                            Device::create($deviceData);
                        } catch (\Throwable $e) {
                            // continue with other IMEIs
                        }
                    }
                    $existingImeis = $item->submitted_imeis ?? [];
                    $item->update(['submitted_imeis' => array_values(array_unique(array_merge($existingImeis, $imeis)))]);

                    ActivityLog::log(
                        $user->id,
                        'stock_take_item_counted',
                        "Counted product in stock take #{$stockTake->stock_take_number}",
                        StockTake::class,
                        $stockTake->id,
                        ['stock_take_number' => $stockTake->stock_take_number, 'product_id' => $item->product_id, 'variance' => $item->variance]
                    );
                }
            }
        }

        // Check if all items have been counted
        $uncountedItems = $stockTake->items()->whereNull('physical_quantity')->count();
        if ($uncountedItems > 0) {
            return back()->withErrors(['error' => "Cannot complete stock take. {$uncountedItems} item(s) still need to be counted."]);
        }

        $stockTake->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        ActivityLog::log(
            $user->id,
            'stock_take_completed',
            "Completed stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number]
        );

        return back()->with('success', 'Stock take marked as completed. Ready for approval.');
    }

    public function approve(Request $request, StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if (!$stockTake->canBeApproved()) {
            return back()->withErrors(['error' => 'This stock take cannot be approved.']);
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        // Prevent timeout when approving stock takes with many items (observer + DB work per item).
        set_time_limit(120);

        DB::transaction(function () use ($stockTake, $user, $validated) {
            // Approve stock take
            $stockTake->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'notes' => $stockTake->notes ? $stockTake->notes . "\n\nApproval Notes: " . ($validated['approval_notes'] ?? '') : ($validated['approval_notes'] ?? null),
            ]);

            $targetBranchId = $stockTake->branch_id;

            // Query 1: IMEI transfers – move each device to this branch so device.branch_id matches physical location, then record movements
            $itemsForTransfers = StockTakeItem::where('stock_take_id', $stockTake->id)->get();
            foreach ($itemsForTransfers as $item) {
                $imeis = $item->submitted_imeis ?? [];
                if (empty($imeis) || !is_array($imeis)) {
                    continue;
                }
                $productId = $item->product_id;
                foreach ($imeis as $imei) {
                    $imei = preg_replace('/\D/', '', (string) $imei);
                    if ($imei === '' || strlen($imei) !== 15) {
                        continue;
                    }
                    $device = Device::where('imei', $imei)->first();
                    if (!$device || (string) $device->branch_id === (string) $targetBranchId) {
                        continue;
                    }
                    $fromBranchId = $device->branch_id;
                    $device->update(['branch_id' => $targetBranchId]);
                    InventoryMovementService::record(
                        (string) $fromBranchId,
                        (string) $productId,
                        'transfer',
                        -1,
                        StockTake::class,
                        (string) $stockTake->id,
                        "Stock take #{$stockTake->stock_take_number} – device transferred out",
                        null,
                        $user->id ? (string) $user->id : null
                    );
                    InventoryMovementService::record(
                        (string) $targetBranchId,
                        (string) $productId,
                        'transfer',
                        1,
                        StockTake::class,
                        (string) $stockTake->id,
                        "Stock take #{$stockTake->stock_take_number} – device transferred in",
                        null,
                        $user->id ? (string) $user->id : null
                    );
                }
            }

            // Query 2: items for branch stock adjustment (independent query, no shared state with above)
            $itemsForAdjustment = StockTakeItem::where('stock_take_id', $stockTake->id)->get();
            foreach ($itemsForAdjustment as $item) {
                if ($item->physical_quantity === null) {
                    continue;
                }

                $branchStock = BranchStock::firstOrCreate(
                    ['branch_id' => $stockTake->branch_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $branchStock->refresh(); // latest from DB (e.g. after IMEI transfers in this transaction)

                $quantityBefore = (int) $branchStock->quantity;
                $quantityAfter = (int) $item->physical_quantity;
                $adjustmentAmount = $quantityAfter - $quantityBefore;

                // Record movement (service updates BranchStock so movements and stock stay in sync)
                if ($adjustmentAmount !== 0) {
                    InventoryMovementService::recordStockTake(
                        $stockTake->branch_id,
                        $item->product_id,
                        $adjustmentAmount,
                        $stockTake->id,
                        "Stock take #{$stockTake->stock_take_number} - Adjustment: {$adjustmentAmount}",
                        $user->id
                    );
                }

                StockAdjustment::create([
                    'branch_id' => $stockTake->branch_id,
                    'product_id' => $item->product_id,
                    'stock_take_id' => $stockTake->id,
                    'adjustment_type' => 'stock_take',
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'adjustment_amount' => $adjustmentAmount,
                    'reason' => "Stock take #{$stockTake->stock_take_number}" . ($adjustmentAmount !== 0 ? " - Adjustment: {$adjustmentAmount}" : ' - Count confirmed'),
                    'adjusted_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
            }

            ActivityLog::log(
                $user->id,
                'stock_take_approved',
                "Approved stock take #{$stockTake->stock_take_number} and applied adjustments",
                StockTake::class,
                $stockTake->id,
                ['stock_take_number' => $stockTake->stock_take_number]
            );
        });

        return redirect()->route('stock-takes.show', $stockTake)
            ->with('success', 'Stock take approved and adjustments applied successfully.');
    }

    public function cancel(Request $request, StockTake $stockTake)
    {
        // Check branch access
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403, 'You do not have access to this stock take.');
        }

        if ($stockTake->isApproved() || $stockTake->isCancelled()) {
            return back()->withErrors(['error' => 'This stock take cannot be cancelled.']);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $stockTake->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'notes' => $stockTake->notes ? $stockTake->notes . "\n\nCancelled: " . ($validated['cancellation_reason'] ?? '') : ($validated['cancellation_reason'] ?? null),
        ]);

        ActivityLog::log(
            $user->id,
            'stock_take_cancelled',
            "Cancelled stock take #{$stockTake->stock_take_number}",
            StockTake::class,
            $stockTake->id,
            ['stock_take_number' => $stockTake->stock_take_number]
        );

        return redirect()->route('stock-takes.index')
            ->with('success', 'Stock take cancelled successfully.');
    }

    /**
     * Normalize a single IMEI value: strip BOM, trim, remove non-digits for validation.
     */
    protected function normalizeImei(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value);
        return $value;
    }

    /**
     * Parse IMEI string (one per line or comma-separated) into array.
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
     * Parse IMEIs from uploaded CSV or Excel (first column or column named "imei").
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
                    $imeis[] = preg_replace('/\D/', '', $imei);
                }
            }
        }
        return array_values($imeis);
    }

    /**
     * Collect IMEIs from request: textarea + optional file. Unique, 15 digits only.
     */
    protected function collectImeisFromRequest(Request $request): array
    {
        $fromText = $this->parseImeisFromRequest($request->input('imeis'));
        $fromFile = $request->hasFile('imei_file')
            ? $this->parseImeisFromUploadedFile($request->file('imei_file'))
            : [];
        return $this->mergeAndNormalizeImeis($fromText, $fromFile);
    }

    /**
     * Collect IMEIs from text + optional uploaded file (e.g. for complete modal per-item upload). Same as restock.
     */
    protected function collectImeisFromTextAndFile(?string $imeiText, $file): array
    {
        $fromText = $this->parseImeisFromRequest($imeiText);
        $fromFile = ($file && $file->isValid()) ? $this->parseImeisFromUploadedFile($file) : [];
        return $this->mergeAndNormalizeImeis($fromText, $fromFile);
    }

    /**
     * Merge and normalize IMEI arrays to unique 15-digit strings.
     */
    protected function mergeAndNormalizeImeis(array $fromText, array $fromFile): array
    {
        $merged = array_merge($fromText, $fromFile);
        $normalized = [];
        foreach ($merged as $imei) {
            $imei = preg_replace('/\D/', '', trim((string) $imei));
            if ($imei !== '' && strlen($imei) === 15) {
                $normalized[] = $imei;
            }
        }
        return array_values(array_unique($normalized));
    }

    /** Batch size for IMEI file processing to avoid timeouts. */
    protected const IMEI_BATCH_SIZE = 100;

    /**
     * Upload IMEI file: parse and store in cache for batch processing. Returns upload_id and total count.
     */
    public function uploadImeiFile(Request $request, StockTake $stockTake, StockTakeItem $item)
    {
        if ($item->stock_take_id !== $stockTake->id) {
            abort(404);
        }
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403);
        }
        if (!$stockTake->canBeEdited()) {
            return response()->json(['error' => 'Stock take cannot be edited.'], 422);
        }

        $request->validate(['imei_file' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls']);

        $file = $request->file('imei_file');
        $fromFile = $this->parseImeisFromUploadedFile($file);
        $imeis = $this->mergeAndNormalizeImeis([], $fromFile);

        $uploadId = Str::random(16);
        $cacheKey = "stock_take_imei_{$stockTake->id}_{$item->id}_{$uploadId}";
        Cache::put($cacheKey, $imeis, 3600);

        return response()->json([
            'upload_id' => $uploadId,
            'total' => count($imeis),
            'batch_size' => self::IMEI_BATCH_SIZE,
        ]);
    }

    /**
     * Process one batch of IMEIs: create missing devices, update item's submitted_imeis, return counts.
     */
    public function processImeiBatch(Request $request, StockTake $stockTake, StockTakeItem $item)
    {
        if ($item->stock_take_id !== $stockTake->id) {
            abort(404);
        }
        $user = Auth::user();
        if ($user->branch_id && $stockTake->branch_id !== $user->branch_id && !$user->isAdmin()) {
            abort(403);
        }
        if (!$stockTake->canBeEdited()) {
            return response()->json(['error' => 'Stock take cannot be edited.'], 422);
        }

        $request->validate([
            'upload_id' => 'required|string|size:16',
            'batch_index' => 'required|integer|min:0',
        ]);

        $uploadId = $request->input('upload_id');
        $batchIndex = (int) $request->input('batch_index');
        $cacheKey = "stock_take_imei_{$stockTake->id}_{$item->id}_{$uploadId}";
        $imeis = Cache::get($cacheKey);

        if (!is_array($imeis)) {
            return response()->json(['error' => 'Upload session expired or invalid.'], 404);
        }

        $total = count($imeis);
        $offset = $batchIndex * self::IMEI_BATCH_SIZE;
        $batch = array_slice($imeis, $offset, self::IMEI_BATCH_SIZE);

        $uploaded = 0;
        $alreadyExisting = 0;
        $neverRecorded = 0;
        $branchId = $stockTake->branch_id;
        $productId = $item->product_id;
        $validProcessed = [];

        foreach ($batch as $imei) {
            $imei = preg_replace('/\D/', '', (string) $imei);
            if (strlen($imei) !== 15) {
                $neverRecorded++;
                continue;
            }
            if (Device::where('imei', $imei)->exists()) {
                $alreadyExisting++;
                $validProcessed[] = $imei;
                continue;
            }
            try {
                $deviceData = [
                    'imei' => $imei,
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'status' => 'available',
                ];
                if (Schema::hasColumn('devices', 'stock_counted')) {
                    $deviceData['stock_counted'] = true;
                }
                Device::create($deviceData);
                $uploaded++;
                $validProcessed[] = $imei;
            } catch (\Throwable $e) {
                $neverRecorded++;
            }
        }

        $existingSubmitted = $item->submitted_imeis ?? [];
        $item->update([
            'submitted_imeis' => array_values(array_unique(array_merge($existingSubmitted, $validProcessed))),
        ]);

        $processed = $offset + count($batch);
        $done = $processed >= $total;
        if ($done) {
            Cache::forget($cacheKey);
        }

        return response()->json([
            'uploaded' => $uploaded,
            'already_existing' => $alreadyExisting,
            'never_recorded' => $neverRecorded,
            'processed' => $processed,
            'total' => $total,
            'done' => $done,
        ]);
    }
}
