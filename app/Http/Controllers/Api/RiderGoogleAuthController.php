<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RiderGoogleAuthController extends Controller
{
    public function exchange(Request $request, GoogleIdTokenVerifier $verifier, RiderAccessService $access): JsonResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'string', 'max:8192'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $claims = $verifier->verify($data['credential']);
        $email = $claims['email'];
        $rider = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($rider) {
            if ($rider->role !== 'courier') {
                return response()->json(['message' => 'This Google email belongs to a different Vendo account type.'], 409);
            }
            if ($rider->archived_at || ($rider->account_status && $rider->account_status !== 'active')) {
                return response()->json(['message' => 'This Rider account is not active. Contact Vendo support.'], 403);
            }
            $center = $access->approvedCenter($rider);
            if (! $center) {
                return response()->json(['message' => $rider->status === 'pending'
                    ? 'Your Rider application is still waiting for logistics approval.'
                    : 'This Rider account is not approved or its logistics hub is inactive.'], 403);
            }

            if (! $rider->email_verified_at) {
                $rider->forceFill(['email_verified_at' => now()])->save();
            }
            $token = $rider->createToken($data['device_name'], ['rider:scan'], now()->addDays(7));

            return response()->json([
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                'rider' => ['id' => $rider->id, 'name' => $rider->name, 'vehicle_type' => $rider->courierDetail?->vehicle_type],
                'logistics_center' => ['id' => $center->id, 'name' => $center->business_name],
            ]);
        }

        $registrationToken = Str::random(64);
        Cache::put(RiderEmailOtpController::proofKey('google-registration', $registrationToken), [
            'email' => $email,
            'first_name' => $claims['given_name'] ?? '',
            'last_name' => $claims['family_name'] ?? '',
        ], now()->addMinutes(30));

        return response()->json([
            'registration_required' => true,
            'registration_token' => $registrationToken,
            'email' => $email,
            'first_name' => $claims['given_name'] ?? '',
            'last_name' => $claims['family_name'] ?? '',
            'expires_in' => 1800,
        ], 202);
    }
}
