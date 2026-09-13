<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Profiles\BuyerDetail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredBuyerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:255',
            'sex' => 'required|in:male,female,prefer_not_to_say',
            'email' => 'required|string|email|max:255|unique:users,email',
            'birthday' => 'required|date',
            'valid_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'province' => 'required|string',
            'municipality' => 'required|string',
            'barangay' => 'required|string',
            'house_no' => 'required|string',
            'street' => 'required|string',
            'zip_code' => 'required|string',
            'contact_number' => 'required|string',
            'agree_terms' => 'accepted',
        ]);

        $otp = OtpVerification::where('email', $request->email)
            ->where('is_verified', true)
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Please verify your email first.'], 422);
        }

        $validIdPath = $request->file('valid_id')->store('valid-ids', 'public');

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'buyer',
            'status' => 'pending',
            'phone_number' => $request->contact_number,
        ]);

        BuyerDetail::create([
            'user_id' => $user->id,
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_initial,
            'sex' => $request->sex,
            'birthday' => $request->birthday,
            'valid_id_path' => $validIdPath,
            'province' => $request->province,
            'municipality' => $request->municipality,
            'barangay' => $request->barangay,
            'house_no' => $request->house_no,
            'street' => $request->street,
            'zip_code' => $request->zip_code,
        ]);

        return response()->json(['success' => true, 'message' => 'Registration submitted.']);
    }
}