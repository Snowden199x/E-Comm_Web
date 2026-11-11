<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\Profiles\BuyerDetail;
use App\Services\ProfilePhotoService;
use App\Services\PsgcAddressResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /** Validation messages that say how to fix the problem. */
    private const MESSAGES = [
        'name.required' => 'Enter your full name.',
        'name.max' => 'Use 255 characters or fewer.',
        'phone_number.max' => 'Use 20 characters or fewer, for example 09171234567.',
        'province.required' => 'Enter your province.',
        'municipality.required' => 'Enter your city or municipality.',
        'barangay.required' => 'Enter your barangay.',
        'street.required' => 'Enter your street so the rider can find you.',
        'house_no.max' => 'Use 255 characters or fewer.',
        'zip_code.max' => 'Use 20 characters or fewer.',
        'latitude.numeric' => 'Move the pin on the map, then save again.',
        'latitude.between' => 'Move the pin onto the map, then save again.',
        'longitude.numeric' => 'Move the pin on the map, then save again.',
        'longitude.between' => 'Move the pin onto the map, then save again.',
        'longitude.required_with' => 'Move the pin on the map, then save again.',
        'profile_picture.required' => 'Choose a photo to upload.',
        'profile_picture.image' => 'Choose a JPG, PNG or WebP photo.',
        'profile_picture.max' => 'Choose a photo under 2 MB.',
        'banner.required' => 'Choose a banner to upload.',
        'banner.image' => 'Choose a JPG, PNG or WebP image.',
        'banner.max' => 'Choose a banner under 4 MB.',
        'preferred_delivery_company_id.exists' => 'Pick a provider from the list.',
        'preferred_delivery_company_id.integer' => 'Pick a provider from the list.',
    ];

    public function index()
    {
        abort_unless(auth()->user()->role === 'buyer', 403);
        $policies = \App\Models\Communication\PlatformPolicy::availableForRole('buyer');
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        // Only Companies with an active Station Hub in the Buyer's own municipality can be preferred.
        $deliveryCompanies = collect();
        if ($buyerDetail) {
            $codes = app(\App\Services\LocationCatalog::class)->codesForNames($buyerDetail->province, $buyerDetail->municipality);
            if ($codes) {
                $deliveryCompanies = \App\Models\Profiles\LogisticsCompany::query()
                    ->where('status', 'approved')
                    ->whereHas('logisticsCenter.user', fn ($user) => $user->where('role', 'logistics_center')
                        ->where('status', 'approved')->whereNull('archived_at')
                        ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
                    ->whereHas('facilities', fn ($facility) => $facility->where('kind', 'station_hub')
                        ->where('is_active', true)
                        ->where('province_code', $codes[0])->where('municipality_code', $codes[1]))
                    ->orderBy('business_name')->get();
            }
        }

        $pendingDeletion = auth()->user()->pendingDeletionRequest;
        $deletionReasons = AccountDeletionRequest::REASONS;

        return view('buyer.account', compact('buyerDetail', 'policies', 'deliveryCompanies', 'pendingDeletion', 'deletionReasons'));
    }

    /** Buyer asks admin to close the account. The account stays usable until an admin approves. */
    public function requestDeletion(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'buyer', 403);

        if ($user->pendingDeletionRequest()->exists()) {
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
            'user_id' => $user->id,
            'reasons' => array_values($data['reasons']),
            'comments' => $data['comments'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Your deletion request was sent to the admin team for review.');
    }

    public function update(Request $request, ProfilePhotoService $photos, PsgcAddressResolver $addresses)
    {
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();
        $rules = [
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'profile_picture' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
        ];
        if ($buyerDetail) {
            $rules += [
                'province' => 'required|string|max:255',
                'municipality' => 'required|string|max:255',
                'barangay' => 'required|string|max:255',
                'street' => 'required|string|max:255',
                'house_no' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:20',
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            ];
        }
        $request->validate($rules, self::MESSAGES);

        $address = $buyerDetail
            ? $addresses->resolve($request->only('province', 'municipality', 'barangay'))
            : null;

        $userData = [
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ];

        auth()->user()->update($userData);

        if ($buyerDetail) {
            $locationChanged = $buyerDetail->province !== $address['province']
                || $buyerDetail->municipality !== $address['municipality'];
            $detailData = [
                ...$address,
                'street' => $request->street,
                'house_no' => $request->house_no,
                'zip_code' => $request->zip_code,
                // Saving the profile without reopening the map must not erase a
                // pin the Buyer already placed.
                'latitude' => $request->filled('latitude') ? $request->latitude : $buyerDetail->latitude,
                'longitude' => $request->filled('longitude') ? $request->longitude : $buyerDetail->longitude,
            ];
            if ($locationChanged) {
                // A preferred carrier is valid only for the saved delivery municipality.
                $detailData['preferred_delivery_company_id'] = null;
            }

            if ($request->hasFile('banner')) {
                $detailData['banner_path'] = $request->file('banner')->store('buyer-banners', 'public');
            }

            $buyerDetail->update($detailData);
        }

        if ($request->hasFile('profile_picture')) {
            $photos->replace($request->user(), $request->file('profile_picture'), 'profile-pictures/buyers');
        }

        return back()->with('success', 'Profile saved');
    }

    public function uploadProfilePicture(Request $request, ProfilePhotoService $photos)
    {
        $request->validate(['profile_picture' => 'required|image|max:2048'], self::MESSAGES);

        $photos->replace($request->user(), $request->file('profile_picture'), 'profile-pictures/buyers');

        return back()->with('success', 'Profile picture updated.');
    }

    public function uploadBanner(Request $request)
    {
        $request->validate(['banner' => 'required|image|max:4096'], self::MESSAGES);

        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $buyerDetail->update([
                'banner_path' => $request->file('banner')->store('buyer-banners', 'public'),
            ]);
        }

        return back()->with('success', 'Banner updated.');
    }

    public function removeProfilePicture(Request $request, ProfilePhotoService $photos)
    {
        $removed = $photos->remove($request->user());

        return back()->with('success', $removed ? 'Profile picture removed.' : 'No profile picture to remove.');
    }

    public function removeBanner()
    {
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $buyerDetail->update(['banner_path' => null]);
        }

        return back()->with('success', 'Banner removed.');
    }

    public function updateDeliveryPreference(Request $request)
    {
        $data = $request->validate([
            'preferred_delivery_company_id' => ['nullable', 'integer', 'exists:logistics_companies,id'],
        ], self::MESSAGES);

        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();
        if ($buyerDetail) {
            $buyerDetail->update(['preferred_delivery_company_id' => $data['preferred_delivery_company_id'] ?? null]);
        }

        return redirect()->route('buyer.account.index', ['tab' => 'delivery'])
            ->with('success', 'Delivery preference saved.');
    }

    public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => ['required'],
        'password' => ['required', 'min:8', 'confirmed'],
    ], [
        'current_password.required' => 'Enter your current password.',
        'password.required' => 'Enter a new password.',
        'password.min' => 'Use at least 8 characters.',
        'password.confirmed' => "Passwords don't match. Retype the new password.",
    ]);

    if (!Hash::check($request->current_password, auth()->user()->password)) {
        return back()
            ->withErrors([
                'current_password' => "That isn't your current password. Re-enter it and try again.",
            ])
            ->withInput();
    }

    auth()->user()->update([
        'password' => Hash::make($request->password),
    ]);

    return back()->with('password_success', 'Password updated');
}
}
