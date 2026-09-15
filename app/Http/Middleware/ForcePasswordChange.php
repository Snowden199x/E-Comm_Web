<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->must_change_password && ! $request->routeIs('admin.account-management.force-password*') && ! $request->routeIs('admin.logout')) {
            return redirect()->route('admin.account-management.force-password');
        }

        return $next($request);
    }
}