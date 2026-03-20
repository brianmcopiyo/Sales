<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureDistributor
{
    /**
     * Only allow active distributor portal users to proceed.
     * Shares the distributor profile with all portal views.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isDistributor()) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Access denied.']);
        }

        $profile = $user->distributorProfile;

        if (!$profile || !$profile->is_active) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Your distributor portal account is not active. Please contact support.']);
        }

        // Share with all portal views so controllers don't have to re-query
        View::share('distributorProfile', $profile);

        return $next($request);
    }
}
