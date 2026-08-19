<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class RegistrationController extends Controller
{
    public function approve(User $user): RedirectResponse
    {
        $user->update(['status' => 'approved']);

        return back()->with('success', $user->name . ' has been approved.');
    }

    public function disapprove(User $user): RedirectResponse
    {
        $user->update(['status' => 'disapproved']);

        return back()->with('success', $user->name . ' has been disapproved.');
    }
}