<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->load(['sellerDetail', 'categories']);

        $policies = \App\Models\Communication\PlatformPolicy::availableForRole('seller');

        return view('seller.account.index', compact('seller', 'policies'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:20'],
            'shop_description' => ['nullable', 'string', 'max:1000'],
        ]);
        $seller = $request->user();
        $seller->update(['phone_number' => $validated['phone_number'] ?? null]);
        $seller->sellerDetail()->update(['shop_description' => $validated['shop_description'] ?? null]);

        return back()->with('success', 'Profile updated.');
    }

    public function avatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $seller = $request->user();
        $old = $seller->profile_picture;
        $path = $request->file('avatar')->store('profile-pictures/sellers', 'public');
        try {
            $seller->update(['profile_picture' => $path]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
        if ($old && str_starts_with($old, 'profile-pictures/sellers/')) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request)
    {
        $seller = $request->user();
        $old = $seller->profile_picture;
        $seller->update(['profile_picture' => null]);
        if ($old && str_starts_with($old, 'profile-pictures/sellers/')) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Profile photo removed.');
    }

    public function banner(Request $request)
    {
        $request->validate(['banner' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);
        $detail = $request->user()->sellerDetail;
        abort_unless($detail, 404);
        $old = $detail->shop_banner_path;
        $path = $request->file('banner')->store('shop-banners', 'public');
        try {
            $detail->update(['shop_banner_path' => $path]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
        if ($old && str_starts_with($old, 'shop-banners/')) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Shop banner updated.');
    }

    public function removeBanner(Request $request)
    {
        $detail = $request->user()->sellerDetail;
        abort_unless($detail, 404);
        $old = $detail->shop_banner_path;
        $detail->update(['shop_banner_path' => null]);
        if ($old && str_starts_with($old, 'shop-banners/')) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Shop banner removed.');
    }

    public function password(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        $seller = $request->user();
        if (! Hash::check($validated['current_password'], $seller->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        $seller->update(['password' => $validated['password']]);

        return back()->with('success', 'Password updated.');
    }
}
