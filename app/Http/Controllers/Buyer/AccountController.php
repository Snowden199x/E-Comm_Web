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
        ]);

        auth()->user()->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        if ($buyerDetail) {
            $buyerDetail->update([
                'street' => $request->street,
                'house_no' => $request->house_no,
                'zip_code' => $request->zip_code,
            ]);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        auth()->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password updated.');
    }
}   