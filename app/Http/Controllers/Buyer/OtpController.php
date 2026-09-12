<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\OtpVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otpCode = random_int(100000, 999999);

        OtpVerification::updateOrCreate(
            ['email' => $request->email],
            [
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
                'is_verified' => false,
            ]
        );

        Mail::to($request->email)->send(new OtpMail($otpCode));

        return response()->json(['message' => 'OTP sent.']);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6',
        ]);

        $record = OtpVerification::where('email', $request->email)
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $record->update(['is_verified' => true]);

        return response()->json(['message' => 'Email verified.']);
    }
}