<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && in_array($user->status, ['suspended', 'banned'], true)) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'This account is '.$user->status.'. Contact support to appeal.']);
        }

        return $next($request);
    }
}
