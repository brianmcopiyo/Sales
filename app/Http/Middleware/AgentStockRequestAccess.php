<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentStockRequestAccess
{
    /**
     * Handle access to agent stock request routes.
     * - view: field agents get access automatically; others need agent-stock-requests.view
     * - store: only field agents (submit request)
     * - manage: only users with agent-stock-requests.create (approve/reject/close)
     *
     * @param  string  $mode  One of: view, store, manage
     */
    public function handle(Request $request, Closure $next, string $mode = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->relationLoaded('fieldAgentProfile')) {
            $user->load('fieldAgentProfile');
        }
        $isFieldAgent = (bool) $user->fieldAgentProfile;

        if ($mode === 'view') {
            if ($isFieldAgent && $user->branch_id) {
                return $next($request);
            }
            if (!$user->relationLoaded('roleModel')) {
                $user->load('roleModel');
            }
            if ($user->hasPermission('agent-stock-requests.view')) {
                return $next($request);
            }
            abort(403, 'You do not have access to agent stock requests.');
        }

        if ($mode === 'store') {
            if ($isFieldAgent && $user->branch_id) {
                return $next($request);
            }
            abort(403, 'Only field agents assigned to a branch can submit agent stock requests.');
        }

        if ($mode === 'manage') {
            if (!$user->relationLoaded('roleModel')) {
                $user->load('roleModel');
            }
            if ($user->hasPermission('agent-stock-requests.create')) {
                return $next($request);
            }
            abort(403, 'You do not have permission to approve or reject agent stock requests.');
        }

        abort(403, 'Access denied.');
    }
}
