<?php

namespace App\Http\Middleware;

use App\Services\RiderAccessService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedRiderToken
{
    public function __construct(private RiderAccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $rider = $request->user();
        abort_unless($rider?->currentAccessToken() instanceof PersonalAccessToken
            && $rider->tokenCan('rider:scan')
            && $this->access->approvedCenter($rider), 403, 'Approved rider token and active logistics membership required.');

        return $next($request);
    }
}
