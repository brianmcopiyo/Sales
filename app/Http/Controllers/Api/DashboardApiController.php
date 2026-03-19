<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard-summary — outlet count and check-in counts for app dashboard KPIs.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $outletQuery = Outlet::query();
        if ($user->branch_id) {
            $outletQuery->where(function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                    ->orWhere('assigned_to', $user->id);
            });
        }
        $outletsCount = $outletQuery->count();

        $todayStart = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();

        $checkInsToday = CheckIn::where('user_id', $user->id)
            ->where('check_in_at', '>=', $todayStart)
            ->count();

        $checkInsThisWeek = CheckIn::where('user_id', $user->id)
            ->where('check_in_at', '>=', $weekStart)
            ->count();

        return response()->json([
            'outlets_count' => $outletsCount,
            'check_ins_today' => $checkInsToday,
            'check_ins_this_week' => $checkInsThisWeek,
        ]);
    }
}
