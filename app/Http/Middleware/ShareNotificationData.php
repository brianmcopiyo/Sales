<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareNotificationData
{
    /**
     * Share notification count and recent items with the layout (navbar bell).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();
            View::share('notificationUnreadCount', $user->unreadNotifications()->count());
            View::share('notificationRecent', $user->unreadNotifications()->latest()->limit(8)->get());
        }

        return $next($request);
    }
}
