<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\RiderTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StationOperationsController extends Controller
{
    public function index(Request $request): View
    {
        $station = $this->station($request);
        $mode = (string) $request->route('mode');
        abort_unless(in_array($mode, ['pickups', 'tasks', 'delivery-queue', 'incoming', 'sorting', 'outgoing'], true), 404);
        $riders = $station->riders()->whereHas('user', fn ($user) => $user->where('status', 'approved')
            ->where('account_status', 'active'))->with('user')->get()
            ->filter(fn ($rider) => in_array($rider->vehicle_type, ['Motorcycle', 'Van', 'L300'], true));
        $statuses = match ($mode) {
            'pickups' => ['ready_for_pickup', 'picked_up'],
            'delivery-queue' => ['at_destination_hub', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'],
            'tasks' => [],
            default => ['picked_up', 'sorted', 'in_transit_to_hub', 'at_destination_hub'],
        };
        $orders = $mode === 'tasks' ? collect() : Order::query()
            ->with(['seller.sellerDetail', 'buyer', 'facilityStops.facility'])
            ->where(fn ($query) => $query->where('origin_station_id', $station->id)
                ->orWhere('destination_station_id', $station->id)
                ->orWhereHas('facilityStops', fn ($stops) => $stops->where('facility_id', $station->id)))
            ->whereIn('status', $statuses)->latest()->limit(200)->get();
        $rows = collect();
        foreach ($orders as $order) {
            $stop = $order->facilityStops->firstWhere('facility_id', $station->id);
            $next = $stop ? $order->facilityStops->firstWhere('position', $stop->position + 1) : null;
            $include = match ($mode) {
                'pickups' => (int) $order->origin_station_id === (int) $station->id
                    && in_array($order->status, ['ready_for_pickup', 'picked_up'], true),
                'incoming' => $stop && (! $stop->arrived_at || $stop->arrived_at->isToday())
                    && in_array($order->status, ['picked_up', 'sorted', 'in_transit_to_hub', 'at_destination_hub'], true),
                'sorting' => $stop && $stop->sorted_at && ! $stop->departed_at,
                'outgoing' => $stop && $next && $stop->sorted_at && ! $stop->departed_at,
                'delivery-queue' => (int) $order->destination_station_id === (int) $station->id
                    && in_array($order->status, ['at_destination_hub', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'], true),
                'tasks' => false,
            };
            if (! $include) continue;
            $rows->push([
                'id' => $order->id, 'tracking' => $order->tracking_number,
                'seller' => $order->seller?->sellerDetail?->business_name ?: $order->seller?->name,
                'buyer' => $order->buyer?->name,
                'address' => $mode === 'pickups'
                    ? implode(', ', array_filter([$order->seller?->sellerDetail?->house_no,
                        $order->seller?->sellerDetail?->street, $order->seller?->sellerDetail?->barangay,
                        $order->seller?->sellerDetail?->municipality]))
                    : $order->shipping_address,
                'status' => $order->status_label, 'pickup_status' => $order->pickup_request_status,
                'rider' => $mode === 'pickups' ? $order->courier_id : $order->delivery_courier_id,
                'next' => $next?->facility?->name ?: 'Local delivery',
                'arrived' => $stop?->arrived_at?->format('M j, g:i A'),
                'sorted' => $stop?->sorted_at?->format('M j, g:i A'),
                'can_assign' => $mode === 'pickups'
                    ? $order->status === 'ready_for_pickup'
                    : ($order->status === 'at_destination_hub' && $stop?->sorted_at),
            ]);
        }
        if ($mode === 'tasks') {
            $rows = RiderTask::query()->where('station_hub_id', $station->id)
                ->whereIn('status', ['assigned', 'in_progress', 'failed'])->with(['order.seller', 'order.buyer', 'rider.user'])->latest()->limit(200)->get()
                ->map(fn ($task) => ['id' => $task->order_id, 'tracking' => $task->order?->tracking_number,
                    'seller' => $task->order?->seller?->name, 'buyer' => $task->order?->buyer?->name,
                    'address' => $task->type === 'pickup' ? 'Seller pickup' : $task->order?->shipping_address,
                    'status' => ucfirst(str_replace('_', ' ', $task->status)), 'pickup_status' => null,
                    'rider_name' => $task->rider?->user?->name, 'type' => $task->type,
                    'failure_reason' => $task->failure_reason,
                    'next' => null, 'arrived' => null, 'sorted' => null, 'can_assign' => false]);
        }
        $trips = $mode === 'outgoing' ? LogisticsTransportTrip::query()
            ->where('origin_facility_id', $station->id)->whereIn('status', ['scheduled', 'in_transit'])
            ->with(['destination', 'driver.user', 'vehicle'])->latest()->limit(30)->get() : collect();

        return view('logistics.station.operations', [
            'mode' => $mode, 'station' => $station, 'rows' => $rows,
            'riders' => $riders, 'trips' => $trips,
        ]);
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $station = $this->station($request);
        $data = $request->validate(['type' => ['required', 'in:pickup,delivery'],
            'rider_id' => ['required', 'integer', 'exists:courier_details,id']]);
        DB::transaction(function () use ($request, $order, $station, $data) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $rider = $station->riders()->whereKey($data['rider_id'])->with('user')->firstOrFail();
            abort_unless($rider->user?->status === 'approved' && $rider->user?->account_status === 'active'
                && in_array($rider->vehicle_type, ['Motorcycle', 'Van', 'L300'], true), 409, 'Choose an active local Rider.');
            if ($data['type'] === 'pickup') {
                abort_unless((int) $order->origin_station_id === (int) $station->id
                    && $order->status === 'ready_for_pickup'
                    && in_array($order->pickup_request_status, ['pending', 'verified'], true), 409,
                    'This parcel is no longer ready for pickup at your station.');
                $order->pickup_request_status = 'verified';
                $order->pickup_verified_at ??= now();
                $order->courier_id = $rider->user_id;
            } else {
                $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $station->id)->first();
                abort_unless((int) $order->destination_station_id === (int) $station->id
                    && $order->status === 'at_destination_hub' && $stop?->sorted_at, 409,
                    'Receive and sort this parcel at your station before assigning delivery.');
                $order->delivery_courier_id = $rider->user_id;
                $order->status = 'assigned_to_rider';
            }
            $order->save();
            $task = RiderTask::query()->where('order_id', $order->id)->where('type', $data['type'])
                ->whereIn('status', ['assigned', 'in_progress'])->lockForUpdate()->first();
            if ($task && $task->status !== 'assigned') {
                throw ValidationException::withMessages(['rider_id' => 'This Rider has already started the task.']);
            }
            if ($task) {
                $task->update(['rider_id' => $rider->id, 'station_hub_id' => $station->id, 'assigned_by' => $request->user()->id]);
            } else {
                RiderTask::create(['rider_id' => $rider->id, 'station_hub_id' => $station->id,
                    'order_id' => $order->id, 'type' => $data['type'], 'status' => 'assigned',
                    'assigned_by' => $request->user()->id, 'assigned_at' => now()]);
            }
            Notification::create(['user_id' => $rider->user_id, 'type' => 'rider_task_assigned',
                'title' => ucfirst($data['type']).' assignment '.$order->number,
                'message' => 'Your Station Hub assigned this parcel to you. Open the Rider app to scan it.']);
            if ($data['type'] === 'pickup') {
                Notification::create(['user_id' => $order->seller_id, 'type' => 'shipment_update',
                    'title' => 'Pickup assigned '.$order->number,
                    'message' => $rider->user->name.' was assigned to collect your parcel.',
                    'link' => route('seller.orders.show', $order)]);
            }
        }, 3);

        return back()->with('success', ucfirst($data['type']).' Rider assigned.');
    }

    public function declinePickup(Request $request, Order $order): RedirectResponse
    {
        $station = $this->station($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        DB::transaction(function () use ($request, $order, $station, $data) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless((int) $order->origin_station_id === (int) $station->id
                && $order->status === 'ready_for_pickup' && ! $order->courier_id
                && $order->pickup_request_status === 'pending', 409,
                'This pickup request can no longer be declined.');
            $order->status = 'preparing';
            $order->pickup_request_status = 'declined';
            $order->pickup_decline_reason = $data['reason'];
            $order->pickup_verified_at = null;
            $order->saveQuietly();
            $order->statusEvents()->create(['user_id' => $request->user()->id,
                'from_status' => 'ready_for_pickup', 'to_status' => 'preparing',
                'note' => 'Pickup request declined by '.$station->name.': '.$data['reason']]);
            Notification::create(['user_id' => $order->seller_id, 'type' => 'pickup_request_declined',
                'title' => 'Pickup request needs attention '.$order->number,
                'message' => $data['reason'].'. Prepare the parcel and mark it ready again.',
                'link' => route('seller.orders.show', $order)]);
        }, 3);

        return back()->with('success', 'Pickup request returned to the seller.');
    }

    private function station(Request $request): LogisticsFacility
    {
        return $request->attributes->get('logistics_facility');
    }
}
