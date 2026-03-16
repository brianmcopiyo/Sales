<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFieldAgent
{
    /**
     * Only allow active field agents to proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $profile = $user->fieldAgentProfile;
        if (!$profile || !$profile->is_active) {
            return redirect()
                ->route('sales.index')
                ->withErrors(['error' => 'Only sales field agents can create sales.']);
        }

        return $next($request);
    }
}


