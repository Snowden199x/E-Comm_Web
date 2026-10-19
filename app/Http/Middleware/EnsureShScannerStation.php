<?php

namespace App\Http\Middleware;

use App\Models\Profiles\ShScannerStation;
use Closure;
use Illuminate\Http\Request;

class EnsureShScannerStation
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token || strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return response()->json(['message' => 'Pair this scanner with an active SH station.'], 401);
        }

        $station = ShScannerStation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where('token_expires_at', '>', now())
            ->with(['checkpoint', 'logisticsCenter.user'])
            ->first();
        $centerUser = $station?->logisticsCenter?->user;
        if (! $station || ! $station->checkpoint?->is_active || ! $centerUser
            || $centerUser->role !== 'logistics_center' || $centerUser->status !== 'approved'
            || $centerUser->archived_at || ($centerUser->account_status && $centerUser->account_status !== 'active')) {
            return response()->json(['message' => 'Scanner session is expired or revoked. Pair again.'], 401);
        }

        $request->attributes->set('sh_station', $station);

        return $next($request);
    }
}
