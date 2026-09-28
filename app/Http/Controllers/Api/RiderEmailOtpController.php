<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RiderEmailOtpController extends Controller
{
    public function sendRegistrationCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account with this email already exists.']);
        }

        $key = 'rider-registration-otp-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many code requests. Please wait before trying again.'], 429);
        }
        RateLimiter::hit($key, 60);

        $code = (string) random_int(100000, 999999);
        try {
            Cache::put($this->otpKey('registration', $email), hash('sha256', $code), now()->addMinutes(10));
            Mail::to($email)->send(new RegistrationOtpMail($code));
        } catch (\Throwable $exception) {
            Cache::forget($this->otpKey('registration', $email));
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
        $key = 'rider-registration-otp-verify:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many verification attempts. Request a new code later.'], 429);
        }
        RateLimiter::hit($key, 60);

        $storedHash = Cache::get($this->otpKey('registration', $email));
        if (! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', $data['code']))) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or expired.']);
        }

        Cache::forget($this->otpKey('registration', $email));
        RateLimiter::clear($key);
        $token = Str::random(64);
        Cache::put($this->proofKey('email', $token), ['email' => $email], now()->addMinutes(30));

        return response()->json([
            'message' => 'Email verified.',
            'verification_token' => $token,
            'expires_in' => 1800,
        ]);
    }

    public static function otpKey(string $purpose, string $email): string
    {
        return 'rider:'.$purpose.':otp:'.hash('sha256', strtolower($email));
    }

    public static function proofKey(string $purpose, string $token): string
    {
        return 'rider:'.$purpose.':proof:'.hash('sha256', $token);
    }
}
