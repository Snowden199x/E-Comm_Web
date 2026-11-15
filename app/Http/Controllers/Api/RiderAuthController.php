<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RiderAuthController extends Controller
{
    public function login(Request $request, RiderAccessService $access): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $rider = User::query()->where('email', $data['email'])->first();

        // A wrong email or password stays 401. The Rider app shares one form
        // with the Driver app and retries the driver endpoint on 401, so the
        // wrong-account-type case must not become a 403.
        if (! $rider || ! Hash::check($data['password'], $rider->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }
        if ($rider->role !== 'courier') {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        $center = $access->approvedCenter($rider);
        if (! $center) {
            // The credentials are right, so say what is actually blocking the
            // sign-in instead of blaming the password.
            return response()->json(['message' => $this->blockedReason($rider)], 403);
        }

        $token = $rider->createToken($data['device_name'], ['rider:scan'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'rider' => [
                'id' => $rider->id,
                'name' => $rider->name,
                'vehicle_type' => $rider->courierDetail?->vehicle_type,
            ],
            'logistics_center' => ['id' => $center->id, 'name' => $center->business_name],
        ]);
    }

    /**
     * Why an approved-password Rider still cannot sign in. Mirrors the checks
     * RiderAccessService::approvedCenter() makes, in the order a Rider can act
     * on them.
     */
    private function blockedReason(User $rider): string
    {
        if ($rider->status === 'pending') {
            $station = $rider->courierDetail?->stationHub?->name;

            return 'Your rider application is still waiting for approval'
                .($station ? ' from '.$station : '')
                .'. Login opens once the Station Hub approves it.';
        }
        if ($rider->status === 'disapproved') {
            return $rider->rejection_reason
                ? 'Your rider application was not approved: '.$rider->rejection_reason
                : 'Your rider application was not approved. Ask your Station Hub for the reason.';
        }
        if ($rider->archived_at) {
            return 'This rider account has been archived. Ask your Station Hub to restore it.';
        }
        if ($rider->status !== 'approved' || $rider->account_status === 'deactivated') {
            return 'This rider account is not active. Ask your Station Hub to activate it again.';
        }

        return 'Your Station Hub or logistics company is not active right now, so this account cannot sign in. Contact your Station Hub.';
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
