<?php

namespace App\Http\Controllers\Portal;

use App\Exports\Portal\PortalReportExport;
use App\Helpers\PeriodHelper;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalReportController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;
        $customerId = $profile->customer_id;

        $reportType = $request->get('type', 'sales_by_product');
        $period     = $request->get('period', 'this_month');
        [$start, $end] = PeriodHelper::getRange($period, $request->date_from, $request->date_to);

        $customerSaleIds = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->pluck('id');

        $reportData = match ($reportType) {
            'revenue_trend'     => $this->revenueTrend($customerId),
            'outlet_performance' => $this->outletPerformance($customerId, $start, $end),
            default             => $this->salesByProduct($customerSaleIds),
        };

        return view('portal.reports.index', compact('reportData', 'reportType', 'period', 'start', 'end', 'profile'));
    }

    public function export(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;
        $customerId = $profile->customer_id;

        $reportType = $request->get('type', 'sales_by_product');
        $period     = $request->get('period', 'this_month');
        [$start, $end] = PeriodHelper::getRange($period, $request->date_from, $request->date_to);

        $customerSaleIds = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->pluck('id');

        $data = match ($reportType) {
            'revenue_trend'      => $this->revenueTrend($customerId),
            'outlet_performance' => $this->outletPerformance($customerId, $start, $end),
            default              => $this->salesByProduct($customerSaleIds),
        };

        $filename = $reportType . '-' . now()->format('Y-m-d') . '.xlsx';
        return (new PortalReportExport($data, $reportType))->download($filename);
    }

    private function salesByProduct($customerSaleIds): array
    {
        if ($customerSaleIds->isEmpty()) {
            return ['headers' => ['Product', 'Brand', 'Qty Sold', 'Revenue'], 'rows' => []];
        }

        $rows = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->whereIn('sale_items.sale_id', $customerSaleIds)
            ->selectRaw('products.name as product, brands.name as brand, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name', 'brands.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                $r->product,
                $r->brand ?? '—',
                $r->qty,
                number_format($r->revenue, 2),
            ])
            ->toArray();

        return ['headers' => ['Product', 'Brand', 'Qty Sold', 'Revenue'], 'rows' => $rows];
    }

    private function revenueTrend(string $customerId): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $rev = Sale::where('customer_id', $customerId)
                ->secondarySales()
                ->where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total');
            $rows[] = [$month->format('M Y'), number_format($rev, 2)];
        }
        return ['headers' => ['Month', 'Revenue'], 'rows' => $rows];
    }

    private function outletPerformance(string $customerId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('sales')
            ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
            ->where('sales.customer_id', $customerId)
            ->where('sales.sale_type', 'secondary')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start, $end])
            ->whereNotNull('sales.outlet_id')
            ->selectRaw('outlets.name as outlet, outlets.type as type, COUNT(sales.id) as orders, SUM(sales.total) as revenue')
            ->groupBy('outlets.id', 'outlets.name', 'outlets.type')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                $r->outlet,
                ucfirst($r->type),
                $r->orders,
                number_format($r->revenue, 2),
            ])
            ->toArray();

        return ['headers' => ['Outlet', 'Type', 'Orders', 'Revenue'], 'rows' => $rows];
    }
}
