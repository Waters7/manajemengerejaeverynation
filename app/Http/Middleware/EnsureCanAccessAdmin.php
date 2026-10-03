<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The ministry dashboard is only for active accounts holding a ministry role.
 * Public sign-ups (role USER) are sent to their member dashboard instead.
 */
class EnsureCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->canAccessAdmin()) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->route('member.dashboard')->with('status', 'Halaman tersebut khusus untuk tim pelayanan.');
        }

        return $next($request);
    }
}
