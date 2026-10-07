<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RiderPasswordResetController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $key = 'rider-password-otp-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => 'If a Rider account exists for that email, a reset code will be sent.'], 200);
        }
        RateLimiter::hit($key, 60);

        $rider = User::query()->whereRaw('LOWER(email) = ?', [$email])->where('role', 'courier')->first();
        if ($rider) {
            $code = (string) random_int(100000, 999999);
            try {
                Cache::put(RiderEmailOtpController::otpKey('password', $email), hash('sha256', $code), now()->addMinutes(10));
                Mail::to($email)->send(new OtpMail($code));
            } catch (\Throwable $exception) {
                Cache::forget(RiderEmailOtpController::otpKey('password', $email));
                report($exception);
            }
        }

        return response()->json(['message' => 'If a Rider account exists for that email, a reset code will be sent.']);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        $key = 'rider-password-otp-verify:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'The code is invalid or expired. Request a new one and try again.'], 422);
        }
        RateLimiter::hit($key, 60);

        $storedHash = Cache::get(RiderEmailOtpController::otpKey('password', $email));
        $riderExists = User::query()->whereRaw('LOWER(email) = ?', [$email])->where('role', 'courier')->exists();
        if (! $riderExists || ! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', $data['code']))) {
            return response()->json(['message' => 'The code is invalid or expired.'], 422);
        }

        Cache::forget(RiderEmailOtpController::otpKey('password', $email));
        RateLimiter::clear($key);
        $token = Str::random(64);
        Cache::put(RiderEmailOtpController::proofKey('password', $token), ['email' => $email], now()->addMinutes(15));

        return response()->json(['reset_token' => $token, 'expires_in' => 900]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $email = strtolower(trim($data['email']));
        $key = RiderEmailOtpController::proofKey('password', $data['reset_token']);
        $proof = Cache::get($key);
        if (! is_array($proof) || ($proof['email'] ?? null) !== $email) {
            return response()->json(['message' => 'The reset request is invalid or expired. Request a new code.'], 422);
        }

        $rider = DB::transaction(function () use ($email, $data) {
            $rider = User::query()->whereRaw('LOWER(email) = ?', [$email])
                ->where('role', 'courier')->lockForUpdate()->first();
            if (! $rider) return null;

            $rider->forceFill(['password' => Hash::make($data['password'])])->save();
            $rider->tokens()->delete();

            return $rider;
        }, 3);

        Cache::forget($key);
        if (! $rider) {
            return response()->json(['message' => 'The reset request is invalid or expired. Request a new code.'], 422);
        }

        return response()->json(['message' => 'Your Rider password has been reset. Sign in with your new password.']);
    }
}
