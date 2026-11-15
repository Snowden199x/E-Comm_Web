<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use App\Services\TransportDriverAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Transport Driver Google sign-in. The app sends the Google ID token; the API
 * verifies it, applies the same role and hub-approval checks as the password
 * login and returns the same session payload, so a driver opens the trip board
 * the same way either route. Google never creates an application.
 */
class TransportDriverGoogleAuthController extends Controller
{
    public function exchange(
        Request $request,
        GoogleIdTokenVerifier $verifier,
        TransportDriverAccessService $access,
        TransportDriverApiController $sessions,
    ): JsonResponse {
        $data = $request->validate([
            'credential' => ['required', 'string', 'max:8192'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $claims = $verifier->verify($data['credential']);
        $user = User::query()->whereRaw('LOWER(email) = ?', [$claims['email']])->first();

        if (! $user) {
            return response()->json([
                'message' => 'No Transport Driver application uses this Google email. Register as a driver first.',
            ], 404);
        }

        if ($user->role !== 'transport_driver') {
            return response()->json(['message' => 'This Google email belongs to a different Vendo account type.'], 409);
        }

        $driver = $access->approvedDriver($user);

        if (! $driver) {
            // A valid application learns where it stands, exactly as the
            // password login answers, so the app shows the same waiting notice.
            $detail = TransportDriverDetail::query()->where('user_id', $user->id)
                ->with(['stationHub:id,name', 'provinceHub:id,name'])->first();
            $pending = $user->status === 'pending';
            $reviewing = $detail?->reviewingHub()?->name;

            return response()->json([
                'message' => $pending
                    ? 'Your application is waiting for '.($reviewing ?? 'hub').' approval.'
                    : ($user->status === 'disapproved'
                        ? 'Your application was declined.'.($user->rejection_reason ? ' '.$user->rejection_reason : '')
                        : 'Your account is not active. Contact your hub.'),
                'status' => $pending ? 'pending' : ($user->status === 'disapproved' ? 'rejected' : 'inactive'),
                'apply_to' => $detail?->apply_to,
                'reviewing_hub' => $reviewing,
                'rejection_reason' => $user->status === 'disapproved' ? $user->rejection_reason : null,
            ], 403);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        $token = $user->createToken($data['device_name'], ['transport-driver:trip'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ] + $sessions->profilePayload($user, $driver));
    }
}
