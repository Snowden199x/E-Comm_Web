<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Buyer mobile sign-in. The web keeps its session login; this issues a Sanctum token instead. */
class BuyerAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $buyer = User::query()->where('email', strtolower(trim($data['email'])))->first();
        if (! $buyer || ! Hash::check($data['password'], $buyer->password)) {
            return response()->json(['message' => 'These credentials do not match our records.'], 401);
        }

        if ($buyer->role !== 'buyer') {
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

        $token = $buyer->createToken($data['device_name'], ['buyer:shop'], now()->addDays(30));
        $buyer->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'buyer' => $this->profile($buyer),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['buyer' => $this->profile($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function profile(User $buyer): array
    {
        $detail = $buyer->buyerDetail;

        return [
            'id' => $buyer->id,
            'name' => $buyer->name,
            'email' => $buyer->email,
            'phone_number' => $buyer->phone_number,
            'profile_picture' => $buyer->profile_picture ? asset('storage/'.$buyer->profile_picture) : null,
            'status' => $buyer->status,
            'address' => $detail ? [
                'province' => $detail->province,
                'municipality' => $detail->municipality,
                'barangay' => $detail->barangay,
                'street' => $detail->street,
                'house_no' => $detail->house_no,
                'zip_code' => $detail->zip_code,
            ] : null,
        ];
    }
}
