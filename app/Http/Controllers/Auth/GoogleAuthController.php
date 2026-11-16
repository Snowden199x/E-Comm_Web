<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class GoogleAuthController extends Controller
{
    public function exchange(Request $request, GoogleIdTokenVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'string', 'max:8192'],
            // The shared /login page serves Buyer and Seller at once, so it sends no
            // role and the account's own type decides where it lands. The role-scoped
            // pages still send one, so an account cannot enter through the wrong page.
            'role' => ['nullable', Rule::in(['buyer', 'seller', 'logistics'])],
        ]);
        $requested = $data['role'] ?? null;
        $role = $requested === 'logistics' ? 'logistics_center' : $requested;
        $claims = $verifier->verify($data['credential']);
        $email = $claims['email'];
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $role ??= $user->role;

            if ($requested === null && ! in_array($role, ['buyer', 'seller'], true)) {
                return response()->json([
                    'message' => match ($role) {
                        'logistics_center' => 'This Google email belongs to a Logistics account. Use the Logistics Partner login page.',
                        'courier' => 'This Google email belongs to a Rider account. Sign in from the Rider app.',
                        'admin' => 'This Google email belongs to an Admin account. Use the Admin login page.',
                        default => 'This Google email belongs to another account type. Use that account type’s login page.',
                    },
                ], 409);
            }

            if ($user->role !== $role) {
                return response()->json([
                    'message' => 'This Google email is already registered under a different account type. Use that account type to sign in.',
                ], 409);
            }

            if ($user->archived_at || ($user->account_status && $user->account_status !== 'active')) {
                return response()->json(['message' => 'This account is not active. Contact Vendo support.'], 403);
            }
            if ($user->status !== 'approved') {
                return response()->json(['message' => $user->status === 'pending'
                    ? 'This account is still waiting for approval.'
                    : 'This account is not approved. Contact Vendo support.'], 403);
            }
            if (($role === 'buyer' && ! $user->buyerDetail)
                || ($role === 'seller' && ! $user->sellerDetail)
                || ($role === 'logistics_center' && ! $user->logisticsCenterDetail)) {
                return response()->json(['message' => 'This account profile is incomplete. Contact Vendo support.'], 403);
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            $route = match ($role) {
                'buyer' => route('buyer.dashboard', absolute: false),
                'seller' => route('seller.dashboard', absolute: false),
                default => route('lgp.company', absolute: false),
            };

            return response()->json(['redirect' => $route]);
        }

        $expiresAt = now()->addMinutes(30);
        $request->session()->put('google_registration_verification', [
            'role' => $role,
            'email' => $email,
            'expires_at' => $expiresAt->timestamp,
            'first_name' => $claims['given_name'] ?? '',
            'last_name' => $claims['family_name'] ?? '',
        ]);

        if ($role === null) {
            // The shared login page cannot know the account type yet. The proof above
            // keeps the verified email for whichever type the chooser picks, so the
            // Buyer or Seller form opens with that address already verified.
            return response()->json([
                'redirect' => route('register.choose', absolute: false),
                'registration' => true,
            ]);
        }

        if ($role !== 'buyer') {
            Cache::put('otp_verified:'.$email, true, $expiresAt);
            $request->session()->put('registration_verification', [
                'email' => $email,
                'expires_at' => $expiresAt->timestamp,
            ]);
        }

        $registerRoute = match ($role) {
            'buyer' => route('buyer.register', absolute: false),
            'seller' => route('seller.register', absolute: false),
            default => route('logistics.register', absolute: false),
        };

        return response()->json(['redirect' => $registerRoute, 'registration' => true]);
    }
}
