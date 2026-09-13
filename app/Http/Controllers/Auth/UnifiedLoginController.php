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
    public function create(): View
    {
        return view('auth.login-buyer');
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

        $dashboard = match ($user->role) {
            'seller' => route('seller.dashboard'),
            'logistics_center' => route('logistics.dashboard'),
            'buyer' => route('buyer.dashboard'),
            default => route('buyer.dashboard'),
        };

        return redirect()->to($dashboard);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}