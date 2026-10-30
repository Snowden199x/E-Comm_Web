<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Buyer mobile registration email verification. The web stores the proof in the session; the
 * mobile app gets a short-lived token instead, which the register endpoint then requires.
 */
class BuyerEmailOtpController extends Controller
{
    public const TTL_MINUTES = 10;
    public const PROOF_TTL_MINUTES = 30;

    public function sendRegistrationCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account with this email already exists.']);
        }

        $key = 'buyer-registration-otp-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many code requests. Please wait before trying again.'], 429);
        }
        RateLimiter::hit($key, 60);

        $code = (string) random_int(100000, 999999);
        try {
            Cache::put($this->otpKey($email), hash('sha256', $code), now()->addMinutes(self::TTL_MINUTES));
            Mail::to($email)->send(new OtpMail($code));
        } catch (\Throwable $exception) {
            Cache::forget($this->otpKey($email));
            report($exception);

            return response()->json(['message' => 'Could not send the verification code. Check your email setup and try again.'], 503);
        }

        return response()->json(['message' => 'A verification code was sent to your email.']);
    }

    public function verifyRegistrationCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));

        $key = 'buyer-registration-otp-verify:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many verification attempts. Request a new code later.'], 429);
        }
        RateLimiter::hit($key, 60);

        $storedHash = Cache::get($this->otpKey($email));
        if (! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', $data['code']))) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or expired.']);
        }

        Cache::forget($this->otpKey($email));
        RateLimiter::clear($key);
        $token = Str::random(64);
        Cache::put($this->proofKey($token), ['email' => $email], now()->addMinutes(self::PROOF_TTL_MINUTES));

        return response()->json([
            'message' => 'Email verified.',
            'verification_token' => $token,
            'expires_in' => self::PROOF_TTL_MINUTES * 60,
        ]);
    }

    public static function otpKey(string $email): string
    {
        return 'buyer:registration:otp:'.hash('sha256', strtolower($email));
    }

    public static function proofKey(string $token): string
    {
        return 'buyer:registration:proof:'.$token;
    }
}
