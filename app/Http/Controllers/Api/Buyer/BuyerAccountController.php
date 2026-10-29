<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Profiles\BuyerDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Profile and password for the Buyer mobile app. Only the signed-in Buyer's own record is touched. */
class BuyerAccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['buyer' => $this->profile($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_no' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        $buyer = $request->user();
        $buyer->update(['name' => $data['name'], 'phone_number' => $data['phone_number'] ?? null]);

        BuyerDetail::query()->where('user_id', $buyer->id)->first()?->update(array_filter([
            'street' => $data['street'] ?? null,
            'house_no' => $data['house_no'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
        ], fn ($value) => $value !== null));

        return response()->json(['message' => 'Profile updated.', 'buyer' => $this->profile($request)]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    private function profile(Request $request): array
    {
        $buyer = $request->user();
        $detail = BuyerDetail::query()->where('user_id', $buyer->id)->first();

        return [
            'id' => $buyer->id,
            'name' => $buyer->name,
            'email' => $buyer->email,
            'phone_number' => $buyer->phone_number,
            'profile_picture' => $buyer->profile_picture ? asset('storage/'.$buyer->profile_picture) : null,
            'address' => $detail ? [
                'province' => $detail->province,
                'municipality' => $detail->municipality,
                'barangay' => $detail->barangay,
                'street' => $detail->street,
                'house_no' => $detail->house_no,
                'zip_code' => $detail->zip_code,
            ] : null,
        ];
    }
}
