<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Profiles\BuyerDetail;
use App\Models\User;
use App\Services\LocationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * Buyer mobile registration. Same fields and rules as the web form, but the email proof is the
 * cache token issued by BuyerEmailOtpController instead of a session record.
 */
class BuyerRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
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
            'verification_token' => 'required|string',
        ]);

        // The app picks the address from /locations, so the names must still exist in the same
        // PSGC snapshot: a municipality has to belong to its province and a barangay to its city.
        $locations = app(LocationCatalog::class);
        $codes = $locations->codesForNames($request->province, $request->municipality);
        if ($codes === null) {
            throw ValidationException::withMessages([
                'municipality' => 'Choose the province and municipality or city from the list.',
            ]);
        }

        $barangays = $locations->barangaysForCity((string) $codes[1]) ?? [];
        $barangayMatches = collect($barangays)->contains(
            fn (string $name) => $locations->normalize($name) === $locations->normalize($request->barangay)
        );
        if (! $barangayMatches) {
            throw ValidationException::withMessages([
                'barangay' => 'Choose a barangay that belongs to the selected municipality or city.',
            ]);
        }

        $proof = Cache::get(BuyerEmailOtpController::proofKey((string) $request->input('verification_token')));
        if (! is_array($proof) || ($proof['email'] ?? null) !== $request->email) {
            throw ValidationException::withMessages(['verification_token' => 'Your email verification expired. Verify your email again.']);
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

            $user = DB::transaction(function () use ($request, $validIdPath, $secondIdPath) {
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

                return $user;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        Cache::forget(BuyerEmailOtpController::proofKey((string) $request->input('verification_token')));

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. An admin reviews new buyer accounts before you can sign in.',
            'buyer' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'status' => $user->status],
        ], 201);
    }
}
