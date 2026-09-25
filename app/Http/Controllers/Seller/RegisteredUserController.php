<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Communication\Notification;
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
        $categories = Category::whereNull('parent_id')->orderBy('id')->get();

        return view('seller.auth.register', compact('categories'));
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
            'contact_number' => ['required', 'digits_between:10,15'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'business_name' => ['required', 'string', 'max:255'],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['exists:categories,id'],
            'agree_terms' => ['accepted'],
        ]);

        $email = strtolower($validated['email']);

        $verification = $request->session()->get('registration_verification', []);

        if (
            ($verification['email'] ?? null) !== $email ||
            ($verification['expires_at'] ?? 0) <= now()->timestamp ||
            ! Cache::get('otp_verified:'.$email)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before submitting.',
            ]);
        }

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $email,
            'phone_number' => $validated['contact_number'],
            'role' => 'seller',
            'status' => 'pending',
            'password' => Hash::make($validated['password']),
        ]);

        $businessPermitPath = $request->file('business_permit')->store('business-permits', 'public');
        $validIdPath = $request->file('valid_id')->store('valid-ids/seller', 'public');

        $user->sellerDetail()->create([
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
            'business_name' => $validated['business_name'],
            'business_permit_path' => $businessPermitPath,
        ]);

        $user->categories()->sync($validated['categories']);

        Cache::forget('otp_verified:'.$email);
            $request->session()->forget('registration_verification');
        Notification::create(['user_id' => null, 'type' => 'new_seller_registration', 'title' => 'New seller registration', 'message' => 'A seller account is awaiting review.', 'link' => route('admin.registrations.index')]);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Awaiting admin approval.',
        ]);
    }
}