<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\CourierDetail;
use App\Models\User;
use App\Services\LocationCatalog;
use App\Services\LogisticsCenterMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RiderRegistrationController extends Controller
{
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

    public function store(Request $request, LocationCatalog $locations, LogisticsCenterMatcher $centers): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'birthday' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'max:30'],
            'province_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'city_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'barangay_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['required', Rule::in(['Motorcycle', 'Bicycle', 'Tricycle', 'Car'])],
            'plate_number' => ['required', 'string', 'max:50'],
            'valid_id' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'drivers_license' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'or_cr' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $address = $locations->address($data['province_code'], $data['city_code']);
        if (! $address) {
            throw ValidationException::withMessages(['city_code' => 'Select a city from the location list.']);
        }

        $barangay = $locations->barangayName($data['city_code'], $data['barangay_code']);
        if (! $barangay) {
            throw ValidationException::withMessages(['barangay_code' => 'Select a barangay from the location list.']);
        }

        $center = $centers->forAddress($address['province'], $address['city']);
        if (! $center) {
            throw ValidationException::withMessages(['city_code' => 'No unique approved logistics hub serves this address yet. Contact Vendo support.']);
        }

        $stored = [];
        try {
            $rider = DB::transaction(function () use ($data, $request, $address, $barangay, $center, &$stored) {
                foreach (['valid_id', 'drivers_license', 'or_cr'] as $field) {
                    $stored[$field] = $request->file($field)->store('private/rider-verification', 'local');
                }

                $rider = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'middle_initial' => isset($data['middle_name']) ? mb_substr($data['middle_name'], 0, 1) : null,
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'phone_number' => $data['phone_number'],
                    'role' => 'courier',
                    'status' => 'pending',
                    'account_status' => 'active',
                ]);

                CourierDetail::create([
                    'user_id' => $rider->id,
                    'logistics_center_id' => $center->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'sex' => $data['sex'],
                    'birthday' => $data['birthday'],
                    'province' => $address['province'],
                    'municipality' => $address['city'],
                    'barangay' => $barangay,
                    'street' => $data['street'],
                    'zip_code' => $data['zip_code'],
                    'vehicle_type' => $data['vehicle_type'],
                    'plate_number' => $data['plate_number'],
                    'valid_id_path' => $stored['valid_id'],
                    'drivers_license_path' => $stored['drivers_license'],
                    'or_cr_path' => $stored['or_cr'],
                ]);

                return $rider;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($stored));
            throw $exception;
        }

        return response()->json([
            'message' => 'Rider application submitted for logistics hub review.',
            'rider_id' => $rider->id,
            'status' => 'pending',
            'logistics_center' => ['id' => $center->id, 'name' => $center->business_name],
        ], 201);
    }
}
