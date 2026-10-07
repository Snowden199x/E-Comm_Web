<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveLogisticsCenter
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        abort_unless($user && $user->role === 'logistics_center', 403, 'Logistics center access only.');
        abort_unless($user->status === 'approved' && ! $user->archived_at
            && (! $user->account_status || $user->account_status === 'active'), 403,
            'Your logistics center account is not active. Please contact the administrator.');
        abort_unless($user->logisticsCenterDetail()->exists(), 403, 'Logistics center profile is required.');

        return $next($request);
    }
}
