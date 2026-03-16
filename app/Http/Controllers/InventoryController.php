<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BranchStock;
use App\Models\InventoryMovement;
use App\Models\InventoryAlert;
use App\Models\StockTake;
use App\Models\StockTransfer;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /** Branch IDs the current user can see (self + descendants). Null = global (all branches). */
    protected function allowedBranchIds(): ?array
    {
        $user = Auth::user();
        return $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    public function dashboard()
    {
        $user = Auth::user();
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.index');
        }
        $allowedBranchIds = $this->allowedBranchIds();

        // Current stock levels
        $stockQuery = BranchStock::with(['branch', 'product'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('quantity', '>', 0);

        $totalStockValue = 0; // Can be calculated if product prices are available
        $totalItems = (clone $stockQuery)->count();
        $stockCollection = (clone $stockQuery)->get();
        $totalQuantity = $stockCollection->sum(fn($s) => $s->display_quantity);

        // Low stock items (display quantity > 0 and <= minimum)
        $lowStockItems = $stockCollection->filter(function ($stock) {
            $minimumLevel = $stock->product->minimum_stock_level ?? 10;
            return $stock->display_quantity > 0 && $stock->display_quantity <= $minimumLevel;
        });

        // Out of stock items (display quantity 0, i.e. quantity <= 0)
        $outOfStockQuery = BranchStock::with(['branch', 'product'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('quantity', '<=', 0);
        $outOfStockItems = (clone $outOfStockQuery)->count();

        // Recent movements
        $recentMovements = InventoryMovement::with(['branch', 'product', 'creator'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->latest()
            ->limit(10)
            ->get();

        // Pending stock takes
        $pendingStockTakes = StockTake::with(['branch', 'creator'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->whereIn('status', ['draft', 'in_progress', 'completed'])
            ->latest()
            ->limit(5)
            ->get();

        // Pending stock transfers
        $pendingTransfers = StockTransfer::with(['fromBranch', 'toBranch', 'product'])
            ->when($allowedBranchIds !== null, function ($q) use ($allowedBranchIds) {
                $q->where(function ($query) use ($allowedBranchIds) {
                    $query->whereIn('from_branch_id', $allowedBranchIds)
                        ->orWhereIn('to_branch_id', $allowedBranchIds);
                });
            })
            ->whereIn('status', ['pending', 'in_transit'])
            ->latest()
            ->limit(5)
            ->get();

        // Movement statistics (last 30 days)
        $movementStats = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('created_at', '>=', now()->subDays(30))
            ->select('movement_type', DB::raw('SUM(ABS(quantity)) as total_quantity'), DB::raw('COUNT(*) as count'))
            ->groupBy('movement_type')
            ->get()
            ->keyBy('movement_type');

        // Daily movements for last 7 days (for chart)
        $dailyMovements = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $movementChartLabels = $dailyMovements->pluck('date')->map(fn($date) => date('M d', strtotime($date)))->toArray();
        $movementChartData = $dailyMovements->pluck('count')->toArray();

        // Movement types distribution
        $movementTypes = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('created_at', '>=', now()->subDays(30))
            ->select('movement_type', DB::raw('COUNT(*) as count'))
            ->groupBy('movement_type')
            ->get();

        $movementTypeLabels = $movementTypes->pluck('movement_type')->map(fn($type) => ucfirst(str_replace('_', ' ', $type)))->toArray();
        $movementTypeData = $movementTypes->pluck('count')->toArray();

        // Top products by movement (last 30 days)
        $topProductsByMovement = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('created_at', '>=', now()->subDays(30))
            ->with('product')
            ->select('product_id', DB::raw('COUNT(*) as movement_count'), DB::raw('SUM(ABS(quantity)) as total_quantity'))
            ->groupBy('product_id')
            ->orderByDesc('movement_count')
            ->limit(5)
            ->get();

        // Stock value calculation (if products have prices)
        $totalStockValue = BranchStock::with('product')
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('quantity', '>', 0)
            ->get()
            ->sum(function ($stock) {
                // Try to get price from product's region prices or default price
                $price = $stock->product->regionPrices->first()?->selling_price ?? $stock->product->selling_price ?? 0;
                return $stock->quantity * $price;
            });

        // Today's movements
        $todayMovements = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->whereDate('created_at', today())
            ->count();

        // This week vs last week comparison
        $thisWeekMovements = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $lastWeekMovements = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();

        $movementTrend = $lastWeekMovements > 0
            ? round((($thisWeekMovements - $lastWeekMovements) / $lastWeekMovements) * 100, 1)
            : ($thisWeekMovements > 0 ? 100 : 0);

        // Active alerts
        $activeAlerts = InventoryAlert::with(['branch', 'product'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('is_resolved', false)
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'total_stock_value' => $totalStockValue,
            'low_stock_count' => $lowStockItems->count(),
            'out_of_stock_count' => $outOfStockItems,
            'pending_stock_takes' => $pendingStockTakes->count(),
            'pending_transfers' => $pendingTransfers->count(),
            'active_alerts' => $activeAlerts->count(),
            'today_movements' => $todayMovements,
            'movement_trend' => $movementTrend,
        ];

        return view('inventory.dashboard', compact(
            'stats',
            'lowStockItems',
            'recentMovements',
            'pendingStockTakes',
            'pendingTransfers',
            'movementStats',
            'activeAlerts',
            'movementChartLabels',
            'movementChartData',
            'movementTypeLabels',
            'movementTypeData',
            'topProductsByMovement'
        ));
    }

    public function movements(Request $request)
    {
        $user = Auth::user();
        $allowedBranchIds = $this->allowedBranchIds();

        $query = InventoryMovement::with(['branch', 'product', 'creator', 'reference'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->latest();

        // Filters
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('movement_type') && $request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(20)->withQueryString();

        // Stats
        $statsQuery = InventoryMovement::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'increases' => (clone $statsQuery)->where('quantity', '>', 0)->count(),
            'decreases' => (clone $statsQuery)->where('quantity', '<', 0)->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', today())->count(),
        ];

        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('inventory.movements', compact('movements', 'stats', 'branches', 'products'));
    }

    public function alerts(Request $request)
    {
        $user = Auth::user();
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.index');
        }
        $allowedBranchIds = $this->allowedBranchIds();

        $query = InventoryAlert::with(['branch', 'product', 'resolver'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));

        // Filter by resolved status
        if ($request->has('resolved')) {
            $query->where('is_resolved', $request->boolean('resolved'));
        } else {
            $query->where('is_resolved', false); // Default to unresolved
        }

        // Filter by alert type
        if ($request->has('alert_type') && $request->alert_type) {
            $query->where('alert_type', $request->alert_type);
        }

        $alerts = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $statsQuery = InventoryAlert::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'unresolved' => (clone $statsQuery)->where('is_resolved', false)->count(),
            'low_stock' => (clone $statsQuery)->where('alert_type', 'low_stock')->where('is_resolved', false)->count(),
            'out_of_stock' => (clone $statsQuery)->where('alert_type', 'out_of_stock')->where('is_resolved', false)->count(),
        ];

        return view('inventory.alerts', compact('alerts', 'stats'));
    }

    public function resolveAlert(InventoryAlert $alert)
    {
        $user = Auth::user();
        $allowedBranchIds = $this->allowedBranchIds();

        if ($allowedBranchIds !== null && !in_array($alert->branch_id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this alert.');
        }

        $alert->resolve();

        return back()->with('success', 'Alert resolved successfully.');
    }

    public function stockHistory(Request $request)
    {
        $user = Auth::user();
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.index');
        }

        // Get filter parameters
        $branchId = $request->get('branch_id');
        $productId = $request->get('product_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set default dates if not provided (last 30 days)
        if (!$dateFrom) {
            $dateFrom = now()->subDays(30)->format('Y-m-d');
        }
        if (!$dateTo) {
            $dateTo = now()->format('Y-m-d');
        }

        // Build query for movements
        $movementsQuery = InventoryMovement::with(['branch', 'product', 'creator'])
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('created_at', 'desc');

        $movements = $movementsQuery->paginate(50)->withQueryString();

        // Get current stock levels for selected product/branch
        $currentStocks = BranchStock::with(['branch', 'product'])
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->where('quantity', '>', 0)
            ->get();

        // Get stock history for selected product/branch combination
        $stockHistory = [];
        if ($productId && $branchId) {
            // Get all movements for this product/branch combination
            $productMovements = InventoryMovement::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->orderBy('created_at', 'asc')
                ->get();

            // Build history timeline using stored quantity_before and quantity_after
            foreach ($productMovements as $movement) {
                $stockHistory[] = [
                    'date' => $movement->created_at,
                    'stock_level' => $movement->quantity_after,
                    'stock_before' => $movement->quantity_before,
                    'movement' => $movement,
                    'change' => $movement->quantity,
                ];
            }

            // Add current stock as the latest entry if we have movements
            $currentStock = BranchStock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();

            if ($currentStock && (count($stockHistory) == 0 || $stockHistory[count($stockHistory) - 1]['stock_level'] != $currentStock->quantity)) {
                $stockHistory[] = [
                    'date' => now(),
                    'stock_level' => $currentStock->quantity,
                    'stock_before' => $currentStock->quantity,
                    'movement' => null,
                    'change' => 0,
                ];
            }
        }

        // Get branches and products for filters
        $branches = Branch::where('is_active', true)
            ->when($user->branch_id, fn($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Statistics
        $stats = [
            'total_movements' => $movements->total(),
            'total_increases' => (clone $movementsQuery)->where('quantity', '>', 0)->count(),
            'total_decreases' => (clone $movementsQuery)->where('quantity', '<', 0)->count(),
            'net_change' => (clone $movementsQuery)->sum('quantity'),
        ];

        return view('inventory.stock-history', compact(
            'movements',
            'currentStocks',
            'stockHistory',
            'branches',
            'products',
            'stats',
            'branchId',
            'productId',
            'dateFrom',
            'dateTo'
        ));
    }
}
