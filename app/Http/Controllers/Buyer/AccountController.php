<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Profiles\BuyerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index()
    {
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        return view('buyer.account', compact('buyerDetail'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'house_no' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'profile_picture' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
        ]);

        $userData = [
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ];

        if ($request->hasFile('profile_picture')) {
            $userData['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        auth()->user()->update($userData);

        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $detailData = [
                'street' => $request->street,
                'house_no' => $request->house_no,
                'zip_code' => $request->zip_code,
            ];

            if ($request->hasFile('banner')) {
                $detailData['banner_path'] = $request->file('banner')->store('buyer-banners', 'public');
            }

            $buyerDetail->update($detailData);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function uploadProfilePicture(Request $request)
    {
        $request->validate(['profile_picture' => 'required|image|max:2048']);

        auth()->user()->update([
            'profile_picture' => $request->file('profile_picture')->store('profile-pictures', 'public'),
        ]);

        return back()->with('success', 'Profile picture updated.');
    }

    public function uploadBanner(Request $request)
    {
        $request->validate(['banner' => 'required|image|max:4096']);

        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $buyerDetail->update([
                'banner_path' => $request->file('banner')->store('buyer-banners', 'public'),
            ]);
        }

        return back()->with('success', 'Banner updated.');
    }

    public function removeProfilePicture()
    {
        auth()->user()->update(['profile_picture' => null]);

        return back()->with('success', 'Profile picture removed.');
    }

    public function removeBanner()
    {
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $buyerDetail->update(['banner_path' => null]);
        }

        return back()->with('success', 'Banner removed.');
    }

    public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => ['required'],
        'password' => ['required', 'min:8', 'confirmed'],
    ], [
        'current_password.required' => 'Please enter your current password.',
        'password.required' => 'Please enter a new password.',
        'password.min' => 'The new password must be at least 8 characters.',
        'password.confirmed' => 'The password confirmation does not match.',
    ]);

    if (!Hash::check($request->current_password, auth()->user()->password)) {
        return back()
            ->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])
            ->withInput();
    }

    auth()->user()->update([
        'password' => Hash::make($request->password),
    ]);

    return back()->with('password_success', 'Password updated successfully!');
}
}   