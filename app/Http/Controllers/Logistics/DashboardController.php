<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\CourierDetail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $center = Auth::user()->logisticsCenterDetail;
        $centerId = optional($center)->id;

        $riders = CourierDetail::with('user')
            ->where('logistics_center_id', $centerId)
            ->whereHas('user', fn ($q) => $q->where('status', 'pending'))
            ->latest()
            ->paginate(8);

        $stats = [
            'pending' => CourierDetail::where('logistics_center_id', $centerId)
                ->whereHas('user', fn ($q) => $q->where('status', 'pending'))->count(),
            'approved' => CourierDetail::where('logistics_center_id', $centerId)
                ->whereHas('user', fn ($q) => $q->where('status', 'approved'))->count(),
            'rejected' => CourierDetail::where('logistics_center_id', $centerId)
                ->whereHas('user', fn ($q) => $q->where('status', 'disapproved'))->count(),
        ];

        return view('logistics.dashboard', compact('center', 'riders', 'stats'));
    }

    public function approveRider(CourierDetail $courierDetail): RedirectResponse
    {
        $this->authorizeRider($courierDetail);
        DB::transaction(function () use ($courierDetail) {
            $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
            abort_unless($user->status === 'pending', 409, 'This application has already been reviewed.');
            $user->update(['status' => 'approved']);
        }, 3);

        return back()->with('confirmation', 'approved');
    }

    public function rejectRider(Request $request, CourierDetail $courierDetail): RedirectResponse
    {
        $this->authorizeRider($courierDetail);

        $request->validate([
            'reason' => 'required|string',
            'additional_details' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $courierDetail) {
            $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
            abort_unless($user->status === 'pending', 409, 'This application has already been reviewed.');
            $user->update([
                'status' => 'disapproved',
                'rejection_reason' => $request->reason,
                'rejection_notes' => $request->additional_details,
            ]);
        }, 3);

        return back()->with('confirmation', 'rejected');
    }

    private function authorizeRider(CourierDetail $courierDetail): void
    {
        abort_unless(
            $courierDetail->logistics_center_id === Auth::user()->logisticsCenterDetail->id
                && $courierDetail->user?->role === 'courier',
            403
        );
    }
}
