<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\CourierDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $courierDetail->user->update(['status' => 'approved']);

        return back()->with('confirmation', 'approved');
    }

    public function rejectRider(Request $request, CourierDetail $courierDetail): RedirectResponse
    {
        $this->authorizeRider($courierDetail);

        $request->validate([
            'reason' => 'required|string',
            'additional_details' => 'nullable|string|max:500',
        ]);

        $courierDetail->user->update([
            'status' => 'disapproved',
            'rejection_reason' => $request->reason,
            'rejection_notes' => $request->additional_details,
        ]);

        return back()->with('confirmation', 'rejected');
    }

    private function authorizeRider(CourierDetail $courierDetail): void
    {
        abort_unless(
            $courierDetail->logistics_center_id === optional(Auth::user()->logisticsCenterDetail)->id,
            403
        );
    }
}