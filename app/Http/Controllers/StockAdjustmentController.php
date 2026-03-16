<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockAdjustment;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Scope: user's branch and all descendant branches (or no restriction for admin)
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $query = StockAdjustment::with(['branch', 'product', 'stockTake', 'adjustedBy', 'approvedBy'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->latest();

        // Filter by branch (selected branch and its descendants)
        if ($request->filled('branch_id')) {
            $filterBranchIds = Branch::selfAndDescendantIds($request->branch_id);
            $query->whereIn('branch_id', $filterBranchIds);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by adjustment type
        if ($request->filled('adjustment_type')) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->paginate(15)->withQueryString();

        // Stats: same scope and filters as the list so cards match the filtered table
        $statsBaseQuery = StockAdjustment::query()
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $filterBranchIds = Branch::selfAndDescendantIds($request->branch_id);
                $q->whereIn('branch_id', $filterBranchIds);
            })
            ->when($request->filled('product_id'), fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('adjustment_type'), fn($q) => $q->where('adjustment_type', $request->adjustment_type))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to));
        $stats = [
            'total' => (clone $statsBaseQuery)->count(),
            'increases' => (clone $statsBaseQuery)->where('adjustment_amount', '>', 0)->count(),
            'decreases' => (clone $statsBaseQuery)->where('adjustment_amount', '<', 0)->count(),
            'from_stock_takes' => (clone $statsBaseQuery)->where('adjustment_type', 'stock_take')->count(),
            'from_sales' => (clone $statsBaseQuery)->where('adjustment_type', 'sale')->count(),
            'total_increase_amount' => (clone $statsBaseQuery)->where('adjustment_amount', '>', 0)->sum('adjustment_amount'),
            'total_decrease_amount' => abs((clone $statsBaseQuery)->where('adjustment_amount', '<', 0)->sum('adjustment_amount')),
        ];

        // Branches dropdown: user's branch and descendants (or all active for admin)
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock-adjustments.index', compact('adjustments', 'stats', 'branches', 'products'));
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        if ($allowedBranchIds !== null && !in_array($stockAdjustment->branch_id, $allowedBranchIds, true) && !$user->isAdmin()) {
            abort(403, 'You do not have access to this adjustment.');
        }

        $stockAdjustment->load(['branch', 'product', 'stockTake', 'adjustedBy', 'approvedBy']);

        return view('stock-adjustments.show', compact('stockAdjustment'));
    }
}
