<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveBuyer
{
    public function handle(Request $request, Closure $next)
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->role === 'buyer' && $buyer->status === 'approved'
            && ! $buyer->archived_at
            && (! $buyer->account_status || $buyer->account_status === 'active'), 403,
            'Your buyer account is not active. Please contact the administrator.');

        return $next($request);
    }
}
