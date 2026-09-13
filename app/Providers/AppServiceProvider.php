<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // By default, Laravel's "guest" middleware sends an already-logged-in
        // user to '/' (since this app has no named "home"/"dashboard" route).
        // That made things like clicking "Apply Now" or the logistics login
        // silently bounce back to the main landing page whenever someone was
        // still signed in under a different role from an earlier session,
        // instead of taking them to that role's own dashboard.
        RedirectIfAuthenticated::redirectUsing(function () {
            if (Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }

            $user = Auth::user();

            return match ($user?->role) {
                'buyer' => route('buyer.dashboard'),
                'seller' => route('seller.dashboard'),
                'courier' => route('logistics.courier.dashboard'),
                'logistics_center' => route('logistics.dashboard'),
                default => '/',
            };
        });

        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            $routeName = $user->role === 'admin' ? 'admin.password.reset' : 'password.reset';

            return url(route($routeName, [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });
    }
}