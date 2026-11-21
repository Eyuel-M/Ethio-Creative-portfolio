<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Route guard: `admin.can:section` (see config/admin.php). */
class AdminCan
{
    public function handle(Request $request, Closure $next, string $section)
    {
        abort_unless($request->user()?->canAdmin($section), 403, 'Your admin role does not include this section.');

        return $next($request);
    }
}
