<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Outlet;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DistributionDashboardController extends Controller
{
    /**
     * Distribution dashboard: outlets count, check-ins summary, coverage for managers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->load(['roleModel.permissions']);

        if (!$user->hasPermission('outlets.view') && !$user->hasPermission('checkins.view') && !$user->hasPermission('distribution.reports')) {
            abort(403);
        }

        $includeDescendants = filter_var($request->input('include_descendants', true), FILTER_VALIDATE_BOOLEAN);
        $branchHasDescendants = $user->branch_id && count(Branch::selfAndDescendantIds($user->branch_id)) > 1;
        $allowedBranchIds = $user->branch_id
            ? ($includeDescendants ? Branch::selfAndDescendantIds($user->branch_id) : [$user->branch_id])
            : null;

        $outletQuery = Outlet::query();
        if ($allowedBranchIds !== null) {
            $outletQuery->whereIn('branch_id', $allowedBranchIds);
        }
        $outletsCount = $outletQuery->count();

        $todayStart = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();

        $checkInsQuery = CheckIn::query();
        if ($allowedBranchIds !== null) {
            $checkInsQuery->whereHas('outlet', fn ($q) => $q->whereIn('branch_id', $allowedBranchIds));
        }

        $checkInsToday = (clone $checkInsQuery)->where('check_in_at', '>=', $todayStart)->count();
        $checkInsThisWeek = (clone $checkInsQuery)->where('check_in_at', '>=', $weekStart)->count();

        $outletsVisitedThisWeek = (clone $checkInsQuery)
            ->where('check_in_at', '>=', $weekStart)
            ->selectRaw('count(distinct outlet_id) as c')
            ->value('c') ?? 0;
        $coveragePercent = $outletsCount > 0
            ? round((float) $outletsVisitedThisWeek / $outletsCount * 100, 1)
            : 0;

        return view('distribution.dashboard', [
            'outletsCount' => $outletsCount,
            'checkInsToday' => $checkInsToday,
            'checkInsThisWeek' => $checkInsThisWeek,
            'outletsVisitedThisWeek' => $outletsVisitedThisWeek,
            'coveragePercent' => $coveragePercent,
            'branchHasDescendants' => $branchHasDescendants,
            'includeDescendants' => $includeDescendants,
        ]);
    }
}
