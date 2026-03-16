<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CommissionDisbursementController extends Controller
{
    /**
     * Display user's commissions per sale (no withdrawal feature).
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $totalEarned = (float) ($user->total_commission_earned ?? 0);

        $query = Sale::query()
            ->where('status', 'completed')
            ->where('sold_by', $user->id)
            ->withSum('items as total_commission', 'commission_amount')
            ->with(['customer', 'branch'])
            ->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(15)->withQueryString();

        return view('commission-disbursements.index', compact('totalEarned', 'sales'));
    }

    /**
     * Display list of users and their total commissions (admin view).
     */
    public function adminIndex(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can access this page.');
        }

        $authUser = auth()->user();
        $allowedBranchIds = $authUser->branch_id ? \App\Models\Branch::selfAndDescendantIds($authUser->branch_id) : null;
        $visibleUserIds = User::visibleTo($authUser)->pluck('id');

        $baseQuery = Sale::query()
            ->where('status', 'completed')
            ->whereIn('sold_by', $visibleUserIds->isEmpty() ? [null] : $visibleUserIds)
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('sales.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('sales.created_at', '<=', $request->date_to);
        }

        $userTotals = (clone $baseQuery)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw('sales.sold_by as user_id, COALESCE(SUM(sale_items.commission_amount), 0) as total_commission, COUNT(DISTINCT sales.id) as sales_count')
            ->groupBy('sales.sold_by')
            ->orderByDesc('total_commission')
            ->get();

        $totalCommission = $userTotals->sum('total_commission');
        $userIds = $userTotals->pluck('user_id')->filter()->unique()->values()->all();
        $usersMap = User::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $perPage = 15;
        $page = (int) $request->get('page', 1);
        $paginated = new LengthAwarePaginator(
            $userTotals->forPage($page, $perPage)->values(),
            $userTotals->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'total_commission' => (float) $totalCommission,
            'users_count' => $userTotals->count(),
        ];

        return view('commission-disbursements.admin-index', compact('paginated', 'usersMap', 'stats'));
    }

    /**
     * Display one user's commission details: list of their sales with commission (admin).
     */
    public function adminUserShow(Request $request, User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can access this page.');
        }

        $authUser = auth()->user();
        if (!User::visibleToUser($user, $authUser)) {
            abort(403, 'You do not have access to this user.');
        }

        $allowedBranchIds = $authUser->branch_id ? \App\Models\Branch::selfAndDescendantIds($authUser->branch_id) : null;

        $query = Sale::query()
            ->where('status', 'completed')
            ->where('sold_by', $user->id)
            ->withSum('items as total_commission', 'commission_amount')
            ->with(['customer', 'branch'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(15)->withQueryString();

        $sumQuery = Sale::query()
            ->where('status', 'completed')
            ->where('sold_by', $user->id)
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
        if ($request->filled('date_from')) {
            $sumQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $sumQuery->whereDate('created_at', '<=', $request->date_to);
        }
        $totalInPeriod = (float) ($sumQuery->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.commission_amount'));

        return view('commission-disbursements.admin-user-show', compact('user', 'sales', 'totalInPeriod'));
    }
}
