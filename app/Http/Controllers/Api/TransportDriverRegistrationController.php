<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use App\Services\LocationCatalog;
use App\Services\StationHubResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransportDriverRegistrationController extends Controller
{
    /**
     * Where a driver can apply. A station-only driver is reviewed by the
     * Station Hub; province and both drivers are reviewed by the Province Hub.
     */
    public function hubs(): JsonResponse
    {
        $networkIsOpen = fn ($query) => $query->where('status', 'approved')
            ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')->where('account_status', 'active'));

        $provinceHubs = LogisticsFacility::query()->where('kind', 'province_hub')->where('is_active', true)
            ->whereHas('company', $networkIsOpen)
            ->with('company:id,business_name')->orderBy('province')->orderBy('name')->get()
            ->map(fn ($hub) => ['id' => $hub->id, 'name' => $hub->name, 'province' => $hub->province,
                'company' => $hub->company?->business_name]);

        $stationHubs = LogisticsFacility::query()->where('kind', 'station_hub')->where('is_active', true)
            ->whereHas('company', $networkIsOpen)
            ->with(['parent:id,name', 'company:id,business_name'])
            ->orderBy('municipality')->orderBy('name')->get()
            ->map(fn ($station) => [
                'id' => $station->id, 'name' => $station->name,
                'province' => $station->province, 'municipality' => $station->municipality,
                'municipality_code' => $station->municipality_code,
                'province_hub_id' => $station->parent_id, 'province_hub' => $station->parent?->name,
                'company' => $station->company?->business_name,
                'staffed' => $station->staff()->whereHas('user', fn ($user) => $user->where('status', 'approved')
                    ->where('account_status', 'active'))->exists(),
            ]);

        return response()->json([
            'province_hubs' => $provinceHubs,
            'station_hubs' => $stationHubs,
            'apply_to' => [
                ['value' => TransportDriverDetail::APPLY_STATION, 'label' => 'Station Hub only',
                    'reviewed_by' => 'Station Hub', 'description' => 'You move parcels between Station Hubs. The Station Hub you choose approves your application.'],
                ['value' => TransportDriverDetail::APPLY_PROVINCE, 'label' => 'Province Hub only',
                    'reviewed_by' => 'Province Hub', 'description' => 'You drive hub to hub trips. The Province Hub approves, and you are linked to the Station Hub where you live.'],
                ['value' => TransportDriverDetail::APPLY_BOTH, 'label' => 'Both',
                    'reviewed_by' => 'Province Hub', 'description' => 'Only the Province Hub approves. Your Station Hub can still see your details and where you live.'],
            ],
        ]);
    }

    public function locations(LocationCatalog $locations): JsonResponse
    {
        return response()->json(['provinces' => $locations->all()]);
    }

    public function barangays(LocationCatalog $locations, string $cityCode): JsonResponse
    {
        $barangays = $locations->barangaysForCity($cityCode);
        abort_if($barangays === null, 404, 'City not found in the location catalog.');

        return response()->json(['barangays' => $barangays]);
    }

    public function store(Request $request, LocationCatalog $locations, StationHubResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'email_verification_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'apply_to' => ['nullable', Rule::in(TransportDriverDetail::APPLY_SCOPES)],
            'province_hub_id' => ['nullable', 'integer', 'exists:logistics_facilities,id'],
            'station_hub_id' => ['nullable', 'integer', 'exists:logistics_facilities,id'],
            'province_code' => ['required', 'string', 'max:12'],
            'city_code' => ['required', 'string', 'max:12'],
            'barangay_code' => ['nullable', 'string', 'max:12'],
            'street' => ['required', 'string', 'min:4', 'max:200'],
            'birthday' => ['required', 'date', 'before:today'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'home_address' => ['nullable', 'string', 'max:500'],
            'vehicle_type' => ['nullable', 'string', 'max:40'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'license_number' => ['required', 'string', 'max:80'],
            'license_category' => ['required', 'string', 'max:40'],
            'license_expires_at' => ['required', 'date', 'after:today'],
            'drivers_license' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            // The Driver app no longer asks for a separate Valid ID: the driver's
            // licence is the identity document. Older app builds may still send one.
            'valid_id' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $applyTo = $data['apply_to'] ?? TransportDriverDetail::APPLY_PROVINCE;
        $address = $locations->address($data['province_code'], $data['city_code']);
        if (! $address) {
            throw ValidationException::withMessages(['city_code' => 'Choose a province and municipality from the list.']);
        }

        // A station-only driver chooses its Station Hub. Everyone else is linked
        // to the Station Hub where they live, or the nearest one.
        $station = null;
        if ($applyTo === TransportDriverDetail::APPLY_STATION) {
            if (empty($data['station_hub_id'])) {
                throw ValidationException::withMessages(['station_hub_id' => 'Choose the Station Hub you are applying to.']);
            }
            $station = $resolver->station((int) $data['station_hub_id']);
            if (! $station || ! $station->parent || ! $station->parent->isProvinceHub()) {
                throw ValidationException::withMessages(['station_hub_id' => 'Choose an active Station Hub inside a Province Hub network.']);
            }
            $provinceHubId = (int) $station->parent_id;
        } else {
            if (empty($data['province_hub_id'])) {
                throw ValidationException::withMessages(['province_hub_id' => 'Choose the Province Hub you are applying to.']);
            }
            $provinceHubId = (int) $data['province_hub_id'];
            $station = $resolver->resolve([
                'province_hub_id' => $provinceHubId,
                'province_code' => $data['province_code'],
                'city_code' => $data['city_code'],
            ]);
        }

        $hub = LogisticsFacility::query()->whereKey($provinceHubId)->where('kind', 'province_hub')
            ->where('is_active', true)->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')->where('account_status', 'active')))
            ->with('staff')->first();
        if (! $hub) {
            throw ValidationException::withMessages(['province_hub_id' => 'Choose an active Province Hub.']);
        }

        $reviewer = $applyTo === TransportDriverDetail::APPLY_STATION
            ? $station->staff()->whereHas('user', fn ($query) => $query->where('status', 'approved')->where('account_status', 'active'))->first()
            : $hub->staff;
        if (! $reviewer?->user_id) {
            throw ValidationException::withMessages(['apply_to' => $applyTo === TransportDriverDetail::APPLY_STATION
                ? 'That Station Hub has no manager account yet. Choose another Station Hub.'
                : 'That Province Hub has no manager account yet. Choose another Province Hub.']);
        }

        $email = strtolower(trim($data['email']));
        $proofKey = RiderEmailOtpController::proofKey('email', $data['email_verification_token']);
        $proof = Cache::get($proofKey);
        if (! is_array($proof) || ($proof['email'] ?? null) !== $email) {
            throw ValidationException::withMessages(['email' => 'Verify this email before applying.']);
        }

        $homeAddress = trim((string) ($data['home_address'] ?? ''));
        if ($homeAddress === '') {
            $barangay = $data['barangay_code'] ? ($locations->barangayName($data['city_code'], $data['barangay_code']) ?? '') : '';
            $homeAddress = collect([$data['street'], $barangay, $address['city'], $address['province']])
                ->filter(fn ($part) => trim((string) $part) !== '')->implode(', ');
        }

        $stored = [];
        try {
            foreach (['drivers_license', 'valid_id'] as $field) {
                if (! $request->hasFile($field)) {
                    continue;
                }
                $stored[$field] = $request->file($field)->store('private/transport-drivers', 'local');
            }
            $driver = DB::transaction(function () use ($data, $email, $hub, $station, $applyTo, $homeAddress, $reviewer, $stored) {
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
                    'apply_to' => $applyTo, 'station_hub_id' => $station?->id,
                    'license_number' => $data['license_number'], 'license_category' => $data['license_category'],
                    'license_expires_at' => $data['license_expires_at'], 'birthday' => $data['birthday'],
                    'sex' => $data['sex'], 'home_address' => $homeAddress,
                    'vehicle_type' => $data['vehicle_type'] ?? null, 'plate_number' => $data['plate_number'] ?? null,
                    'drivers_license_path' => $stored['drivers_license'], 'valid_id_path' => $stored['valid_id'] ?? $stored['drivers_license'],
                    'status' => 'pending',
                ]);

                $appliesToStationOnly = $applyTo === TransportDriverDetail::APPLY_STATION;
                Notification::create([
                    'user_id' => $reviewer->user_id,
                    'type' => 'logistics_driver_application',
                    'title' => 'New Transport Driver application',
                    'message' => $user->name.' applied to '.($appliesToStationOnly ? $station->name : $hub->name)
                        .($appliesToStationOnly ? '.' : ' ('.$driver->applyLabel().').'),
                    'link' => $appliesToStationOnly ? route('lgp.station.drivers') : route('lgp.province.drivers'),
                ]);

                // "Both" drivers are approved by the Province Hub only. The
                // Station Hub still gets a read-only heads-up.
                if ($applyTo === TransportDriverDetail::APPLY_BOTH && $station) {
                    $stationStaff = $station->staff()->whereHas('user', fn ($query) => $query->where('status', 'approved')
                        ->where('account_status', 'active'))->first();
                    if ($stationStaff?->user_id && (int) $stationStaff->user_id !== (int) $reviewer->user_id) {
                        Notification::create([
                            'user_id' => $stationStaff->user_id,
                            'type' => 'logistics_driver_assigned_readonly',
                            'title' => 'Driver linked to your Station Hub',
                            'message' => $user->name.' applied to both hubs and is linked to '.$station->name
                                .'. The Province Hub reviews this application; no action is needed here.',
                            'link' => route('lgp.station.drivers'),
                        ]);
                    }
                }

                return $driver;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($stored));
            throw $exception;
        }
        Cache::forget($proofKey);

        $driver->load(['stationHub:id,name', 'provinceHub:id,name']);

        return response()->json([
            'message' => $applyTo === TransportDriverDetail::APPLY_STATION
                ? 'Application submitted to '.$driver->stationHub?->name.' for review.'
                : 'Application submitted to '.$driver->provinceHub?->name.' for review.',
            'driver_id' => $driver->id,
            'status' => 'pending',
            'apply_to' => $driver->apply_to,
            'apply_label' => $driver->applyLabel(),
            'reviewing_hub' => [
                'kind' => $applyTo === TransportDriverDetail::APPLY_STATION ? 'station_hub' : 'province_hub',
                'name' => $applyTo === TransportDriverDetail::APPLY_STATION ? $driver->stationHub?->name : $driver->provinceHub?->name,
            ],
            'station_hub' => $driver->stationHub ? ['id' => $driver->stationHub->id, 'name' => $driver->stationHub->name] : null,
            'station_assignment' => $applyTo === TransportDriverDetail::APPLY_STATION ? 'chosen' : ($driver->stationHub ? 'nearest' : 'none'),
        ], 201);
    }

    /**
     * The applicant checks the decision from the app before signing in.
     * Only the application state is returned, and only for this email.
     */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::query()->where('email', strtolower(trim($data['email'])))
            ->where('role', 'transport_driver')->first();

        if (! $user) {
            return response()->json(['application' => null, 'message' => 'No Transport Driver application was found for this email.'], 404);
        }

        $driver = TransportDriverDetail::query()->where('user_id', $user->id)
            ->with(['stationHub:id,name', 'provinceHub:id,name', 'approvedBy:id,name'])->first();

        if (! $driver) {
            return response()->json(['application' => null, 'message' => 'No Transport Driver application was found for this email.'], 404);
        }

        $decision = match (true) {
            $user->status === 'approved' => 'approved',
            $user->status === 'disapproved' => 'rejected',
            default => 'pending',
        };

        return response()->json([
            'application' => [
                'status' => $decision,
                'driver_status' => $driver->status,
                'apply_to' => $driver->apply_to,
                'apply_label' => $driver->applyLabel(),
                'reviewing_hub' => $driver->reviewingHub() ? [
                    'kind' => $driver->worksStationToStation() ? 'station_hub' : 'province_hub',
                    'name' => $driver->reviewingHub()->name,
                ] : null,
                'station_hub' => $driver->stationHub ? ['id' => $driver->stationHub->id, 'name' => $driver->stationHub->name] : null,
                'province_hub' => $driver->provinceHub ? ['id' => $driver->provinceHub->id, 'name' => $driver->provinceHub->name] : null,
                'approved_by' => $driver->approvedBy?->name,
                'approved_at' => $driver->approved_at?->toIso8601String(),
                'rejection_reason' => $decision === 'rejected' ? $user->rejection_reason : null,
                'applied_at' => $driver->created_at?->toIso8601String(),
            ],
        ]);
    }
}
