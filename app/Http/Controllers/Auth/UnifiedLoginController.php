<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnifiedLoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user?->role === 'buyer') {
            return redirect()->route('buyer.dashboard');
        }

        if ($user?->role === 'seller') {
            return redirect()->route('seller.dashboard');
        }

        return view('buyer.auth.login', ['unified' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // Buyer and Seller share this page. Logistics, Rider and Admin keep their own login pages.
        if (! in_array($user->role, ['buyer', 'seller'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This login is for Buyer and Seller accounts only. Use your account type’s login page.',
            ]);
        }

        if ($user->status !== 'approved' || $user->archived_at
            || ($user->account_status && $user->account_status !== 'active')) {
            Auth::logout();

            $message = match ($user->status) {
                'pending' => 'Your account is still pending admin approval.',
                'disapproved' => 'Your registration was not approved. '.($user->rejection_reason ?? ''),
                default => 'Your account is not active.',
            };

            if ($user->archived_at || ($user->account_status && $user->account_status !== 'active')) {
                $message = 'Your account is not active. Please contact the administrator.';
            }

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        $dashboard = $user->role === 'seller'
            ? route('seller.dashboard', absolute: false)
            : route('buyer.dashboard', absolute: false);

        return redirect()->intended($dashboard);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
