<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Communication\Notification;
use App\Services\PsgcAddressResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $categories = Category::whereNull('parent_id')->orderBy('id')->get();
        $google = $request->session()->get('google_registration_verification', []);
        $googleRole = $google['role'] ?? null;
        $googleProofValid = in_array($googleRole, ['seller', null], true)
            && filter_var($google['email'] ?? null, FILTER_VALIDATE_EMAIL)
            && ($google['expires_at'] ?? 0) > now()->timestamp;

        if ($googleProofValid && $googleRole === null) {
            $this->adoptSharedProof($request, $google);
        }

        $verification = $request->session()->get('registration_verification', []);
        $googleVerified = $googleProofValid
            && ($google['email'] ?? null) === ($verification['email'] ?? null)
            && Cache::get('otp_verified:'.($google['email'] ?? ''), false);

        return view('seller.auth.register', [
            'categories' => $categories,
            'googleRegistration' => $googleVerified ? $google : null,
        ]);
    }

    /**
     * The shared login page verifies the email before the account type is chosen.
     * Adopt that proof into the OTP-verified state the rest of the Seller pipeline
     * already reads, so the form opens with the address filled in, locked, and
     * verified instead of asking for a code.
     */
    private function adoptSharedProof(Request $request, array $proof): void
    {
        $request->session()->put('registration_verification', [
            'email' => $proof['email'],
            'expires_at' => $proof['expires_at'],
        ]);
        Cache::put('otp_verified:'.$proof['email'], true, now()->setTimestamp((int) $proof['expires_at']));
    }

    public function store(Request $request, PsgcAddressResolver $addresses): JsonResponse
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'birthday' => ['required', 'date', 'before:today'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'business_name' => ['required', 'string', 'max:255'],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['exists:categories,id'],
            'agree_terms' => ['accepted'],
        ], [
            'valid_id.max' => 'The ID file must be 10MB or smaller.',
            'valid_id.mimes' => 'The ID file must be a JPEG, PNG, or PDF.',
            'valid_id.uploaded' => 'The ID file could not be uploaded. Choose a file under 10MB and submit again.',
            'business_permit.max' => 'The business permit must be 10MB or smaller.',
            'business_permit.mimes' => 'The business permit must be a JPEG, PNG, or PDF.',
            'business_permit.uploaded' => 'The business permit could not be uploaded. Choose a file under 10MB and submit again.',
        ]);
        $email = strtolower($validated['email']);

        $verification = $request->session()->get('registration_verification', []);
        $googleProof = $request->session()->get('google_registration_verification', []);
        $googleRole = $googleProof['role'] ?? null;
        $googleVerified = in_array($googleRole, ['seller', null], true)
            && ($googleProof['email'] ?? null) === $email
            && ($googleProof['expires_at'] ?? 0) > now()->timestamp;

        if (($googleProof['email'] ?? null) === $email && $googleRole !== null && $googleRole !== 'seller') {
            throw ValidationException::withMessages(['email' => 'Continue registration using the account type chosen in Google sign-in.']);
        }

        $otpVerified = ($verification['email'] ?? null) === $email
            && ($verification['expires_at'] ?? 0) > now()->timestamp
            && Cache::get('otp_verified:'.$email);

        if (! $googleVerified && ! $otpVerified) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before submitting.',
            ]);
        }

        $validated = array_merge($validated, $addresses->resolve($validated));

        // The account and its documents are one registration: a failed upload
        // must not leave a seller row that then blocks a second attempt on the
        // same email. Stored files are removed if the transaction rolls back.
        $paths = [];
        try {
            DB::transaction(function () use ($request, $validated, $email, &$paths) {
                $user = User::create([
                    'name' => trim($validated['first_name'].' '.$validated['last_name']),
                    'email' => $email,
                    'phone_number' => $validated['contact_number'],
                    'role' => 'seller',
                    'status' => 'pending',
                    'password' => Hash::make($validated['password']),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                $businessPermitPath = $request->file('business_permit')->store('business-permits', 'local');
                $paths[] = $businessPermitPath;
                $validIdPath = $request->file('valid_id')->store('valid-ids/seller', 'local');
                $paths[] = $validIdPath;

                $user->sellerDetail()->create([
                    'last_name' => $validated['last_name'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_initial'] ?? null,
                    'sex' => $validated['sex'],
                    'birthday' => $validated['birthday'],
                    'valid_id_path' => $validIdPath,
                    'province' => $validated['province'],
                    'municipality' => $validated['municipality'],
                    'barangay' => $validated['barangay'],
                    'street' => $validated['street'],
                    'zip_code' => $validated['zip_code'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'business_name' => $validated['business_name'],
                    'business_permit_path' => $businessPermitPath,
                ]);

                $user->categories()->sync($validated['categories']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        Cache::forget('otp_verified:'.$email);
        $request->session()->forget('registration_verification');
        $request->session()->forget('google_registration_verification');
        Notification::create(['user_id' => null, 'type' => 'new_seller_registration', 'title' => 'New seller registration', 'message' => 'A seller account is awaiting review.', 'link' => route('admin.registrations.index')]);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Awaiting admin approval.',
        ]);
    }
}
