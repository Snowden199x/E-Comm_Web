<?php

namespace App\Http\Controllers\Logistics\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('logistics.auth.login');
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

        // Rider and Transport Driver work is mobile-only. Do not issue a web session
        // or redirect these accounts to a web dashboard.
        if (in_array($user->role, ['courier', 'transport_driver'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Rider and Transport Driver accounts sign in only in the Vendo mobile apps.',
            ]);
        }

        if (! in_array($user->role, ['logistics_center', 'province_hub', 'station_hub'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This login is for logistics partner accounts only.',
            ]);
        }

        if ($user->status !== 'approved') {
            Auth::logout();

            $message = match ($user->status) {
                'pending' => 'Your account is still pending approval.',
                'disapproved' => 'Your registration was not approved. '.($user->rejection_reason ?? ''),
                default => 'Your account is not active.',
            };

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        $redirectRoute = match ($user->role) {
            'logistics_center' => 'lgp.company',
            'province_hub' => 'lgp.hub.province',
            'station_hub' => 'lgp.hub.station',
            default => 'logistics.landing',
        };

        return redirect()->intended(route($redirectRoute, absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('logistics.login'));
    }
}
