<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestockOrder;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Ticket;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\CustomerDisbursement;
use App\Models\FieldAgentStock;
use App\Models\AgentStockRequest;
use App\Models\PettyCashRequest;
use App\Models\CommissionDisbursement;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->load(['roleModel.permissions']);

        // Field agents get a dedicated agent dashboard
        if ($user->fieldAgentProfile && $user->branch_id) {
            return $this->agentDashboard($user);
        }

        $periodOptions = [
            'today' => 'Today',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'last_3_months' => 'Last 3 months',
            'this_year' => 'This year',
        ];
        $period = $request->input('period', 'this_month');
        if (!array_key_exists($period, $periodOptions)) {
            $period = 'this_month';
        }
        $periodLabel = $periodOptions[$period];
        [$periodStart, $periodEnd] = $this->getPeriodRange($period);

        $includeDescendants = filter_var($request->input('include_descendants', true), FILTER_VALIDATE_BOOLEAN);
        $branchHasDescendants = $user->branch_id && count(Branch::selfAndDescendantIds($user->branch_id)) > 1;
        $allowedBranchIds = $user->branch_id
            ? ($includeDescendants ? Branch::selfAndDescendantIds($user->branch_id) : [$user->branch_id])
            : null;

        // Permission flags: only compute and expose data the user is allowed to see
        $canViewStock = $user->hasPermission('inventory.view') || $user->hasPermission('branch-stocks.view');
        $canViewTransfers = $user->hasPermission('stock-transfers.view');
        $canViewTickets = $user->hasPermission('tickets.view');
        $canViewProducts = $user->hasPermission('products.view');
        $canViewBranches = $user->hasPermission('branches.view');
        $canViewSales = $user->hasPermission('sales.view');
        $canViewDevices = false; // device model removed
        $canViewStockManagement = $user->hasPermission('stock-management.view');
        $canViewPettyCash = $user->hasPermission('petty-cash.view');
        $canViewBills = $user->hasPermission('bills.view');
        $canViewCustomerDisbursements = $user->hasPermission('customer-disbursements.view');
        $canViewCommissions = $user->hasPermission('commission-disbursements.view');
        $canAccessRestockWizard = $user->isAdmin() || $user->hasPermission('stock-management.restock') || $user->hasPermission('stock-management.initiate-restock');

        // Default stats (zeros) so view never misses a key
        $stats = [
            'total_products' => 0,
            'total_branches' => 0,
            'total_stock_quantity' => 0,
            'total_available_stock' => 0,
            'total_reserved_stock' => 0,
            'low_stock_items' => 0,
            'out_of_stock_items' => 0,
            'pending_transfers' => 0,
            'completed_transfers' => 0,
            'open_tickets' => 0,
            'total_tickets' => 0,
            'in_progress_tickets' => 0,
            'resolved_tickets' => 0,
            'overdue_tickets' => 0,
            'urgent_tickets' => 0,
            'total_devices' => 0,
            'available_devices' => 0,
            'total_sales' => 0,
            'sales_this_month' => 0,
            'sales_completed' => 0,
            'sales_pending' => 0,
            'sales_today' => 0,
            'total_revenue' => 0,
            'revenue_this_month' => 0,
            'revenue_today' => 0,
            'sales_in_period' => 0,
            'revenue_in_period' => 0,
            'sales_completed_in_period' => 0,
            'sales_pending_in_period' => 0,
            'total_support' => 0,
            'support_in_period' => 0,
            'total_commissions_paid' => 0,
            'commissions_paid_in_period' => 0,
            'total_commission' => 0,
            'commission_in_period' => 0,
        ];

        $branchStockLabels = [];
        $branchStockData = [];
        $brandStockLabels = [];
        $brandStockData = [];
        $stockMovementsLabels = [];
        $stockMovementsData = [];
        $transfersByStatus = [];
        $topStockProducts = collect();
        $lowStockProducts = collect();
        $stockStatusDistribution = ['low' => 0, 'medium' => 0, 'high' => 0];
        $monthlyStockLabels = [];
        $monthlyStockMovements = [];
        $recent_transfers = collect();
        $recent_stock_updates = collect();
        $ticketsByStatus = [];
        $ticketsByPriority = [];
        $recent_tickets = collect();
        $recent_sales = collect();
        $recent_restock_orders = collect();
        $stocks_by_dealership = collect();
        $recent_devices = collect();
        $salesByStatus = [];
        $monthlySalesLabels = [];
        $monthlySalesData = [];
        $monthlyRevenueData = [];
        $topPerformingUsers = collect();
        $topPerformingDevices = collect();

        if ($canViewSales) {
            $salesQuery = $allowedBranchIds !== null
                ? Sale::whereIn('branch_id', $allowedBranchIds)
                : Sale::query();
            $stats['total_sales'] = (clone $salesQuery)->count();
            $stats['sales_completed'] = (clone $salesQuery)->where('status', 'completed')->count();
            $stats['sales_pending'] = (clone $salesQuery)->where('status', 'pending')->count();
            $salesInPeriodQuery = (clone $salesQuery)->whereBetween('created_at', [$periodStart, $periodEnd]);
            $stats['sales_in_period'] = (clone $salesInPeriodQuery)->count();
            $stats['sales_completed_in_period'] = (clone $salesInPeriodQuery)->where('status', 'completed')->count();
            $stats['sales_pending_in_period'] = (clone $salesInPeriodQuery)->where('status', 'pending')->count();
            $stats['sales_this_month'] = (clone $salesQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $stats['sales_today'] = (clone $salesQuery)->whereDate('created_at', today())->count();
            $completedQuery = (clone $salesQuery)->where('status', 'completed');
            $stats['total_revenue'] = (clone $completedQuery)->sum('total');
            $completedIds = (clone $completedQuery)->pluck('id')->all();
            $licenseCost = (clone $completedQuery)->sum('total_license_cost');
            $disbursementCost = CustomerDisbursement::whereIn('sale_id', $completedIds)->sum('amount');
            $stats['total_support'] = $disbursementCost;
            $totalBuyingPrice = Sale::totalBuyingPriceForSaleIds($completedIds);
            $stats['total_commission'] = SaleItem::whereIn('sale_id', $completedIds)->sum('commission_amount');
            $totalPettyCashDisbursed = PettyCashRequest::query()
                ->where('status', PettyCashRequest::STATUS_DISBURSED)
                ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)))
                ->sum('amount');
            $totalBillsPaid = $canViewBills
                ? Bill::query()
                    ->when($allowedBranchIds !== null, fn($q) => $q->where(function ($q) use ($allowedBranchIds) {
                        $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                    }))
                    ->paid()
                    ->sum('amount')
                : 0;
            $stats['total_cost_to_sell'] = $totalBuyingPrice + $licenseCost + $stats['total_commission'] + $disbursementCost + $totalPettyCashDisbursed + $totalBillsPaid;
            $stats['total_profit'] = $stats['total_revenue'] - $stats['total_cost_to_sell'];
            $revenueInPeriodQuery = (clone $completedQuery)->whereBetween('created_at', [$periodStart, $periodEnd]);
            $stats['revenue_in_period'] = (clone $revenueInPeriodQuery)->sum('total');
            $periodIds = (clone $revenueInPeriodQuery)->pluck('id')->all();
            $supportInPeriod = CustomerDisbursement::whereIn('sale_id', $periodIds)->sum('amount');
            $stats['support_in_period'] = $supportInPeriod;
            $buyingPriceInPeriod = Sale::totalBuyingPriceForSaleIds($periodIds);
            $stats['commission_in_period'] = SaleItem::whereIn('sale_id', $periodIds)->sum('commission_amount');
            $costToSellInPeriod = $buyingPriceInPeriod + (clone $revenueInPeriodQuery)->sum('total_license_cost') + $stats['commission_in_period'] + $supportInPeriod;
            $pettyCashInPeriod = PettyCashRequest::query()
                ->where('status', PettyCashRequest::STATUS_DISBURSED)
                ->whereBetween('disbursed_at', [$periodStart, $periodEnd])
                ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)))
                ->sum('amount');
            $stats['petty_cash_expenses_in_period'] = $pettyCashInPeriod;
            $billsPaidInPeriod = 0;
            if ($canViewBills) {
                $billsPaidInPeriod = Bill::query()
                    ->when($allowedBranchIds !== null, fn($q) => $q->where(function ($q) use ($allowedBranchIds) {
                        $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                    }))
                    ->paid()
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->sum('amount');
            }
            $stats['bills_paid_in_period'] = $billsPaidInPeriod;
            $stats['cost_to_sell_in_period'] = $costToSellInPeriod + $pettyCashInPeriod + $billsPaidInPeriod;
            $stats['profit_in_period'] = $stats['revenue_in_period'] - $stats['cost_to_sell_in_period'];
            $stats['revenue_this_month'] = (clone $completedQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');
            $stats['revenue_today'] = (clone $completedQuery)->whereDate('created_at', today())->sum('total');
            $commissionsQuery = CommissionDisbursement::query()
                ->where('status', 'completed')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('user', fn($f) => $f->where(function ($q) use ($allowedBranchIds) {
                    $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                })));
            $stats['total_commissions_paid'] = (clone $commissionsQuery)->sum('amount');
            $stats['commissions_paid_in_period'] = (clone $commissionsQuery)->whereBetween('processed_at', [$periodStart, $periodEnd])->sum('amount');
            $salesByStatus = (clone $salesQuery)->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthlySalesLabels[] = $month->format('M Y');
                $monthlySalesData[] = (clone $salesQuery)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();
                $monthlyRevenueData[] = (clone $salesQuery)
                    ->where('status', 'completed')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('total');
            }
            $recent_sales = Sale::with(['customer', 'soldBy', 'branch', 'items.product.regionPrices', 'customerDisbursements'])
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->latest()
                ->limit(10)
                ->get();

            $completedInPeriod = (clone $salesQuery)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$periodStart, $periodEnd]);
            $saleIdsInPeriod = (clone $completedInPeriod)->pluck('id');

            $topPerformingUsers = User::query()
                ->whereIn('id', (clone $completedInPeriod)->distinct()->pluck('sold_by')->filter()->values())
                ->withCount(['sales as sales_count' => fn($q) => $q->where('status', 'completed')->whereBetween('created_at', [$periodStart, $periodEnd])->when($allowedBranchIds !== null, fn($q2) => $q2->whereIn('branch_id', $allowedBranchIds))])
                ->withSum(['sales as revenue' => fn($q) => $q->where('status', 'completed')->whereBetween('created_at', [$periodStart, $periodEnd])->when($allowedBranchIds !== null, fn($q2) => $q2->whereIn('branch_id', $allowedBranchIds))], 'total')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get(['id', 'name']);

            $topPerformingDevices = SaleItem::query()
                ->whereIn('sale_id', $saleIdsInPeriod)
                ->select('product_id')
                ->selectRaw('count(*) as items_sold, sum(subtotal) as revenue')
                ->groupBy('product_id')
                ->orderByDesc('items_sold')
                ->limit(5)
                ->get()
                ->load('product:id,name,sku');
        }

        if ($canViewStockManagement) {
            $recent_restock_orders = RestockOrder::with(['branch', 'product', 'creator'])
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->latest('created_at')
                ->limit(10)
                ->get();

            $stocks_by_dealership = RestockOrder::query()
                ->leftJoin('dealerships', 'restock_orders.dealership_id', '=', 'dealerships.id')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('restock_orders.branch_id', $allowedBranchIds))
                ->select(
                    DB::raw("COALESCE(dealerships.name, restock_orders.dealership_name, '—') as dealership_label"),
                    DB::raw('COALESCE(SUM(restock_orders.quantity_received), 0) as quantity_received'),
                    DB::raw('COUNT(*) as order_count')
                )
                ->groupBy(DB::raw("COALESCE(dealerships.name, restock_orders.dealership_name, '—')"))
                ->orderByDesc('quantity_received')
                ->get();
        }

        if ($canViewProducts) {
            $stats['total_products'] = $allowedBranchIds
                ? (int) BranchStock::whereIn('branch_id', $allowedBranchIds)->selectRaw('COUNT(DISTINCT product_id) as c')->value('c')
                : Product::count();
        }
        if ($canViewBranches) {
            $stats['total_branches'] = $allowedBranchIds ? count($allowedBranchIds) : Branch::count();
        }

        if ($canViewStock) {
            $baseStockQuery = BranchStock::when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            $stats['total_stock_quantity'] = (clone $baseStockQuery)->get()->sum(fn($s) => $s->display_quantity);
            $stats['total_available_stock'] = (clone $baseStockQuery)->get()->sum(fn($stock) => $stock->available_quantity);
            $stats['total_reserved_stock'] = (clone $baseStockQuery)->sum('reserved_quantity');
            $stockCollection = (clone $baseStockQuery)->with('product')->get();
            $stats['low_stock_items'] = $stockCollection->filter(fn($s) => $s->display_quantity > 0 && $s->display_quantity <= ($s->product->minimum_stock_level ?? 10))->count();
            $stats['out_of_stock_items'] = $stockCollection->filter(fn($s) => $s->display_quantity === 0)->count();

            $stockByBranch = BranchStock::select('branches.name', DB::raw('SUM(GREATEST(0, branch_stocks.quantity)) as total_quantity'))
                ->join('branches', 'branch_stocks.branch_id', '=', 'branches.id')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                ->groupBy('branches.id', 'branches.name')
                ->orderBy('total_quantity', 'desc')
                ->get();
            $branchStockLabels = $stockByBranch->pluck('name')->toArray();
            $branchStockData = $stockByBranch->pluck('total_quantity')->toArray();

            $stockByBrand = BranchStock::select(DB::raw('COALESCE(brands.name, \'No brand\') as brand_name'), DB::raw('SUM(GREATEST(0, branch_stocks.quantity)) as total_quantity'))
                ->join('products', 'branch_stocks.product_id', '=', 'products.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                ->groupBy(DB::raw('products.brand_id'), DB::raw('COALESCE(brands.name, \'No brand\')'))
                ->orderBy('total_quantity', 'desc')
                ->get();
            $brandStockLabels = $stockByBrand->pluck('brand_name')->toArray();
            $brandStockData = $stockByBrand->pluck('total_quantity')->toArray();

            $topStockProducts = BranchStock::select('products.name', DB::raw('SUM(GREATEST(0, branch_stocks.quantity)) as total_stock'))
                ->join('products', 'branch_stocks.product_id', '=', 'products.id')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_stock', 'desc')
                ->limit(5)
                ->get();

            $lowStockProducts = BranchStock::select('products.name', 'products.minimum_stock_level', 'branch_stocks.quantity', 'branches.name as branch_name')
                ->join('products', 'branch_stocks.product_id', '=', 'products.id')
                ->join('branches', 'branch_stocks.branch_id', '=', 'branches.id')
                ->whereRaw('GREATEST(0, branch_stocks.quantity) > 0 AND GREATEST(0, branch_stocks.quantity) <= COALESCE(products.minimum_stock_level, 10)')
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                ->orderBy('branch_stocks.quantity', 'asc')
                ->limit(10)
                ->get();

            $stockStatusDistribution = [
                'low' => BranchStock::join('products', 'branch_stocks.product_id', '=', 'products.id')
                    ->whereColumn('branch_stocks.quantity', '<=', 'products.minimum_stock_level')
                    ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                    ->count(),
                'medium' => BranchStock::join('products', 'branch_stocks.product_id', '=', 'products.id')
                    ->whereColumn('branch_stocks.quantity', '>', 'products.minimum_stock_level')
                    ->whereColumn('branch_stocks.quantity', '<=', DB::raw('products.minimum_stock_level * 2'))
                    ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                    ->count(),
                'high' => BranchStock::join('products', 'branch_stocks.product_id', '=', 'products.id')
                    ->whereColumn('branch_stocks.quantity', '>', DB::raw('products.minimum_stock_level * 2'))
                    ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_stocks.branch_id', $allowedBranchIds))
                    ->count(),
            ];

            $recent_stock_updates = BranchStock::with(['product', 'branch'])
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->whereHas('product')
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($canViewTransfers) {
            $transferScope = fn($q) => $q->when($allowedBranchIds !== null, fn($q2) => $q2->where(function ($query) use ($allowedBranchIds) {
                $query->whereIn('from_branch_id', $allowedBranchIds)->orWhereIn('to_branch_id', $allowedBranchIds);
            }));
            $stats['pending_transfers'] = StockTransfer::where('status', 'pending')->when($allowedBranchIds !== null, $transferScope)->count();
            $stats['completed_transfers'] = StockTransfer::where('status', 'received')
                ->when($allowedBranchIds !== null, $transferScope)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $stockMovementsLabels[] = $date->format('M d');
                $dayTransfers = StockTransfer::whereDate('created_at', $date)->when($allowedBranchIds !== null, $transferScope)->sum('quantity');
                $stockMovementsData[] = $dayTransfers;
            }

            $transfersByStatus = StockTransfer::query()->when($allowedBranchIds !== null, $transferScope)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthlyStockLabels[] = $month->format('M Y');
                $monthTransfers = StockTransfer::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->when($allowedBranchIds !== null, $transferScope)
                    ->sum('quantity');
                $monthlyStockMovements[] = $monthTransfers;
            }

            $recent_transfers = StockTransfer::with(['fromBranch', 'toBranch', 'product'])
                ->when($allowedBranchIds !== null, $transferScope)
                ->latest()
                ->limit(10)
                ->get();
        }

        if ($canViewTickets) {
            $ticketBaseQuery = Ticket::query()
                ->when($user->isCustomer(), fn($q) => $q->where('customer_id', $user->id))
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            $stats['open_tickets'] = (clone $ticketBaseQuery)->where('status', '!=', 'closed')->count();
            $stats['total_tickets'] = (clone $ticketBaseQuery)->count();
            $stats['in_progress_tickets'] = (clone $ticketBaseQuery)->where('status', 'in_progress')->count();
            $stats['resolved_tickets'] = (clone $ticketBaseQuery)->where('status', 'resolved')->count();
            $stats['overdue_tickets'] = (clone $ticketBaseQuery)->where('due_date', '<', now())->whereNotIn('status', ['resolved', 'closed'])->count();
            $stats['urgent_tickets'] = (clone $ticketBaseQuery)->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count();

            $ticketsByStatus = (clone $ticketBaseQuery)->select('status', DB::raw('count(*) as count'))->groupBy('status')->get()->pluck('count', 'status')->toArray();
            $ticketsByPriority = (clone $ticketBaseQuery)->whereNotIn('status', ['resolved', 'closed'])->select('priority', DB::raw('count(*) as count'))->groupBy('priority')->get()->pluck('count', 'priority')->toArray();
            $recent_tickets = Ticket::with(['customer', 'assignedTo'])
                ->when($user->isCustomer(), fn($q) => $q->where('customer_id', $user->id))
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->latest()
                ->limit(10)
                ->get();
        }

        $pettyCashStats = [
            'requests_in_period' => 0,
            'disbursed_in_period_count' => 0,
            'disbursed_in_period_amount' => 0,
        ];
        if ($canViewPettyCash) {
            $pcQuery = PettyCashRequest::query()
                ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)));
            $pettyCashStats['requests_in_period'] = (clone $pcQuery)->whereBetween('created_at', [$periodStart, $periodEnd])->count();
            $disbursedInPeriod = (clone $pcQuery)->where('status', PettyCashRequest::STATUS_DISBURSED)
                ->whereBetween('disbursed_at', [$periodStart, $periodEnd]);
            $pettyCashStats['disbursed_in_period_count'] = (clone $disbursedInPeriod)->count();
            $pettyCashStats['disbursed_in_period_amount'] = (clone $disbursedInPeriod)->sum('amount');
        }

        $billsStats = [
            'total_unpaid' => 0,
            'due_this_week' => 0,
            'overdue' => 0,
            'paid_this_month' => 0,
        ];
        if ($canViewBills) {
            $billsQuery = Bill::query()
                ->when($allowedBranchIds !== null, fn($q) => $q->where(function ($q) use ($allowedBranchIds) {
                    $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                }));
            $billsStats['total_unpaid'] = (clone $billsQuery)->unpaid()->sum('amount');
            $billsStats['due_this_week'] = (clone $billsQuery)->unpaid()
                ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
            $billsStats['overdue'] = (clone $billsQuery)->overdue()->count();
            $billsStats['paid_this_month'] = (clone $billsQuery)->paid()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');
        }

        return view('dashboard.index', compact(
            'period',
            'periodLabel',
            'periodOptions',
            'includeDescendants',
            'branchHasDescendants',
            'stats',
            'recent_transfers',
            'recent_stock_updates',
            'recent_sales',
            'recent_restock_orders',
            'stocks_by_dealership',
            'recent_devices',
            'salesByStatus',
            'monthlySalesLabels',
            'monthlySalesData',
            'monthlyRevenueData',
            'topPerformingUsers',
            'topPerformingDevices',
            'branchStockLabels',
            'branchStockData',
            'brandStockLabels',
            'brandStockData',
            'stockMovementsLabels',
            'stockMovementsData',
            'transfersByStatus',
            'topStockProducts',
            'lowStockProducts',
            'stockStatusDistribution',
            'monthlyStockLabels',
            'monthlyStockMovements',
            'ticketsByStatus',
            'ticketsByPriority',
            'recent_tickets',
            'canViewStock',
            'canViewTransfers',
            'canViewTickets',
            'canViewProducts',
            'canViewBranches',
            'canViewSales',
            'canViewDevices',
            'canViewStockManagement',
            'canViewPettyCash',
            'pettyCashStats',
            'canViewBills',
            'billsStats',
            'canViewCustomerDisbursements',
            'canViewCommissions',
            'canAccessRestockWizard'
        ));
    }

    /**
     * Return [start, end] Carbon instances for the given period (inclusive of the day).
     */
    protected function getPeriodRange(string $period): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $endOfToday = $now->copy()->endOfDay();

        return match ($period) {
            'today' => [$today, $endOfToday],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            'last_3_months' => [
                $now->copy()->subMonths(2)->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Dashboard view for field agents: allocations, stock requests, quick actions.
     */
    protected function agentDashboard($user)
    {
        $allocations = FieldAgentStock::with(['product', 'branch'])
            ->where('field_agent_id', $user->id)
            ->orderBy('quantity', 'asc')
            ->get();

        $totalAllocated = $allocations->sum('quantity');
        $productCount = $allocations->where('quantity', '>', 0)->count();
        $lowStockCount = $allocations->filter(fn($a) => $a->isLowStock() && $a->quantity > 0)->count();
        $outOfStockCount = $allocations->where('quantity', 0)->count();

        $requestPending = AgentStockRequest::where('field_agent_id', $user->id)
            ->whereIn('status', ['pending', 'partially_fulfilled'])
            ->whereNull('closed_at')
            ->count();
        $requestApproved = AgentStockRequest::where('field_agent_id', $user->id)->where('status', 'approved')->count();
        $requestRejected = AgentStockRequest::where('field_agent_id', $user->id)->where('status', 'rejected')->count();

        $salesThisMonth = SaleItem::where('field_agent_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $recentRequests = AgentStockRequest::with(['product', 'branch'])
            ->where('field_agent_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $branch = $user->branch_id ? Branch::find($user->branch_id) : null;
        $canViewCommissions = $user->hasPermission('customer-disbursements.view');

        return view('dashboard.agent', compact(
            'allocations',
            'totalAllocated',
            'productCount',
            'lowStockCount',
            'outOfStockCount',
            'requestPending',
            'requestApproved',
            'requestRejected',
            'salesThisMonth',
            'recentRequests',
            'branch',
            'canViewCommissions'
        ));
    }
}
