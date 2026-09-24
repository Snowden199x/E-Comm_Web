<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureActiveSeller
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'seller', 403, 'Seller access only.');
        abort_unless($user->status === 'approved' && ! $user->archived_at &&
            (! $user->account_status || $user->account_status === 'active'), 403,
            'Your seller account is not active. Please contact the administrator.');
        return $next($request);
    }
}
