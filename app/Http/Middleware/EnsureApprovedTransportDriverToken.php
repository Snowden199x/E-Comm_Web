<?php

namespace App\Http\Middleware;

use App\Services\TransportDriverAccessService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedTransportDriverToken
{
    public function __construct(private TransportDriverAccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $driver = $this->access->approvedDriver($request->user());
        abort_unless($request->user()?->currentAccessToken() instanceof PersonalAccessToken
            && $request->user()->tokenCan('transport-driver:trip') && $driver,
            403, 'An approved Transport Driver token and active Province Hub membership are required.');

        $request->attributes->set('transport_driver_detail', $driver);

        return $next($request);
    }
}
