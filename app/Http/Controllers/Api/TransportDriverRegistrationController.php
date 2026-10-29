<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransportDriverRegistrationController extends Controller
{
    public function hubs(): JsonResponse
    {
        $hubs = LogisticsFacility::query()->where('kind', 'province_hub')->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')->where('account_status', 'active')))
            ->with('company:id,business_name')->orderBy('province')->orderBy('name')->get()
            ->map(fn ($hub) => ['id' => $hub->id, 'name' => $hub->name, 'province' => $hub->province,
                'company' => $hub->company?->business_name]);

        return response()->json(['province_hubs' => $hubs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'email_verification_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'province_hub_id' => ['required', 'integer', 'exists:logistics_facilities,id'],
            'birthday' => ['required', 'date', 'before:today'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'home_address' => ['required', 'string', 'min:10', 'max:500'],
            'license_number' => ['required', 'string', 'max:80'],
            'license_category' => ['required', 'string', 'max:40'],
            'license_expires_at' => ['required', 'date', 'after:today'],
            'drivers_license' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'valid_id' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
        $email = strtolower(trim($data['email']));
        $proofKey = RiderEmailOtpController::proofKey('email', $data['email_verification_token']);
        $proof = Cache::get($proofKey);
        if (! is_array($proof) || ($proof['email'] ?? null) !== $email) {
            throw ValidationException::withMessages(['email' => 'Verify this email before applying.']);
        }
        $hub = LogisticsFacility::query()->whereKey($data['province_hub_id'])->where('kind', 'province_hub')
            ->where('is_active', true)->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')->where('account_status', 'active')))
            ->with('staff')->first();
        if (! $hub || ! $hub->staff?->user_id) {
            throw ValidationException::withMessages(['province_hub_id' => 'Choose a staffed, active Province Hub.']);
        }

        $stored = [];
        try {
            foreach (['drivers_license', 'valid_id'] as $field) {
                $stored[$field] = $request->file($field)->store('private/transport-drivers', 'local');
            }
            $driver = DB::transaction(function () use ($data, $email, $hub, $stored) {
                $user = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                    'email' => $email, 'password' => $data['password'],
                    'phone_number' => $data['phone_number'], 'role' => 'transport_driver',
                    'status' => 'pending', 'account_status' => 'active',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $driver = TransportDriverDetail::create([
                    'user_id' => $user->id, 'province_hub_id' => $hub->id,
                    'license_number' => $data['license_number'], 'license_category' => $data['license_category'],
                    'license_expires_at' => $data['license_expires_at'], 'birthday' => $data['birthday'],
                    'sex' => $data['sex'], 'home_address' => $data['home_address'],
                    'drivers_license_path' => $stored['drivers_license'], 'valid_id_path' => $stored['valid_id'],
                    'status' => 'pending',
                ]);
                Notification::create(['user_id' => $hub->staff->user_id, 'type' => 'logistics_driver_application',
                    'title' => 'New Transport Driver application',
                    'message' => $user->name.' applied to '.$hub->name.'.',
                    'link' => route('lgp.province.drivers')]);

                return $driver;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($stored));
            throw $exception;
        }
        Cache::forget($proofKey);

        return response()->json(['message' => 'Transport Driver application submitted for Province Hub review.',
            'driver_id' => $driver->id, 'status' => 'pending'], 201);
    }
}
