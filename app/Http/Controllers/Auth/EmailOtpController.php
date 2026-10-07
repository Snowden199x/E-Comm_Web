<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class EmailOtpController extends Controller
{
    /**
     * Generate a 6-digit code, cache it against the email, and send it.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($request->string('email'));

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this email already exists.',
            ], 422);
        }

        $throttleKey = 'otp-send:'.$email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please wait a minute before trying again.',
            ], 429);
        }

        RateLimiter::hit($throttleKey, 60);

        $code = (string) random_int(100000, 999999);

        // Both the cache write and the send are wrapped together now —
        // previously Cache::put() sat outside the try/catch, so a cache
        // failure (e.g. the `cache` table missing) crashed with a raw
        // 500 instead of the friendly JSON error below. And on failure
        // we now log the *real* exception message/trace via Log::error,
        // since swallowing it silently is exactly why "no email, no
        // visible reason" happens — check storage/logs/laravel.log
        // after a failed attempt to see the actual SMTP/cache error.
        try {
            Cache::put('otp:'.$email, $code, now()->addMinutes(10));

            Mail::to($email)->send(new RegistrationOtpMail($code));
        } catch (\Throwable $e) {
            Log::error('OTP email send failed for '.$email.': '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Could not send the verification email: '.$e->getMessage()
                    : 'Could not send the verification email. Please check the address and try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent.',
        ]);
    }

    /**
     * Compare the submitted code against the cached one for this email.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower($request->string('email'));
        $cached = Cache::get('otp:'.$email);

        if (! $cached || ! hash_equals($cached, $request->string('code')->value())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        Cache::forget('otp:'.$email);
        $expiresAt = now()->addMinutes(30);

        Cache::put('otp_verified:'.$email, true, $expiresAt);

        $request->session()->put('registration_verification', [
            'email' => $email,
            'expires_at' => $expiresAt->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified.',
        ]);
    }
}