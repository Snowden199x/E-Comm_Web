<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\CourierDetail;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function index(Request $request): View
    {
        $center = $request->user()->logisticsCenterDetail;
        $lane = $request->route('lane', 'all');
        $orders = Order::query()
            ->where(function ($query) use ($center) {
                $query->where(fn ($origin) => $origin->where('logistics_center_id', $center->id)
                    ->whereIn('status', ['ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'in_transit_to_hub']))
                    ->orWhere(fn ($destination) => $destination->where('destination_logistics_center_id', $center->id)
                        ->whereIn('status', ['in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery']));
            })
            ->when($lane === 'incoming', fn ($query) => $query->where('logistics_center_id', $center->id)
                ->whereIn('status', ['ready_for_pickup', 'picked_up']))
            ->when($lane === 'sorting', fn ($query) => $query->where('logistics_center_id', $center->id)
                ->whereIn('status', ['at_sorting_center', 'sorted', 'in_transit_to_hub']))
            ->when($lane === 'delivery', fn ($query) => $query->where('destination_logistics_center_id', $center->id)
                ->whereIn('status', ['sorted', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery']))
            ->with(['seller:id,name,phone_number', 'seller.sellerDetail', 'courier:id,name', 'deliveryCourier:id,name', 'destinationLogisticsCenter'])
            ->latest()
            ->paginate(15);

        $couriers = CourierDetail::query()
            ->where('logistics_center_id', $center->id)
            ->whereHas('user', fn ($query) => $query->where('role', 'courier')
                ->where('status', 'approved')->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->with('user:id,name')
            ->get()
            ->sortBy(fn ($courier) => $courier->user->name)
            ->values();

        return view('logistics.dispatch.index', compact('center', 'orders', 'couriers', 'lane'));
    }

    public function assignCourier(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'courier_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);
        $centerId = $request->user()->logisticsCenterDetail->id;

        DB::transaction(function () use ($request, $order, $data, $centerId) {
            $record = Order::query()->where('logistics_center_id', $centerId)
                ->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'ready_for_pickup', 409, 'This order is no longer ready for pickup.');
            abort_unless(! $record->courier_id, 409, 'A courier is already assigned to this order.');

            $courier = CourierDetail::query()->where('logistics_center_id', $centerId)
                ->where('user_id', $data['courier_id'])->with('user')->firstOrFail();
            $user = $courier->user;
            abort_unless($user && $user->role === 'courier' && $user->status === 'approved'
                && ! $user->archived_at && (! $user->account_status || $user->account_status === 'active'),
                422, 'Select an active approved courier from your center.');

            $record->courier_id = $user->id;
            $record->save();
            DB::table('order_logistics_assignments')->insert([
                'order_id' => $record->id,
                'actor_id' => $request->user()->id,
                'action' => 'pickup_courier_assigned',
                'to_logistics_center_id' => $centerId,
                'courier_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Pickup courier assigned. Waiting for the rider pickup scan.');
    }

    public function markArrived(Request $request, int $order): RedirectResponse
    {
        $this->transition($request, $order, 'picked_up', 'at_sorting_center');

        return back()->with('success', 'Arrival at the sorting center recorded.');
    }

    public function markSorted(Request $request, int $order): RedirectResponse
    {
        $this->transition($request, $order, 'at_sorting_center', 'sorted');

        return back()->with('success', 'Sorting completed for this order.');
    }

    public function sendToHub(Request $request, int $order, OrderRoutingService $routing): RedirectResponse
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $order, $centerId, $routing) {
            $record = Order::query()->where('logistics_center_id', $centerId)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'sorted', 409, 'This order is no longer ready for hub transfer.');
            $routing->route($record);
            abort_unless($record->destination_logistics_center_id, 409, 'Destination hub is unresolved. Check the buyer location and active centers.');
            abort_unless($record->destination_logistics_center_id !== $centerId, 409, 'This order is already at its destination hub.');
            $record->status = 'in_transit_to_hub';
            $record->save();
            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => 'sorted',
                'to_status' => 'in_transit_to_hub',
                'note' => 'Sent from '.$record->logisticsCenter->business_name.' to '.$record->destinationLogisticsCenter->business_name.'.',
            ]);
            DB::table('order_logistics_assignments')->insert([
                'order_id' => $record->id, 'actor_id' => $request->user()->id, 'action' => 'sent_to_destination_hub',
                'from_logistics_center_id' => $centerId, 'to_logistics_center_id' => $record->destination_logistics_center_id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Parcel dispatched to its destination hub.');
    }

    public function receiveAtHub(Request $request, int $order): RedirectResponse
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $order, $centerId) {
            $record = Order::query()->where('destination_logistics_center_id', $centerId)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'in_transit_to_hub', 409, 'This parcel is not awaiting destination hub receipt.');
            $record->status = 'at_destination_hub';
            $record->save();
            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => 'in_transit_to_hub',
                'to_status' => 'at_destination_hub',
                'note' => 'Parcel received at '.$request->user()->logisticsCenterDetail->business_name.'.',
            ]);
        }, 3);

        return back()->with('success', 'Parcel received at the destination hub.');
    }

    public function assignDeliveryCourier(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'courier_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);
        $centerId = $request->user()->logisticsCenterDetail->id;

        DB::transaction(function () use ($request, $order, $data, $centerId) {
            $record = Order::query()->where('destination_logistics_center_id', $centerId)
                ->lockForUpdate()->findOrFail($order);
            $local = $record->logistics_center_id === $centerId && $record->status === 'sorted';
            abort_unless(($local || $record->status === 'at_destination_hub') && ! $record->delivery_courier_id,
                409, 'This order is no longer available for delivery rider assignment.');

            $courier = CourierDetail::query()->where('logistics_center_id', $centerId)
                ->where('user_id', $data['courier_id'])->with('user')->firstOrFail();
            $user = $courier->user;
            abort_unless($user && $user->role === 'courier' && $user->status === 'approved'
                && ! $user->archived_at && (! $user->account_status || $user->account_status === 'active'),
                422, 'Select an active approved courier from your center.');

            $record->delivery_courier_id = $user->id;
            $record->status = 'assigned_to_rider';
            $record->save();
            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => $local ? 'sorted' : 'at_destination_hub',
                'to_status' => 'assigned_to_rider',
                'note' => 'Delivery rider assigned by '.$request->user()->logisticsCenterDetail->business_name.'.',
            ]);
            DB::table('order_logistics_assignments')->insert([
                'order_id' => $record->id,
                'actor_id' => $request->user()->id,
                'action' => 'delivery_courier_assigned',
                'to_logistics_center_id' => $centerId,
                'courier_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Delivery rider assigned. Waiting for the rider app workflow.');
    }

    private function transition(Request $request, int $order, string $from, string $to): void
    {
        $centerId = $request->user()->logisticsCenterDetail->id;

        DB::transaction(function () use ($request, $order, $centerId, $from, $to) {
            $record = Order::query()->where('logistics_center_id', $centerId)
                ->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === $from, 409, 'This order has changed. Reload the dispatch page.');
            abort_unless($record->courier_id, 409, 'A pickup courier is required.');

            $record->status = $to;
            $record->save();
            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $to === 'at_sorting_center'
                    ? 'Arrival confirmed at '.$request->user()->logisticsCenterDetail->business_name.'.'
                    : 'Sorting completed at '.$request->user()->logisticsCenterDetail->business_name.'.',
            ]);
        }, 3);
    }
}
