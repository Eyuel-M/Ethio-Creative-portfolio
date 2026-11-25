<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        $ok = $role === 'freelancer' ? ($user->is_freelancer && $user->freelancerProfile) : ($user->is_client && $user->clientProfile);
        if (! $ok && ! $user->hasMemberWorkspace()) {
            // A member who hasn't picked a path: offer the right one, then bring them back here.
            if ($request->isMethod('GET')) {
                session(['after_enable' => $request->fullUrl()]);
            }

            return redirect()->route('dashboard', ['need' => $role]);
        }
        if (! $ok) {
            return redirect()->route('settings')->with('status', 'Enable the '.$role.' workspace to continue.');
        }
        return $next($request);
    }
}
