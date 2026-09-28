<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\Profiles\BuyerDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredBuyerController extends Controller
{
    public function create(Request $request)
    {
        $proof = $request->session()->get('buyer_registration_verification', []);
        $googleProof = $request->session()->get('google_registration_verification', []);
        $googleVerified = ($googleProof['role'] ?? null) === 'buyer'
            && filter_var($googleProof['email'] ?? null, FILTER_VALIDATE_EMAIL)
            && ($googleProof['expires_at'] ?? 0) > now()->timestamp;
        $email = $googleVerified ? $googleProof['email'] : ($proof['email'] ?? '');
        $valid = $googleVerified || $this->verifiedRecord($request, $email) !== null;

        return view('buyer.auth.register', ['registrationVerification' => [
            'email' => $valid ? $email : '',
            'expires_at' => $valid ? ($googleVerified ? $googleProof['expires_at'] : $proof['expires_at']) : 0,
            'google_verified' => $valid && $googleVerified,
            'first_name' => $googleVerified ? ($googleProof['first_name'] ?? '') : '',
            'last_name' => $googleVerified ? ($googleProof['last_name'] ?? '') : '',
        ]]);
    }

    private function verifiedRecord(Request $request, string $email): ?OtpVerification
    {
        $proof = $request->session()->get('buyer_registration_verification', []);
        if (! $email || ($proof['email'] ?? null) !== $email || ($proof['expires_at'] ?? 0) <= now()->timestamp) {
            return null;
        }

        return OtpVerification::whereKey($proof['record_id'] ?? 0)
            ->where('email', $email)->where('otp_code', $proof['code'] ?? '')
            ->where('is_verified', true)->where('expires_at', '>', now())->first();
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim($request->string('email')->value()))]);
        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:255',
            'sex' => 'required|in:male,female,prefer_not_to_say',
            'email' => 'required|string|email|max:255|unique:users,email',
            'birthday' => 'required|date|before:today',
            'id_category' => 'required|in:primary,secondary',
            'id_type' => 'exclude_unless:id_category,primary|required|string|max:255',
            'id_type_1' => 'exclude_unless:id_category,secondary|required|string|max:255',
            'id_type_2' => 'exclude_unless:id_category,secondary|required|string|max:255',
            'valid_id_2' => 'exclude_unless:id_category,secondary|required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'valid_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'province' => 'required|string',
            'municipality' => 'required|string',
            'barangay' => 'required|string',
            'street' => 'required|string|max:255',
            'zip_code' => 'required|string',
            'contact_number' => 'required|string',
            'agree_terms' => 'accepted',
        ]);

        $googleProof = $request->session()->get('google_registration_verification', []);
        $googleVerified = ($googleProof['role'] ?? null) === 'buyer'
            && ($googleProof['email'] ?? null) === $request->email
            && ($googleProof['expires_at'] ?? 0) > now()->timestamp;
        $otp = $googleVerified ? null : $this->verifiedRecord($request, $request->email);
        if (! $googleVerified && ! $otp) {
            throw ValidationException::withMessages(['email' => 'Please verify your email first.']);
        }

        $paths = [];
        try {
            $validIdPath = $request->file('valid_id')->store('valid-ids', 'local');
            $paths[] = $validIdPath;
            $secondIdPath = null;
            if ($request->id_category === 'secondary') {
                $secondIdPath = $request->file('valid_id_2')->store('valid-ids', 'local');
                $paths[] = $secondIdPath;
            }
            DB::transaction(function () use ($request, $validIdPath, $secondIdPath, $otp) {
                $user = User::create([
                    'name' => $request->first_name.' '.$request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'buyer',
                    'status' => 'pending',
                    'phone_number' => $request->contact_number,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                BuyerDetail::create([
                    'user_id' => $user->id,
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_initial,
                    'sex' => $request->sex,
                    'birthday' => $request->birthday,
                    'valid_id_path' => $validIdPath,
                    'id_type' => $request->id_category,
                    'valid_id_path_2' => $secondIdPath,
                    'province' => $request->province,
                    'municipality' => $request->municipality,
                    'barangay' => $request->barangay,
                    'house_no' => null,
                    'street' => $request->street,
                    'zip_code' => $request->zip_code,
                ]);

                $otp?->delete();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
        $request->session()->forget('buyer_registration_verification');
        $request->session()->forget('google_registration_verification');

        return response()->json(['success' => true, 'message' => 'Registration submitted.']);
    }
}
