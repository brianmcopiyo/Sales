<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Permission slug(s). Use "slug1|slug2" to allow access if user has any of them.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // If user is not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Ensure roleModel is loaded for permission checks
        if (!$user->relationLoaded('roleModel')) {
            $user->load('roleModel');
        }

        // Admin users bypass all permission checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Support pipe-separated permissions (OR): user needs at least one
        $permissions = array_map('trim', explode('|', $permission));
        $hasAny = false;
        foreach ($permissions as $slug) {
            if ($user->hasPermission($slug)) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
