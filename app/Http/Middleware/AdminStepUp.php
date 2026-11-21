<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Admins confirm a one-time code (sent by email) before using the console; re-asked every few hours. */
class AdminStepUp
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('admin.step_up') || $request->routeIs('admin.verify', 'admin.verify.*')) {
            return $next($request);
        }
        $at = $request->session()->get('admin_verified_at');
        if (! $at || now()->diffInHours(\Illuminate\Support\Carbon::createFromTimestamp($at)) >= config('admin.step_up_hours')) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('admin.verify');
        }

        return $next($request);
    }
}
