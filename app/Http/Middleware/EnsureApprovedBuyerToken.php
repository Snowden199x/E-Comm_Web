<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buyer mobile API guard. Mirrors the web's EnsureActiveBuyer: the account must be an approved,
 * unarchived, active buyer. A token alone never grants access.
 */
class EnsureApprovedBuyerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $buyer = $request->user();

        if (! $buyer || $buyer->role !== 'buyer') {
            return response()->json(['message' => 'This login is for buyer accounts only.'], 403);
        }

        if ($buyer->status !== 'approved' || $buyer->archived_at
            || ($buyer->account_status && $buyer->account_status !== 'active')) {
            $message = match ($buyer->status) {
                'pending' => 'Your account is still pending admin approval.',
                'disapproved' => 'Your registration was not approved. '.($buyer->rejection_reason ?? ''),
                default => 'Your account is not active.',
            };

            if ($buyer->archived_at || ($buyer->account_status && $buyer->account_status !== 'active')) {
                $message = 'Your account is not active. Please contact the administrator.';
            }

            return response()->json(['message' => trim($message)], 403);
        }

        return $next($request);
    }
}
