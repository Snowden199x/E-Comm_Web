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

/**
 * Password recovery for the Transport Driver app.
 *
 * The driver is a `transport_driver` user, so the Rider reset flow cannot be
 * reused as it is: it only looks up `courier` accounts. The OTP storage keys
 * are the same shape as the Rider ones, so a code is never valid for another
 * role's reset.
 */
class TransportDriverPasswordResetController extends Controller
{
    private const ROLE = 'transport_driver';

    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $key = 'driver-password-otp-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => $this->sentMessage()]);
        }
        RateLimiter::hit($key, 60);

        $driver = User::query()->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', self::ROLE)->first();
        if ($driver) {
            $code = (string) random_int(100000, 999999);
            try {
                Cache::put($this->otpKey('password', $email), hash('sha256', $code), now()->addMinutes(10));
                Mail::to($email)->send(new OtpMail($code));
            } catch (\Throwable $exception) {
                Cache::forget($this->otpKey('password', $email));
                report($exception);
            }
        }

        return response()->json(['message' => $this->sentMessage()]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        $key = 'driver-password-otp-verify:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'The code is invalid or expired. Request a new one and try again.'], 422);
        }
        RateLimiter::hit($key, 60);

        $storedHash = Cache::get($this->otpKey('password', $email));
        $driverExists = User::query()->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', self::ROLE)->exists();
        if (! $driverExists || ! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', $data['code']))) {
            return response()->json(['message' => 'The code is invalid or expired.'], 422);
        }

        Cache::forget($this->otpKey('password', $email));
        RateLimiter::clear($key);
        $token = Str::random(64);
        Cache::put($this->proofKey('password', $token), ['email' => $email], now()->addMinutes(15));

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
        $key = $this->proofKey('password', $data['reset_token']);
        $proof = Cache::get($key);
        if (! is_array($proof) || ($proof['email'] ?? null) !== $email) {
            return response()->json(['message' => 'The reset request is invalid or expired. Request a new code.'], 422);
        }

        $driver = DB::transaction(function () use ($email, $data) {
            $driver = User::query()->whereRaw('LOWER(email) = ?', [$email])
                ->where('role', self::ROLE)->lockForUpdate()->first();
            if (! $driver) {
                return null;
            }

            $driver->forceFill(['password' => Hash::make($data['password'])])->save();
            // Every device signs out once the password changes.
            $driver->tokens()->delete();

            return $driver;
        }, 3);

        Cache::forget($key);
        if (! $driver) {
            return response()->json(['message' => 'The reset request is invalid or expired. Request a new code.'], 422);
        }

        return response()->json([
            'message' => 'Your password has been reset. Sign in with your new password.',
        ]);
    }

    /** Answers the same way whether or not the email is registered. */
    private function sentMessage(): string
    {
        return 'If a Transport Driver account exists for that email, a reset code will be sent.';
    }

    private function otpKey(string $purpose, string $email): string
    {
        return 'driver:'.$purpose.':otp:'.hash('sha256', strtolower($email));
    }

    private function proofKey(string $purpose, string $token): string
    {
        return 'driver:'.$purpose.':proof:'.hash('sha256', $token);
    }
}
