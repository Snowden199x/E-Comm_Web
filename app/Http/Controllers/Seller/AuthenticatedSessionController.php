<?php

namespace App\Http\Controllers\Seller;

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
        return view('seller.auth.login');
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

        if ($user->role !== 'seller') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This login is for seller accounts only.',
            ]);
        }

        if ($user->status !== 'approved') {
            Auth::logout();

            $message = match ($user->status) {
                'pending' => 'Your account is still pending admin approval.',
                'disapproved' => 'Your registration was not approved. '.($user->rejection_reason ?? ''),
                default => 'Your account is not active.',
            };

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('seller.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('seller.login'));
    }
}
