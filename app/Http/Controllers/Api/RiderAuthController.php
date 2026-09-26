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
        $center = $rider && Hash::check($data['password'], $rider->password)
            ? $access->approvedCenter($rider)
            : null;

        if (! $center) {
            return response()->json(['message' => 'Invalid credentials or rider account is not approved.'], 401);
        }

        $token = $rider->createToken($data['device_name'], ['rider:scan'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'rider' => ['id' => $rider->id, 'name' => $rider->name],
            'logistics_center' => ['id' => $center->id, 'name' => $center->business_name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
