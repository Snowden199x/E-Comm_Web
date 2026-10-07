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
        return view('admin.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $admin = User::where('role', 'admin')
            ->where(function ($query) use ($request) {
                $query->where('email', $request->email)
                    ->orWhere('recovery_email', $request->email);
            })
            ->first();

        if ($admin && ! empty($admin->recovery_email)) {
            Password::sendResetLink(['email' => $admin->email]);
        }

        return back()->with('status', 'If an account exists with this email, password reset instructions have been sent.');
    }
}