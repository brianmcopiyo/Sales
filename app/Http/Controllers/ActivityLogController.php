<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $isFieldAgent = $currentUser->fieldAgentProfile && $currentUser->branch_id;
        $allowedUserIds = null;
        if ($isFieldAgent) {
            $allowedUserIds = [$currentUser->id];
        } elseif ($currentUser->branch_id) {
            $allowedUserIds = User::visibleTo($currentUser)->pluck('id')->all();
        }

        $query = ActivityLog::with(['user', 'model'])->latest();

        if ($allowedUserIds !== null) {
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($request->filled('user_id')) {
            $userId = $request->get('user_id');
            if ($allowedUserIds === null || in_array($userId, $allowedUserIds, true)) {
                $query->where('user_id', $userId);
            }
        }

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $activityLogs = $query->paginate(30)->withQueryString();

        $users = $isFieldAgent
            ? User::where('id', $currentUser->id)->orderBy('name')->get()
            : User::visibleTo($currentUser)->orderBy('name')->get();

        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();

        $baseQuery = ActivityLog::query();
        if ($allowedUserIds !== null) {
            $baseQuery->whereIn('user_id', $allowedUserIds);
        }
        if ($request->filled('user_id')) {
            $userId = $request->get('user_id');
            if ($allowedUserIds === null || in_array($userId, $allowedUserIds, true)) {
                $baseQuery->where('user_id', $userId);
            }
        }
        if ($request->filled('action')) {
            $baseQuery->where('action', $request->get('action'));
        }
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return view('activity-logs.index', compact('activityLogs', 'users', 'actions', 'stats'));
    }
}
