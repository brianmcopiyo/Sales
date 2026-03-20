<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Scheme;
use Illuminate\Http\Request;

class PortalSchemeController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;
        $customerId = $profile->customer_id;

        // Customer's secondary sale IDs (for achievement calculation)
        $customerSaleIds = Sale::where('customer_id', $customerId)
            ->secondarySales()
            ->where('status', 'completed')
            ->pluck('id');

        $filter = $request->get('filter', 'active');

        $query = Scheme::orderBy('end_date');
        if ($filter === 'active') {
            $query->where('is_active', true)->where('start_date', '<=', now())->where('end_date', '>=', now());
        } elseif ($filter === 'upcoming') {
            $query->where('is_active', true)->where('start_date', '>', now());
        } elseif ($filter === 'expired') {
            $query->where('end_date', '<', now());
        }

        $schemes = $query->get()->map(function (Scheme $scheme) use ($customerSaleIds) {
            // Count how many of this customer's completed sales used this scheme
            $achievedCount = \Illuminate\Support\Facades\DB::table('sale_scheme')
                ->whereIn('sale_id', $customerSaleIds)
                ->where('scheme_id', $scheme->id)
                ->count();

            $totalDiscount = \Illuminate\Support\Facades\DB::table('sale_scheme')
                ->whereIn('sale_id', $customerSaleIds)
                ->where('scheme_id', $scheme->id)
                ->sum('discount_applied');

            $scheme->achieved_count   = $achievedCount;
            $scheme->total_discount   = $totalDiscount;
            return $scheme;
        });

        return view('portal.schemes.index', compact('schemes', 'filter', 'profile'));
    }
}
