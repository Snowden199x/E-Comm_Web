<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Buyer Google sign-in. The token comes from the app's `google_sign_in` flow;
 * the API verifies it and issues the same session as a password login. Google
 * never creates a Buyer account, so an unknown email is told to register first.
 */
class BuyerGoogleAuthController extends Controller
{
    public function exchange(Request $request, GoogleIdTokenVerifier $verifier, BuyerAuthController $sessions): JsonResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'string', 'max:8192'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $claims = $verifier->verify($data['credential']);
        $buyer = User::query()->whereRaw('LOWER(email) = ?', [$claims['email']])->first();

        if (! $buyer) {
            return response()->json([
                'message' => 'No Vendo Buyer account uses this Google email. Create a Buyer account with this address first, then sign in with Google.',
            ], 404);
        }

        if ($buyer->role !== 'buyer') {
            return response()->json(['message' => 'This Google email belongs to a different Vendo account type.'], 409);
        }

        if ($buyer->status !== 'approved' || $buyer->archived_at
            || ($buyer->account_status && $buyer->account_status !== 'active')) {
            $message = 'Your account is not active. Please contact the administrator.';
            if ($buyer->status === 'pending') {
                $message = 'Your account is still pending admin approval.';
            } elseif ($buyer->status === 'disapproved') {
                $message = trim('Your registration was not approved. '.($buyer->rejection_reason ?? ''));
            }

            return response()->json(['message' => $message], 403);
        }

        if (! $buyer->email_verified_at) {
            $buyer->forceFill(['email_verified_at' => now()])->save();
        }

        $buyer->forceFill(['last_login_at' => now()])->save();
        $token = $buyer->createToken($data['device_name'], ['buyer:shop'], now()->addDays(30));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'buyer' => $sessions->profile($buyer),
        ]);
    }
}
