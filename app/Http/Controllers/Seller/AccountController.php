<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use App\Models\AccountDeletionRequest;
use App\Models\Category;
use App\Models\Profiles\SellerCategoryRequest;
use App\Services\PsgcAddressResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

        $pendingDeletion = $seller->pendingDeletionRequest;
        $deletionReasons = AccountDeletionRequest::REASONS;

        return view('seller.account.index', compact('seller', 'policies', 'categoryRequests', 'availableCategories', 'pendingDeletion', 'deletionReasons'));
    }

    /** Seller asks admin to close the account. The shop stays open until an admin approves. */
    public function requestDeletion(Request $request)
    {
        $seller = $request->user();
        abort_unless($seller->role === 'seller', 403);

        if ($seller->pendingDeletionRequest()->exists()) {
            return back()->with('success', 'Your deletion request is already waiting for admin review.');
        }

        $data = $request->validate([
            'reasons' => ['required', 'array', 'min:1'],
            'reasons.*' => ['string', Rule::in(AccountDeletionRequest::REASONS)],
            'comments' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array('Other', (array) $request->input('reasons', []), true))],
        ], [
            'reasons.required' => 'Pick a reason for deleting your account.',
            'reasons.min' => 'Pick a reason for deleting your account.',
            'reasons.*.in' => 'Pick a reason from the list.',
            'comments.max' => 'Keep your comment under 500 characters.',
            'comments.required' => 'Tell the admin why when you pick "Other".',
        ]);

        AccountDeletionRequest::create([
            'user_id' => $seller->id,
            'reasons' => array_values($data['reasons']),
            'comments' => $data['comments'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Your deletion request was sent to the admin team for review.');
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
        ], [
            'phone_number.regex' => 'Contact number must be 11 digits and start with 09. Example: 09171234567.',
            'shop_description.max' => 'Description is too long. Keep it under 1,000 characters.',
            'province.required' => 'Enter your province.',
            'municipality.required' => 'Enter your municipality or city.',
            'barangay.required' => 'Enter your barangay.',
            'street.required' => 'Enter your street address.',
            'zip_code.max' => 'ZIP code is too long. Use 20 characters or fewer.',
            'latitude.between' => 'Pin is outside the map range. Search your address again and drop the pin.',
            'longitude.required_with' => 'Pin your pickup address on the map, then save again.',
            'longitude.between' => 'Pin is outside the map range. Search your address again and drop the pin.',
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

        return back()->with('success', 'Changes saved.');
    }

    public function avatar(Request $request, ProfilePhotoService $photos)
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']], [
            'avatar.required' => 'Choose a photo first.',
            'avatar.image' => 'That file is not an image. Choose a JPG, PNG, or WebP photo.',
            'avatar.mimes' => 'Photo must be a JPG, PNG, or WebP file.',
            'avatar.max' => 'Photo is over 2 MB. Choose a smaller photo.',
        ]);
        $photos->replace($request->user(), $request->file('avatar'), 'profile-pictures/sellers');

        return back()->with('success', 'Photo saved.');
    }

    public function removeAvatar(Request $request, ProfilePhotoService $photos)
    {
        $removed = $photos->remove($request->user());

        return back()->with('success', $removed ? 'Profile photo removed.' : 'No profile photo to remove.');
    }

    public function banner(Request $request)
    {
        $request->validate(['banner' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']], [
            'banner.required' => 'Choose a banner first.',
            'banner.image' => 'That file is not an image. Choose a JPG, PNG, or WebP banner.',
            'banner.mimes' => 'Banner must be a JPG, PNG, or WebP file.',
            'banner.max' => 'Banner is over 4 MB. Choose a smaller image.',
        ]);
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

        return back()->with('success', 'Banner saved.');
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
        ], [
            'current_password.required' => 'Enter your current password.',
            'password.required' => 'Enter a new password.',
            'password.confirmed' => 'New passwords do not match. Retype the confirmation.',
            'password.min' => 'New password is too short. Use at least 8 characters.',
            'password.mixed' => 'New password needs an uppercase and a lowercase letter.',
            'password.numbers' => 'New password needs at least one number.',
            'password.symbols' => 'New password needs at least one symbol, like ! or @.',
        ]);
        $seller = $request->user();
        if (! Hash::check($validated['current_password'], $seller->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect. Retype it and try again.']);
        }
        $seller->update(['password' => $validated['password']]);

        return back()->with('success', 'Password updated.');
    }
}
