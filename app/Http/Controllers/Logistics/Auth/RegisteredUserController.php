<?php

namespace App\Http\Controllers\Logistics\Auth;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use App\Services\LocationCatalog;
use App\Services\PsgcAddressResolver;
use Illuminate\Database\UniqueConstraintViolationException;
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
    public function create(Request $request, LocationCatalog $locations): View
    {
        $verification = $request->session()->get('registration_verification', []);
        $google = $request->session()->get('google_registration_verification', []);
        $googleVerified = ($google['role'] ?? null) === 'logistics_center'
            && ($google['email'] ?? null) === ($verification['email'] ?? null)
            && ($google['expires_at'] ?? 0) > now()->timestamp
            && Cache::get('otp_verified:'.($google['email'] ?? ''), false);

        return view('logistics.auth.register-center', [
            'googleRegistration' => $googleVerified ? $google : null,
            'claimedCoverageCodes' => [],
            'excludedProvinceCodes' => [],
        ]);
    }

    public function store(Request $request, LocationCatalog $locations, PsgcAddressResolver $addresses): JsonResponse
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'birthday' => ['required', 'date', 'before:today'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'agree_terms' => ['accepted'],
        ]);

        $validated = array_merge($validated, $addresses->resolve($validated));

        $email = strtolower($validated['email']);
        $coverageProvinceCode = $locations->provinceCodeForName($validated['province']);
        if (! $coverageProvinceCode) {
            throw ValidationException::withMessages(['province' => 'Select a valid province for the company address.']);
        }
        $province = $locations->all()[$coverageProvinceCode]['name'];
        $mainHubCodes = $locations->codesForNames($validated['province'], $validated['municipality']);
        if (! $mainHubCodes || (string) $mainHubCodes[0] !== $coverageProvinceCode) {
            throw ValidationException::withMessages(['municipality' => 'Select a municipality inside the selected province.']);
        }

        $googleProof = $request->session()->get('google_registration_verification', []);
        if (($googleProof['email'] ?? null) === $email && ($googleProof['role'] ?? null) !== 'logistics_center') {
            throw ValidationException::withMessages(['email' => 'Continue registration using the account type chosen in Google sign-in.']);
        }

        if (! Cache::get('otp_verified:'.$email)) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before submitting.',
            ]);
        }

        $stored = [];
        try {
            $stored['business_permit'] = $request->file('business_permit')->store('business-permits', 'local');
            $stored['valid_id'] = $request->file('valid_id')->store('valid-ids/logistics-center', 'local');
            DB::transaction(function () use ($validated, $email, $province, &$stored) {
                $user = User::create([
                    'name' => trim($validated['first_name'].' '.$validated['last_name']),
                    'email' => $email,
                    'phone_number' => $validated['contact_number'],
                    'role' => 'logistics_center',
                    'status' => 'pending',
                    'password' => Hash::make($validated['password']),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                $center = $user->logisticsCenterDetail()->create([
                    'last_name' => $validated['last_name'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_initial'] ?? null,
                    'sex' => $validated['sex'],
                    'birthday' => $validated['birthday'],
                    'valid_id_path' => $stored['valid_id'],
                    'province' => $validated['province'],
                    'municipality' => $validated['municipality'],
                    'barangay' => $validated['barangay'],
                    'street' => $validated['street'],
                    'zip_code' => $validated['zip_code'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'business_name' => $validated['business_name'],
                    'business_permit_path' => $stored['business_permit'],
                    'coverage_province_code' => null,
                    'coverage_province' => null,
                ]);
                $center->company()->create([
                    'business_name' => $validated['business_name'],
                    'status' => 'pending',
                ]);

                Notification::create([
                    'user_id' => null,
                    'type' => 'new_logistics_registration',
                    'title' => 'New Logistics Company registration',
                    'message' => 'A Logistics Company account is awaiting review.',
                    'link' => route('admin.registrations.index', ['user_type' => 'logistics_center']),
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            Storage::disk('local')->delete(array_values($stored));
            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($stored));
            throw $exception;
        }

        Cache::forget('otp_verified:'.$email);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Awaiting admin approval.',
        ]);
    }
}
