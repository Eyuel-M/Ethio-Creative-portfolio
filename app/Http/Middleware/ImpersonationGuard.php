<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * While an admin is viewing the site as another user, everything is read-only: any request that would change
 * data is refused, except stopping the impersonation itself.
 */
class ImpersonationGuard
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('impersonator_id') && ! $request->isMethodSafe() && ! $request->routeIs('impersonation.stop', 'logout')) {
            return back()->with('status', 'Read-only: you are viewing the site as another user. Nothing was changed.');
        }

        return $next($request);
    }
}
