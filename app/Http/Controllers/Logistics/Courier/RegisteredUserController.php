<?php

namespace App\Http\Controllers\Logistics\Courier;

use App\Http\Controllers\Controller;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $logisticsCenters = LogisticsCenter::whereHas('user', fn ($q) => $q->where('status', 'approved'))
            ->orderBy('business_name')
            ->get();

        return view('auth.register-rider', compact('logisticsCenters'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'birthday' => ['required', 'date', 'before:today'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'logistics_center_id' => ['required', 'exists:logistics_centers,id'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'contact_number' => ['required', 'digits_between:10,15'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'vehicle_type' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:20'],
            'drivers_license' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'or_cr' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'agree_terms' => ['accepted'],
        ]);

        // Guard against a stale/tampered selection pointing at a center that
        // isn't (or is no longer) approved.
        $center = LogisticsCenter::whereHas('user', fn ($q) => $q->where('status', 'approved'))
            ->find($validated['logistics_center_id']);

        if (! $center) {
            throw ValidationException::withMessages([
                'logistics_center_id' => 'Please select a valid, approved Logistics/Sorting Center.',
            ]);
        }

        $email = strtolower($validated['email']);

        if (! Cache::pull('otp_verified:'.$email)) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before submitting.',
            ]);
        }

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $email,
            'phone_number' => $validated['contact_number'],
            'role' => 'courier',
            'password' => Hash::make($validated['password']),
        ]);

        $validIdPath = $request->file('valid_id')->store('valid-ids/courier', 'public');
        $driversLicensePath = $request->file('drivers_license')->store('drivers-licenses', 'public');
        $orCrPath = $request->file('or_cr')->store('or-cr', 'public');

        $user->courierDetail()->create([
            'logistics_center_id' => $center->id,
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
            'vehicle_type' => $validated['vehicle_type'],
            'plate_number' => $validated['plate_number'],
            'drivers_license_path' => $driversLicensePath,
            'or_cr_path' => $orCrPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Awaiting approval from your chosen Logistics/Sorting Center.',
        ]);
    }
}