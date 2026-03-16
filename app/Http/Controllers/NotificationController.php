<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for the current user (for dropdown / API).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = $user->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->limit(20)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'action_url' => $notification->data['action_url'] ?? null,
                'activity' => $notification->data['activity'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'unread_count' => $user->unreadNotifications()->count(),
                'notifications' => $notifications,
            ]);
        }

        $filter = $request->get('filter', 'all');
        $viewQuery = $user->notifications()->latest();
        if ($filter === 'unread') {
            $viewQuery->whereNull('read_at');
        } elseif ($filter === 'read') {
            $viewQuery->whereNotNull('read_at');
        }
        $notificationsPaginated = $viewQuery->paginate(15)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notificationsPaginated,
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read and optionally redirect to action_url.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['marked' => true]);
        }

        $url = $notification->data['action_url'] ?? route('notifications.index');
        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['marked' => true]);
        }

        $filter = $request->get('redirect_filter', 'all');
        return redirect()->route('notifications.index', $filter !== 'all' ? ['filter' => $filter] : [])
            ->with('success', 'All notifications marked as read.');
    }
}
