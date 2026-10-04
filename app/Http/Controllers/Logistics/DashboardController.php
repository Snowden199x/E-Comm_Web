<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\CourierDetail;
use App\Models\Ecommerce\Order;
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

        $overview = [
            'pickup_requests' => Order::query()->where('logistics_center_id', $centerId)
                ->where('status', 'ready_for_pickup')->count(),
            'to_sort' => Order::query()->where('logistics_center_id', $centerId)
                ->where('status', 'at_sorting_center')->count(),
            'to_assign' => Order::query()->where('destination_logistics_center_id', $centerId)
                ->where(fn ($query) => $query->where('status', 'at_destination_hub')
                    ->orWhere(fn ($local) => $local->where('status', 'sorted')
                        ->where('logistics_center_id', $centerId)))->count(),
            'active' => Order::query()
                ->where(fn ($query) => $query->where('logistics_center_id', $centerId)
                    ->orWhere('destination_logistics_center_id', $centerId))
                ->whereIn('status', ['picked_up', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub',
                    'at_destination_hub', 'assigned_to_rider', 'out_for_delivery'])->count(),
        ];

        return view('logistics.dashboard', compact('center', 'riders', 'stats', 'overview'));
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

    public function ridersIndex(Request $request): View
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $status = $request->validate(['status' => 'nullable|in:approved,pending,disapproved,deactivated'])['status'] ?? 'approved';
        $riders = CourierDetail::query()->where('logistics_center_id', $centerId)
            ->whereHas('user', function ($query) use ($status) {
                $query->where('role', 'courier');
                if ($status === 'deactivated') {
                    $query->where('status', 'approved')->where('account_status', 'deactivated');
                } elseif ($status === 'approved') {
                    $query->where('status', 'approved')
                        ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'));
                } else {
                    $query->where('status', $status);
                }
            })
            ->with('user')->latest()->paginate(12)->withQueryString();
        $workload = Order::query()->where('destination_logistics_center_id', $centerId)
            ->whereIn('status', ['assigned_to_rider', 'out_for_delivery'])
            ->whereIn('delivery_courier_id', $riders->getCollection()->pluck('user_id'))
            ->select('delivery_courier_id', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_courier_id')->pluck('total', 'delivery_courier_id');

        return view('logistics.riders', compact('riders', 'status', 'workload'));
    }

    public function updateRiderStatus(Request $request, CourierDetail $courierDetail): RedirectResponse
    {
        $this->authorizeRider($courierDetail);
        $data = $request->validate(['active' => 'required|boolean']);
        DB::transaction(function () use ($courierDetail, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
            abort_unless($user->status === 'approved'
                && in_array($user->account_status, ['active', 'deactivated', null], true), 409,
                'Review this rider application before changing its active status.');
            $user->update(['account_status' => $data['active'] ? 'active' : 'deactivated']);
        }, 3);

        return back()->with('success', $data['active'] ? 'Rider activated.' : 'Rider deactivated.');
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
