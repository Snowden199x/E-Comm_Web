<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\CourierDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Profile and password for the Rider mobile app.
 *
 * The Rider app reads the signed-in Rider's own approved record so the
 * Personal Information and Vehicle Details screens show what the Station Hub
 * actually approved. Only the authenticated Rider's record is ever returned.
 */
class RiderProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['rider' => $this->profile($request)]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $rider = $request->user();

        if (! Hash::check($data['current_password'], $rider->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $rider->update(['password' => $data['password']]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * The Rider can edit their full name and birthday.
     *
     * Contact details, vehicle, documents, service areas, and the Station Hub
     * assignment stay Station-Hub managed.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthday' => ['required', 'date', 'before:today'],
        ]);

        $rider = $request->user();
        $detail = CourierDetail::query()->where('user_id', $rider->id)->first();

        if (! $detail) {
            throw ValidationException::withMessages([
                'birthday' => 'No rider record is on file. Contact your Station Hub.',
            ]);
        }

        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $middleInitial = $middleName === '' ? null : mb_substr($middleName, 0, 1);

        $rider->update([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_initial' => $middleInitial,
        ]);

        $detail->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $middleName === '' ? null : $middleName,
            'birthday' => $data['birthday'],
        ]);

        return response()->json([
            'message' => 'Personal information updated.',
            'rider' => $this->profile($request),
        ]);
    }

    /**
     * Replaces the Rider's account photo on the public profile disk. This is
     * separate from the private licence and OR/CR verification documents.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $rider = $request->user();
        $old = $rider->profile_picture;
        $path = $request->file('photo')->store('profile-pictures/riders', 'public');

        if (! $path) {
            return response()->json(['message' => 'The profile picture could not be stored.'], 500);
        }

        $rider->update(['profile_picture' => $path]);

        if ($old && str_starts_with($old, 'profile-pictures/riders/')) {
            Storage::disk('public')->delete($old);
        }

        return response()->json([
            'message' => 'Profile picture updated.',
            'rider' => $this->profile($request),
        ]);
    }

    private function profile(Request $request): array
    {
        $rider = $request->user();
        $detail = CourierDetail::query()
            ->with(['logisticsCenter', 'stationHub', 'serviceAreas'])
            ->where('user_id', $rider->id)
            ->first();

        return [
            'id' => $rider->id,
            'name' => $rider->name,
            'first_name' => $rider->first_name ?: $detail?->first_name,
            'middle_initial' => $rider->middle_initial
                ?: ($detail?->middle_name ? mb_substr($detail->middle_name, 0, 1) : null),
            'last_name' => $rider->last_name ?: $detail?->last_name,
            'email' => $rider->email,
            'phone_number' => $rider->phone_number,
            'profile_picture' => $rider->profile_picture
                ? asset('storage/'.$rider->profile_picture)
                : null,
            'status' => $rider->status,
            'account_status' => $rider->account_status,
            'joined_at' => $rider->created_at?->toIso8601String(),
            'details' => $detail ? [
                'sex' => $detail->sex,
                'birthday' => $detail->birthday?->toDateString(),
                'age' => $detail->birthday?->age,
                'address' => [
                    'street' => $detail->street,
                    'barangay' => $detail->barangay,
                    'municipality' => $detail->municipality,
                    'province' => $detail->province,
                    'zip_code' => $detail->zip_code,
                ],
            ] : null,
            'vehicle' => [
                'type' => $detail?->vehicle_type,
                'plate_number' => $detail?->plate_number,
            ],
            // The app never receives the document images, only whether the
            // private copies are on file.
            'documents' => [
                'drivers_license' => (bool) $detail?->drivers_license_path,
                'or_cr' => (bool) $detail?->or_cr_path,
            ],
            'logistics_center' => $detail?->logisticsCenter ? [
                'id' => $detail->logisticsCenter->id,
                'name' => $detail->logisticsCenter->business_name,
            ] : null,
            'station_hub' => $detail?->stationHub ? [
                'id' => $detail->stationHub->id,
                'name' => $detail->stationHub->name,
            ] : null,
            'service_areas' => $detail
                ? $detail->serviceAreas->pluck('barangay')->filter()->values()->all()
                : [],
        ];
    }
}
