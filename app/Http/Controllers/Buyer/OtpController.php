<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);
        $email = strtolower(trim($request->string('email')->value()));
        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'An account with this email already exists.'], 422);
        }
        $key = 'buyer-otp-send:'.$email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many requests. Please try again in a minute.'], 429);
        }
        RateLimiter::hit($key, 60);
        $otpCode = random_int(100000, 999999);
        try {
            $record = OtpVerification::updateOrCreate(['email' => $email], [
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
                'is_verified' => false,
            ]);
            $request->session()->forget('buyer_registration_verification');
            Mail::to($email)->send(new OtpMail($otpCode));
        } catch (\Throwable $exception) {
            if (isset($record)) {
                $record->delete();
            }
            report($exception);

            return response()->json(['message' => 'Could not send the code. Please try again.'], 500);
        }

        return response()->json(['message' => 'OTP sent.']);
    }

    public function verify(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255', 'otp_code' => 'required|digits:6']);
        $email = strtolower(trim($request->string('email')->value()));
        $key = 'buyer-otp-verify:'.$email;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many attempts. Please try again in a minute.'], 429);
        }
        RateLimiter::hit($key, 60);
        $record = OtpVerification::where('email', $email)
            ->where('is_verified', false)->where('expires_at', '>', now())->first();
        if (! $record || ! hash_equals((string) $record->otp_code, $request->string('otp_code')->value())) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }
        $expiresAt = now()->addMinutes(30);
        $record->update(['is_verified' => true, 'expires_at' => $expiresAt]);
        $request->session()->put('buyer_registration_verification', [
            'email' => $email, 'expires_at' => $expiresAt->timestamp,
            'record_id' => $record->id, 'code' => (string) $record->otp_code,
        ]);
        RateLimiter::clear($key);

        return response()->json(['message' => 'Email verified.', 'expires_at' => $expiresAt->timestamp]);
    }
}
