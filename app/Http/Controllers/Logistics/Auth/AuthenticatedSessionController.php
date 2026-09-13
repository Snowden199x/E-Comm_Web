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
        return view('auth.login-logistics');
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

        if (! in_array($user->role, ['courier', 'logistics_center'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This login is for logistics partner accounts only.',
            ]);
        }

        if ($user->status !== 'approved') {
            Auth::logout();

            // Riders are approved by their chosen Logistics/Sorting Center, not the admin.
            $pendingMessage = $user->role === 'courier'
                ? 'Your account is still pending approval from your chosen Logistics/Sorting Center.'
                : 'Your account is still pending admin approval.';

            $message = match ($user->status) {
                'pending' => $pendingMessage,
                'disapproved' => 'Your registration was not approved. '.($user->rejection_reason ?? ''),
                default => 'Your account is not active.',
            };

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        $redirectRoute = $user->role === 'logistics_center'
            ? 'logistics.dashboard'
            : 'logistics.courier.dashboard';

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