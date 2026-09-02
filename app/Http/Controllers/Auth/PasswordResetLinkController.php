<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $admin = User::where('email', $request->email)
            ->where('role', 'admin')
            ->first();

        if ($admin) {
            Password::sendResetLink(['email' => $admin->email]);
        }

        return back()->with('status', 'If an account exists with this email, password reset instructions have been sent.');
    }
}