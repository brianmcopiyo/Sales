<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BranchStock;
use App\Models\Branch;
use App\Models\Product;
use App\Services\InventoryMovementService;

class BranchStockController extends Controller
{
    protected function allowedBranchIds()
    {
        $user = auth()->user();
        return $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        // Field agents use agent-stock-requests for their stock; redirect there
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.index');
        }
        $allowedBranchIds = $this->allowedBranchIds();

        $query = BranchStock::with(['branch', 'product'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->when($allowedBranchIds === null && $user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id));

        // Filter by branch when selected (admin: any branch; branch user: only branches they can see)
        if ($request->filled('branch_id')) {
            $requestedBranchId = $request->get('branch_id');
            $canFilterBranch = $allowedBranchIds === null || in_array($requestedBranchId, $allowedBranchIds, true);
            if ($canFilterBranch) {
                $query->where('branch_id', $requestedBranchId);
            }
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }
        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
        }

        $stocks = $query->latest()->paginate(15)->withQueryString();

        // Base query for stats: same scope + same branch/product/search filters so stats match the table
        $baseQuery = BranchStock::with('product')
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->when($allowedBranchIds === null && $user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id));
        if ($request->filled('branch_id')) {
            $requestedBranchId = $request->get('branch_id');
            $canFilterBranch = $allowedBranchIds === null || in_array($requestedBranchId, $allowedBranchIds, true);
            if ($canFilterBranch) {
                $baseQuery->where('branch_id', $requestedBranchId);
            }
        }
        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->get('product_id'));
        }
        if ($request->filled('search')) {
            $term = $request->get('search');
            $baseQuery->whereHas('product', fn($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
        }
        $collection = (clone $baseQuery)->get();
        $lowStockQuery = $collection->filter(function ($stock) {
            $minimumLevel = $stock->product->minimum_stock_level ?? 10;
            return $stock->display_quantity > 0 && $stock->display_quantity <= $minimumLevel;
        });
        $stats = [
            'total_items' => $collection->count(),
            'total_quantity' => $collection->sum(fn ($s) => $s->display_quantity),
            'low_stock' => $lowStockQuery->count(),
            'out_of_stock' => $collection->filter(fn ($s) => $s->display_quantity === 0)->count(),
        ];

        $branches = $allowedBranchIds
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('branch-stocks.index', compact('stocks', 'stats', 'branches', 'products'));
    }

    public function create()
    {
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('branch-stocks.create', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $allowedBranchIds = $this->allowedBranchIds();
        $branchRule = 'required|exists:branches,id';
        if ($allowedBranchIds !== null) {
            $branchRule .= '|in:' . implode(',', $allowedBranchIds);
        }
        $validated = $request->validate([
            'branch_id' => $branchRule,
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // Get existing stock to calculate delta (service will update BranchStock when recording)
        $existingStock = BranchStock::where('branch_id', $validated['branch_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        $quantityBefore = $existingStock ? $existingStock->quantity : 0;
        $quantityChange = $validated['quantity'] - $quantityBefore;

        if ($quantityChange != 0) {
            InventoryMovementService::record(
                $validated['branch_id'],
                $validated['product_id'],
                $quantityChange > 0 ? 'receipt' : 'issue',
                $quantityChange,
                null,
                null,
                $quantityChange > 0 ? 'Stock added' : 'Stock reduced',
                'Manual stock update',
                auth()->id()
            );
        }

        return redirect()->route('branch-stocks.index')->with('success', 'Stock updated successfully.');
    }

    public function edit(BranchStock $branchStock)
    {
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($branchStock->branch_id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this branch stock.');
        }
        $branchStock->load(['branch', 'product']);
        return view('branch-stocks.edit', compact('branchStock'));
    }

    public function update(Request $request, BranchStock $branchStock)
    {
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($branchStock->branch_id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this branch stock.');
        }
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $quantityBefore = (int) $branchStock->quantity;
        $quantityChange = $validated['quantity'] - $quantityBefore;

        if ($quantityChange != 0) {
            InventoryMovementService::record(
                $branchStock->branch_id,
                $branchStock->product_id,
                $quantityChange > 0 ? 'receipt' : 'issue',
                $quantityChange,
                null,
                null,
                $quantityChange > 0 ? 'Stock increased' : 'Stock decreased',
                'Manual stock update',
                auth()->id()
            );
        }

        return redirect()->route('branch-stocks.index')->with('success', 'Stock updated successfully.');
    }
}
