<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\PeriodHelper;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Scheme;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;
        $customerId = $profile->customer_id;

        // Revenue MTD
        [$mtdStart, $mtdEnd] = PeriodHelper::getRange('this_month');
        $revenueMtd = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$mtdStart, $mtdEnd])
            ->sum('total');

        // Revenue YTD
        [$ytdStart, $ytdEnd] = PeriodHelper::getRange('this_year');
        $revenueYtd = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$ytdStart, $ytdEnd])
            ->sum('total');

        // Total orders this month
        $ordersMtd = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->whereBetween('created_at', [$mtdStart, $mtdEnd])
            ->count();

        // Active schemes
        $activeSchemes = Scheme::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('end_date')
            ->get();

        // Recent 10 orders
        $recentOrders = Sale::with(['items.product'])
            ->where('customer_id', $customerId)
            ->secondarySales()
            ->latest()
            ->limit(10)
            ->get();

        // Monthly revenue chart: last 6 months
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyRevenue[] = [
                'label'   => $month->format('M Y'),
                'revenue' => Sale::where('customer_id', $customerId)
                    ->secondarySales()
                    ->where('status', 'completed')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total'),
            ];
        }

        // Pending claims count
        $pendingClaims = $profile->claims()->whereIn('status', ['pending', 'under_review'])->count();

        return view('portal.dashboard.index', compact(
            'profile',
            'revenueMtd',
            'revenueYtd',
            'ordersMtd',
            'activeSchemes',
            'recentOrders',
            'monthlyRevenue',
            'pendingClaims'
        ));
    }
}
