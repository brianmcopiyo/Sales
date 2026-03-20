<?php

namespace App\Http\Controllers\Portal;

use App\Exports\Portal\PortalSalesExport;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class PortalOrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;
        $customerId = $profile->customer_id;

        $query = Sale::with(['items.product', 'outlet', 'schemes'])
            ->where('customer_id', $customerId)
            ->secondarySales()
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('sale_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total'     => Sale::where('customer_id', $customerId)->secondarySales()->count(),
            'completed' => Sale::where('customer_id', $customerId)->secondarySales()->where('status', 'completed')->count(),
            'revenue'   => Sale::where('customer_id', $customerId)->secondarySales()->where('status', 'completed')->sum('total'),
        ];

        return view('portal.orders.index', compact('orders', 'stats', 'profile'));
    }

    public function show(Sale $sale)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        abort_if($sale->customer_id !== $profile->customer_id, 403, 'Access denied.');

        $sale->load(['items.product.brand', 'schemes', 'outlet', 'branch']);

        return view('portal.orders.show', compact('sale', 'profile'));
    }

    public function export(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        $export = new PortalSalesExport(
            customerId: $profile->customer_id,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            status: $request->status,
            search: $request->search,
        );

        $filename = 'orders-' . now()->format('Y-m-d') . '.xlsx';
        return $export->download($filename);
    }
}
