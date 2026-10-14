<?php

namespace App\Http\Controllers\Logistics\Auth;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use App\Services\LocationCatalog;
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

        $claimedCodes = LogisticsCenter::query()->whereHas('user', fn ($query) => $query
            ->where('role', 'logistics_center')->whereIn('status', ['pending', 'approved']))
            ->get(['province', 'coverage_province_code'])
            ->map(fn ($center) => $center->coverage_province_code ?: $locations->provinceCodeForName($center->province))
            ->filter()->all();

        return view('logistics.auth.register-center', [
            'googleRegistration' => $googleVerified ? $google : null,
            'coverageProvinces' => $locations->all(),
            'claimedCoverageCodes' => $claimedCodes,
        ]);
    }

    public function store(Request $request, LocationCatalog $locations): JsonResponse
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'birthday' => ['required', 'date', 'before:today'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'contact_number' => ['required', 'digits_between:10,15'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'business_name' => ['required', 'string', 'max:255'],
            'coverage_province_code' => ['required', 'regex:/^[0-9]{9}$/', Rule::in(array_keys($locations->all()))],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'agree_terms' => ['accepted'],
        ]);

        $email = strtolower($validated['email']);
        $province = $locations->all()[$validated['coverage_province_code']]['name'];
        if ($locations->normalize($validated['province']) !== $locations->normalize($province)) {
            throw ValidationException::withMessages(['coverage_province_code' => 'The Main Hub address must be inside the province this account covers.']);
        }
        $mainHubCodes = $locations->codesForNames($validated['province'], $validated['municipality']);
        if (! $mainHubCodes || (string) $mainHubCodes[0] !== $validated['coverage_province_code']) {
            throw ValidationException::withMessages(['municipality' => 'Select a Main Hub municipality within the covered province.']);
        }
        $alreadyClaimed = LogisticsCenter::query()->whereHas('user', fn ($query) => $query
            ->where('role', 'logistics_center')->whereIn('status', ['pending', 'approved']))
            ->get(['province', 'coverage_province_code'])
            ->contains(fn ($center) => ($center->coverage_province_code ?: $locations->provinceCodeForName($center->province)) === $validated['coverage_province_code']);
        if ($alreadyClaimed) {
            throw ValidationException::withMessages(['coverage_province_code' => 'This province already has a Logistics account or an application awaiting review.']);
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
            DB::transaction(function () use ($validated, $email, $province, $mainHubCodes, &$stored) {
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
                    'business_name' => $validated['business_name'],
                    'business_permit_path' => $stored['business_permit'],
                    'coverage_province_code' => $validated['coverage_province_code'],
                    'coverage_province' => $province,
                ]);
                $center->facilities()->create([
                    'kind' => 'main_hub', 'main_hub_owner_id' => $center->id,
                    'name' => $validated['business_name'].' Main Hub',
                    'province_code' => $validated['coverage_province_code'], 'province' => $province,
                    'municipality_code' => $mainHubCodes[1],
                    'municipality' => $validated['municipality'],
                    'address' => implode(', ', array_filter([$validated['street'], $validated['barangay']])),
                    'is_active' => true,
                ]);

                Notification::create([
                    'user_id' => null,
                    'type' => 'new_logistics_registration',
                    'title' => 'New Logistics Center registration',
                    'message' => 'A Logistics Center account is awaiting review.',
                    'link' => route('admin.registrations.index', ['user_type' => 'logistics_center']),
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            Storage::disk('local')->delete(array_values($stored));
            if (! str_contains($exception->getMessage(), 'lc_coverage_province_unique')) {
                throw $exception;
            }
            throw ValidationException::withMessages(['coverage_province_code' => 'This province was just claimed. Choose an available province.']);
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
