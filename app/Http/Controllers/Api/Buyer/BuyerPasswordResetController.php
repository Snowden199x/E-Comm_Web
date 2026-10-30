<?php

namespace App\Http\Controllers\Api\Buyer;

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
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/** Buyer "forgot password" for the mobile app. Only buyer accounts receive a code. */
class BuyerPasswordResetController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $key = 'buyer-password-otp-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => 'If a buyer account exists for that email, a reset code will be sent.']);
        }
        RateLimiter::hit($key, 60);

        $buyer = User::query()->whereRaw('LOWER(email) = ?', [$email])->where('role', 'buyer')->first();
        if ($buyer) {
            $code = (string) random_int(100000, 999999);
            try {
                Cache::put($this->otpKey($email), hash('sha256', $code), now()->addMinutes(10));
                Mail::to($email)->send(new OtpMail($code));
            } catch (\Throwable $exception) {
                Cache::forget($this->otpKey($email));
                report($exception);
            }
        }

        return response()->json(['message' => 'If a buyer account exists for that email, a reset code will be sent.']);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        $key = 'buyer-password-otp-verify:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'The code is invalid or expired. Request a new one and try again.'], 422);
        }
        RateLimiter::hit($key, 60);

        $storedHash = Cache::get($this->otpKey($email));
        $buyerExists = User::query()->whereRaw('LOWER(email) = ?', [$email])->where('role', 'buyer')->exists();
        if (! $buyerExists || ! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', $data['code']))) {
            return response()->json(['message' => 'The code is invalid or expired.'], 422);
        }

        Cache::forget($this->otpKey($email));
        RateLimiter::clear($key);
        $token = Str::random(64);
        Cache::put($this->proofKey($token), ['email' => $email], now()->addMinutes(15));

        return response()->json(['reset_token' => $token, 'expires_in' => 900]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        $email = strtolower(trim($data['email']));
        $proof = Cache::get($this->proofKey((string) $data['reset_token']));
        if (! is_array($proof) || ($proof['email'] ?? null) !== $email) {
            throw ValidationException::withMessages(['reset_token' => 'Your reset link expired. Request a new code.']);
        }

        $buyer = User::query()->whereRaw('LOWER(email) = ?', [$email])->where('role', 'buyer')->first();
        if (! $buyer) {
            throw ValidationException::withMessages(['email' => 'The code is invalid or expired.']);
        }

        DB::transaction(function () use ($buyer, $data) {
            $buyer->update(['password' => Hash::make($data['password'])]);
            $buyer->tokens()->delete();
        });
        Cache::forget($this->proofKey((string) $data['reset_token']));

        return response()->json(['message' => 'Password updated. Sign in with your new password.']);
    }

    private function otpKey(string $email): string
    {
        return 'buyer:password:otp:'.hash('sha256', $email);
    }

    private function proofKey(string $token): string
    {
        return 'buyer:password:proof:'.$token;
    }
}
