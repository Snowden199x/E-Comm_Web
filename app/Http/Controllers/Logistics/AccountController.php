<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\PlatformPolicy;
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
}
