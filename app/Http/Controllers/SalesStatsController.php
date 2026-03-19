<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class SalesStatsController extends Controller
{
    /**
     * Base sale query scoped by current user (same as SaleController::index).
     */
    protected function baseSaleQuery(Request $request = null): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchFilter = $request ? ($request->get('branch') ?: null) : null;
        if ($request && $request->has('branch') && $request->get('branch') === '') {
            $branchFilter = null;
        }
        if ($allowedBranchIds !== null && $branchFilter !== null && !in_array($branchFilter, $allowedBranchIds, true)) {
            $branchFilter = $user->branch_id;
        }

        $query = Sale::query();
        if ($isFieldAgent) {
            $query->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id));
            if ($branchFilter !== null) {
                $query->where('branch_id', $branchFilter);
            }
        } else {
            $query->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            if ($branchFilter !== null) {
                $query->where('branch_id', $branchFilter);
            }
        }
        return $query;
    }

    /**
     * Sales stats: best performing users (by sales initiated) and best performing products (by units/revenue).
     */
    public function index(Request $request)
    {
        $baseQuery = $this->baseSaleQuery($request);
        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $saleIds = (clone $completedQuery)->pluck('id');
        $saleIdsArray = $saleIds->all();
        $allSaleIds = (clone $baseQuery)->pluck('id');
        $userIds = (clone $completedQuery)->distinct()->pluck('sold_by')->filter()->values()->all();

        // Derive all summary stats from the same sale set used in tables so stats match table totals
        $licenseCost = Sale::whereIn('id', $saleIdsArray)->sum('total_license_cost');
        $totalBuyingPrice = Sale::totalBuyingPriceForSaleIds($saleIdsArray);
        $totalInSales = Sale::whereIn('id', $saleIdsArray)->sum('total');
        $stats = [
            'completed_sales' => count($saleIdsArray),
            'total_revenue' => $totalInSales,
            'total_cost_to_sell' => $totalBuyingPrice + $licenseCost,
            'total_profit' => $totalInSales - ($totalBuyingPrice + $licenseCost),
            'users_with_sales' => count($userIds),
            'products_sold' => SaleItem::whereIn('sale_id', $saleIdsArray)->sum('quantity'),
        ];

        // Best users by sales initiated (sold_by): count and total revenue
        $bestUsers = User::query()
            ->whereIn('id', $userIds)
            ->withCount(['sales as completed_sales_count' => fn($q) => $q->where('status', 'completed')->whereIn('id', $saleIds)])
            ->withSum(['sales as total_revenue' => fn($q) => $q->where('status', 'completed')->whereIn('id', $saleIds)], 'total')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        $salesByUser = Sale::with(['items.product.regionPrices', 'branch'])->whereIn('id', $saleIds)->get();
        $saleToUser = $salesByUser->pluck('sold_by', 'id');
        $buyingByUser = $salesByUser->groupBy('sold_by')->map(fn($sales) => $sales->sum('total_buying_price'));
        $licenseByUser = $salesByUser->groupBy('sold_by')->map(fn($s) => $s->sum('total_license_cost'));
        foreach ($bestUsers as $u) {
            $u->cost_to_sell = ($buyingByUser[$u->id] ?? 0) + ($licenseByUser[$u->id] ?? 0);
            $u->gross_profit = (float) ($u->total_revenue ?? 0) - $u->cost_to_sell;
        }

        // Best products by sale items (quantity and subtotal) from scoped sales
        $bestProducts = SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->select('product_id')
            ->selectRaw('count(*) as items_count, sum(quantity) as total_quantity, sum(subtotal) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get()
            ->load('product:id,name,sku');

        $salesForProducts = Sale::with(['items.product.regionPrices', 'branch'])->whereIn('id', $saleIds)->get()->keyBy('id');
        $costBySale = $salesForProducts->pluck('total_license_cost', 'id')->map(fn($v) => (float) ($v ?? 0));
        $productSaleIds = SaleItem::whereIn('sale_id', $saleIds)->with('product.regionPrices')->get()->groupBy('product_id')->map(fn($i) => $i->groupBy('sale_id'));
        foreach ($bestProducts as $row) {
            $itemsBySale = $productSaleIds[$row->product_id] ?? collect();
            $buyingCost = 0.0;
            foreach ($itemsBySale as $saleId => $items) {
                $sale = $salesForProducts[$saleId] ?? null;
                if ($sale) {
                    $regionId = $sale->branch?->region_id;
                    $item = $items->first();
                    $unitCost = $item && $item->relationLoaded('product') ? ($item->product?->costPriceForRegion($regionId) ?? 0) : 0;
                    $buyingCost += $unitCost * $items->sum('quantity');
                }
            }
            $licenseDisbForProduct = $itemsBySale->keys()->sum(fn($sid) => (float) ($costBySale[$sid] ?? 0));
            $row->cost_to_sell = $buyingCost + $licenseDisbForProduct;
            $row->gross_profit = (float) ($row->total_revenue ?? 0) - $row->cost_to_sell;
        }

        $branches = $this->getBranchesForFilter($request);
        $branchFilter = $request->get('branch');

        return view('sales-stats.index', compact('bestUsers', 'bestProducts', 'branches', 'branchFilter', 'stats'));
    }

    /**
     * Detail: sales performance for a specific user (sales they initiated).
     */
    public function userShow(Request $request, User $user)
    {
        $baseQuery = $this->baseSaleQuery($request)->where('sold_by', $user->id);
        $sales = (clone $baseQuery)
            ->with(['customer', 'branch'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $saleIds = (clone $baseQuery)->pluck('id');

        $totalRevenue = (clone $completedQuery)->sum('total');
        $saleIdsUser = (clone $completedQuery)->pluck('id')->all();
        $totalBuyingPrice = Sale::totalBuyingPriceForSaleIds($saleIdsUser);
        $licenseCost = (clone $completedQuery)->sum('total_license_cost');
        $costToSell = $totalBuyingPrice + $licenseCost;
        $stats = [
            'total_sales' => (clone $baseQuery)->count(),
            'completed_sales' => (clone $completedQuery)->count(),
            'total_revenue' => $totalRevenue,
            'total_cost_to_sell' => $costToSell,
            'total_profit' => $totalRevenue - $costToSell,
        ];

        return view('sales-stats.user-show', compact('user', 'sales', 'stats'));
    }

    /**
     * Detail: sales performance for a specific product (sale items).
     */
    public function productShow(Request $request, Product $product)
    {
        $baseQuery = $this->baseSaleQuery($request);
        $saleIds = (clone $baseQuery)->where('status', 'completed')->pluck('id')->all();

        $items = SaleItem::query()
            ->where('product_id', $product->id)
            ->whereIn('sale_id', $saleIds)
            ->with(['sale.customer', 'sale.branch', 'device'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $itemsBase = SaleItem::where('product_id', $product->id)->whereIn('sale_id', $saleIds);
        $totalRevenue = (clone $itemsBase)->sum('subtotal');
        $salesWithProduct = Sale::with(['items.product.regionPrices', 'branch'])->whereIn('id', $saleIds)->get()->keyBy('id');
        $buyingCostProduct = SaleItem::where('product_id', $product->id)->whereIn('sale_id', $saleIds)->with('product.regionPrices')->get()->sum(function ($item) use ($salesWithProduct) {
            $sale = $salesWithProduct[$item->sale_id] ?? null;
            $regionId = $sale?->branch?->region_id;
            $unitCost = $item->product?->costPriceForRegion($regionId) ?? 0;
            return $unitCost * $item->quantity;
        });
        $saleIdsWithProduct = array_unique(SaleItem::where('product_id', $product->id)->whereIn('sale_id', $saleIds)->pluck('sale_id')->all());
        $licenseDisb = Sale::whereIn('id', $saleIdsWithProduct)->sum('total_license_cost');
        $costToSell = $buyingCostProduct + $licenseDisb;
        $stats = [
            'total_quantity' => (clone $itemsBase)->sum('quantity'),
            'total_revenue' => $totalRevenue,
            'sales_count' => (clone $itemsBase)->selectRaw('count(distinct sale_id) as c')->value('c') ?? 0,
            'total_cost_to_sell' => $costToSell,
            'total_profit' => $totalRevenue - $costToSell,
        ];

        return view('sales-stats.product-show', compact('product', 'items', 'stats'));
    }

    protected function getBranchesForFilter(Request $request = null): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        return $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
