<?php

namespace App\Http\Controllers;

use App\Models\StockTake;
use App\Models\StockTransfer;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Branch;
use App\Models\Region;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\CustomerDisbursement;
use App\Models\Ticket;
use App\Models\Device;
use App\Models\User;
use App\Models\Role;
use App\Models\FieldAgent;
use Illuminate\Support\Facades\Auth;

class HubController extends Controller
{
    protected function scopeTransfersByBranch($query)
    {
        $user = Auth::user();
        if ($user->branch_id && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('from_branch_id', $user->branch_id)
                    ->orWhere('to_branch_id', $user->branch_id);
            });
        }
        return $query;
    }

    /**
     * Stock Operations hub: Stock Takes, Adjustments, Transfers
     */
    public function stockOperations()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;

        $stockTakesQuery = StockTake::query();
        $adjustmentsQuery = StockAdjustment::query();
        $transfersQuery = StockTransfer::query();
        if ($isFieldAgent) {
            $stockTakesQuery->whereRaw('1 = 0');
            $adjustmentsQuery->whereRaw('1 = 0');
            $transfersQuery->whereRaw('1 = 0');
        } else {
            $stockTakesQuery->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
            $adjustmentsQuery->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
            $this->scopeTransfersByBranch($transfersQuery);
        }

        $stats = [
            'stock_takes_total' => (clone $stockTakesQuery)->count(),
            'stock_takes_pending' => (clone $stockTakesQuery)->whereIn('status', ['draft', 'in_progress', 'completed'])->count(),
            'adjustments_total' => (clone $adjustmentsQuery)->count(),
            'transfers_total' => (clone $transfersQuery)->count(),
            'transfers_pending' => (clone $transfersQuery)->whereIn('status', ['pending', 'in_transit'])->count(),
        ];

        $recentStockTakes = StockTake::with(['branch', 'creator']);
        $recentTransfers = StockTransfer::with(['fromBranch', 'toBranch', 'product']);
        if ($isFieldAgent) {
            $recentStockTakes->whereRaw('1 = 0');
            $recentTransfers->whereRaw('1 = 0');
        } else {
            $recentStockTakes->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
            $recentTransfers->when($user->branch_id && !$user->isAdmin(), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('from_branch_id', $user->branch_id)->orWhere('to_branch_id', $user->branch_id);
                });
            });
        }
        $recentStockTakes = $recentStockTakes->latest()->limit(5)->get();
        $recentTransfers = $recentTransfers->latest()->limit(5)->get();

        return view('hubs.stock-operations', compact('stats', 'recentStockTakes', 'recentTransfers'));
    }

    /**
     * Catalog hub: Products, Brands, Product Pricing, Devices (field agents: devices they distributed)
     */
    public function catalog()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent) {
            $devicesQuery = Device::query()->whereHas('saleItem', fn($q) => $q->where('field_agent_id', $user->id));
        } else {
            $devicesQuery = Device::query()->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $cutoff = now()->subDays(30);
        $overstayedQuery = Device::query()
            ->whereIn('status', ['available', 'assigned'])
            ->where('created_at', '<=', $cutoff);
        if ($isFieldAgent) {
            $overstayedQuery->where('branch_id', $user->branch_id);
        } elseif ($user->branch_id && !$user->isAdmin()) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            $overstayedQuery->whereIn('branch_id', $allowedBranchIds);
        }

        $stats = [
            'products_total' => Product::count(),
            'products_active' => Product::where('is_active', true)->count(),
            'brands_total' => Brand::count(),
            'brands_active' => Brand::where('is_active', true)->count(),
            'devices_total' => (clone $devicesQuery)->count(),
            'devices_overstayed' => (clone $overstayedQuery)->count(),
        ];

        $recentProducts = Product::with('brand')->latest()->limit(5)->get();
        $recentBrands = Brand::latest()->limit(5)->get();
        $recentOverstayedDevices = (clone $overstayedQuery)->with(['product', 'branch'])->orderBy('created_at')->limit(5)->get();

        return view('hubs.catalog', compact('stats', 'recentProducts', 'recentBrands', 'recentOverstayedDevices'));
    }

    /**
     * Locations hub: Branches, Regions (field agents see only their branch)
     */
    public function locations()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent && $user->branch_id) {
            $branchIds = [$user->branch_id];
            $stats = [
                'branches_total' => 1,
                'branches_active' => Branch::where('id', $user->branch_id)->where('is_active', true)->count(),
                'regions_total' => Region::count(),
                'regions_active' => Region::where('is_active', true)->count(),
            ];
            $recentBranches = Branch::with('region')->whereIn('id', $branchIds)->latest()->limit(5)->get();
        } else {
            $stats = [
                'branches_total' => Branch::count(),
                'branches_active' => Branch::where('is_active', true)->count(),
                'regions_total' => Region::count(),
                'regions_active' => Region::where('is_active', true)->count(),
            ];
            $recentBranches = Branch::with('region')->latest()->limit(5)->get();
        }
        $recentRegions = Region::latest()->limit(5)->get();

        return view('hubs.locations', compact('stats', 'recentBranches', 'recentRegions'));
    }

    /**
     * Sales & Transactions hub (field agents see only their sales)
     */
    public function salesTransactions()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent) {
            $salesQuery = Sale::query()->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id));
        } else {
            $salesQuery = Sale::query()->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $stats = [
            'sales_total' => (clone $salesQuery)->count(),
            'sales_completed' => (clone $salesQuery)->where('status', 'completed')->count(),
            'sales_today' => (clone $salesQuery)->whereDate('created_at', today())->count(),
        ];

        $recentSales = Sale::with(['customer', 'branch', 'items.product.regionPrices', 'customerDisbursements'])
            ->when($isFieldAgent, fn($q) => $q->whereHas('items', fn($q2) => $q2->where('field_agent_id', $user->id)))
            ->when(!$isFieldAgent && $user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest()
            ->limit(8)
            ->get();

        return view('hubs.sales-transactions', compact('stats', 'recentSales'));
    }

    /**
     * Customers hub: Customers, Customer Disbursements (field agents see only their customers)
     */
    public function customers()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent) {
            $customersQuery = Customer::query()->whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id));
            $stats = [
                'customers_total' => (clone $customersQuery)->count(),
                'customers_active' => (clone $customersQuery)->where('is_active', true)->count(),
                'disbursements_total' => CustomerDisbursement::count(),
                'disbursements_amount' => CustomerDisbursement::sum('amount'),
            ];
            $recentDisbursements = CustomerDisbursement::with(['customer', 'device'])->latest()->limit(6)->get();
        } else {
            $stats = [
                'customers_total' => Customer::count(),
                'customers_active' => Customer::where('is_active', true)->count(),
                'disbursements_total' => CustomerDisbursement::count(),
                'disbursements_amount' => CustomerDisbursement::sum('amount'),
            ];
            $recentDisbursements = CustomerDisbursement::with(['customer', 'device'])->latest()->limit(6)->get();
        }

        return view('hubs.customers', compact('stats', 'recentDisbursements'));
    }

    /**
     * Support hub: Tickets (field agents see only assigned/own tickets)
     */
    public function support()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent) {
            $ticketsQuery = Ticket::query()->where(function ($q) use ($user) {
                $q->where('field_agent_id', $user->id)->orWhere('assigned_to', $user->id);
            });
        } else {
            $ticketsQuery = Ticket::query()->when($user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $stats = [
            'tickets_total' => (clone $ticketsQuery)->count(),
            'tickets_open' => (clone $ticketsQuery)->whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $recentTickets = Ticket::with(['customer', 'branch'])
            ->when($isFieldAgent, fn($q) => $q->where(function ($q2) use ($user) {
                $q2->where('field_agent_id', $user->id)->orWhere('assigned_to', $user->id);
            }))
            ->when(!$isFieldAgent && $user->branch_id && !$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest()
            ->limit(6)
            ->get();

        return view('hubs.support', compact('stats', 'recentTickets'));
    }

    /**
     * Team/Access hub: Users, Roles, Field Agents (field agents see only themselves)
     */
    public function team()
    {
        $currentUser = auth()->user();
        $isFieldAgent = $currentUser->fieldAgentProfile && $currentUser->branch_id;
        if ($isFieldAgent) {
            $usersQuery = User::where('id', $currentUser->id);
            $fieldAgentUserIds = collect([$currentUser->id]);
        } else {
            $usersQuery = User::visibleTo($currentUser);
            $fieldAgentUserIds = User::visibleTo($currentUser)->pluck('id');
        }
        $stats = [
            'users_total' => $usersQuery->count(),
            'roles_total' => Role::count(),
            'roles_active' => Role::where('is_active', true)->count(),
            'field_agents_total' => FieldAgent::whereIn('user_id', $fieldAgentUserIds)->count(),
            'field_agents_active' => FieldAgent::whereIn('user_id', $fieldAgentUserIds)->where('is_active', true)->count(),
        ];

        $recentUsers = ($isFieldAgent ? User::where('id', $currentUser->id) : User::visibleTo($currentUser))->with('branch')->latest()->limit(5)->get();

        return view('hubs.team', compact('stats', 'recentUsers'));
    }
}
