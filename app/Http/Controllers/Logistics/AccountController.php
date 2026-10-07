<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\PlatformPolicy;
use App\Services\ProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'logistics_center', 403);
        abort_unless($user->status === 'approved' && ! $user->archived_at
            && (! $user->account_status || $user->account_status === 'active'), 403);

        $center = $user->logisticsCenterDetail;
        $policies = PlatformPolicy::availableForRole($user->role);

        return view('logistics.account', compact('user', 'center', 'policies'));
    }

    public function avatar(Request $request, ProfilePhotoService $photos): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $photos->replace($request->user(), $request->file('avatar'), 'profile-pictures/logistics');

        return back()->with('success', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request, ProfilePhotoService $photos): RedirectResponse
    {
        $removed = $photos->remove($request->user());

        return back()->with('success', $removed ? 'Profile photo removed.' : 'No profile photo to remove.');
    }
}
