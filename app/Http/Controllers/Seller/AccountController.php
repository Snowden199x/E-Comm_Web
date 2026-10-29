<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use App\Models\Category;
use App\Models\Profiles\SellerCategoryRequest;
use App\Services\PsgcAddressResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->load(['sellerDetail', 'categories']);
        $categoryRequests = SellerCategoryRequest::query()->where('seller_id', $seller->id)
            ->with('categories')->latest()->get();
        $availableCategories = Category::query()->whereNull('parent_id')
            ->whereNotIn('id', $seller->categories->pluck('id'))->orderBy('name')->get();

        $policies = \App\Models\Communication\PlatformPolicy::availableForRole('seller');

        return view('seller.account.index', compact('seller', 'policies', 'categoryRequests', 'availableCategories'));
    }

    public function update(Request $request, PsgcAddressResolver $addresses)
    {
        $validated = $request->validate([
            'phone_number' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'shop_description' => ['nullable', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);
        $address = $addresses->resolve($validated);
        $seller = $request->user();
        $seller->update(['phone_number' => $validated['phone_number'] ?? null]);
        $seller->sellerDetail()->update([
            ...$address,
            'street' => $validated['street'],
            'zip_code' => $validated['zip_code'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'shop_description' => $validated['shop_description'] ?? null,
        ]);

        return back()->with('success', 'Profile updated.');
    }

    public function avatar(Request $request, ProfilePhotoService $photos)
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $photos->replace($request->user(), $request->file('avatar'), 'profile-pictures/sellers');

        return back()->with('success', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request, ProfilePhotoService $photos)
    {
        $removed = $photos->remove($request->user());

        return back()->with('success', $removed ? 'Profile photo removed.' : 'No profile photo to remove.');
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
