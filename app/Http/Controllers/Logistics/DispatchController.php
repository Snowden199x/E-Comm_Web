<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Communication\Notification;
use App\Models\Profiles\CourierDetail;
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
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'stage' => 'nullable|string|max:30', 'area' => 'nullable|string|size:9',
            'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        $base = Order::query()
            ->where(function ($query) use ($center) {
                $query->where(fn ($origin) => $origin->where('logistics_center_id', $center->id)
                    ->whereIn('status', ['ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub']))
                    ->orWhere(fn ($destination) => $destination->where('destination_logistics_center_id', $center->id)
                        ->whereIn('status', ['in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery']));
            })
            ->when($lane === 'incoming', fn ($query) => $query->where('logistics_center_id', $center->id)
                ->whereIn('status', ['ready_for_pickup', 'picked_up']))
            ->when($lane === 'sorting', fn ($query) => $query->where('logistics_center_id', $center->id)
                ->whereIn('status', ['at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub']))
            ->when($lane === 'delivery', fn ($query) => $query->where('destination_logistics_center_id', $center->id)
                ->whereIn('status', ['sorted', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery']));

        $areas = (clone $base)->whereNotNull('shipping_city_code')->whereNotNull('shipping_city')
            ->select('shipping_city_code', 'shipping_city')->distinct()->orderBy('shipping_city')->get();
        $availableStatuses = (clone $base)->select('status')->distinct()->orderBy('status')->pluck('status');
        $search = trim($filters['q'] ?? '');
        $filtered = (clone $base)
            ->when($search !== '', function ($query) use ($search) {
                $id = preg_match('/^VN-0*(\d+)$/i', $search, $match) ? (int) $match[1] : null;
                $query->where(fn ($q) => $q->where('tracking_number', 'like', '%'.$search.'%')
                    ->orWhere('shipping_address', 'like', '%'.$search.'%')
                    ->orWhereHas('seller', fn ($seller) => $seller->where('name', 'like', '%'.$search.'%'))
                    ->when($id, fn ($number) => $number->orWhereKey($id)));
            })
            ->when($filters['area'] ?? null, fn ($query, $area) => $query->where('shipping_city_code', $area))
            ->when($filters['date_from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $stages = match ($lane) {
            'incoming' => ['pickup' => ['ready_for_pickup'], 'arriving' => ['picked_up']],
            'sorting' => ['to-sort' => ['at_sorting_center'], 'sorted' => ['sorted'],
                'transit' => ['in_transit_to_hub'], 'legacy' => ['to_soc5', 'to_soc6']],
            'delivery' => ['inbound' => ['sorted', 'in_transit_to_hub'], 'at-hub' => ['at_destination_hub'],
                'assigned' => ['assigned_to_rider'], 'out' => ['out_for_delivery']],
            default => [],
        };
        $stageCounts = collect(array_keys($stages))->mapWithKeys(fn ($stage) => [$stage =>
            $this->forStage(clone $filtered, $stage, $stages, $lane, $center->id)->count()]);
        $selectedStage = $filters['stage'] ?? 'all';
        abort_unless($selectedStage === 'all' || isset($stages[$selectedStage]), 422, 'Unknown parcel stage.');
        $orders = ($selectedStage === 'all' ? $filtered : $this->forStage($filtered, $selectedStage, $stages, $lane, $center->id))
            ->with(['seller:id,name,phone_number', 'seller.sellerDetail', 'courier:id,name', 'linehaulRider:id,name', 'deliveryCourier:id,name', 'destinationLogisticsCenter', 'nextRouteCheckpoint', 'routePlan.stops.checkpoint'])
            ->latest()
            ->paginate(15)->withQueryString();

        $couriers = CourierDetail::query()
            ->where('logistics_center_id', $center->id)
            ->whereHas('user', fn ($query) => $query->where('role', 'courier')
                ->where('status', 'approved')->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->with('user:id,name')
            ->get()
            ->sortBy(fn ($courier) => $courier->user->name)
            ->values();
        $truckRiders = $couriers->filter(fn (CourierDetail $courier) => $courier->vehicle_type === 'Truck')->values();
        $localRiders = $couriers->filter(fn (CourierDetail $courier) => $courier->vehicle_type !== 'Truck')->values();

        $workload = Order::query()->where('destination_logistics_center_id', $center->id)
            ->whereIn('status', ['assigned_to_rider', 'out_for_delivery'])
            ->select('delivery_courier_id', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_courier_id')->pluck('total', 'delivery_courier_id');

        return view('logistics.dispatch.index', compact('center', 'orders', 'couriers', 'truckRiders', 'localRiders', 'lane',
            'filters', 'areas', 'availableStatuses', 'stageCounts', 'selectedStage', 'workload'));
    }

    private function forStage($query, string $stage, array $stages, string $lane, int $centerId)
    {
        if ($lane === 'delivery' && $stage === 'at-hub') {
            return $query->where(fn ($q) => $q->where('status', 'at_destination_hub')
                ->orWhere(fn ($local) => $local->where('status', 'sorted')->where('logistics_center_id', $centerId)));
        }
        if ($lane === 'delivery' && $stage === 'inbound') {
            return $query->where(fn ($q) => $q->where('status', 'in_transit_to_hub')
                ->orWhere(fn ($crossHub) => $crossHub->where('status', 'sorted')
                    ->where('logistics_center_id', '!=', $centerId)));
        }

        return $query->whereIn('status', $stages[$stage]);
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
            abort_unless($record->pickup_request_status === 'verified', 409, 'Verify the pickup request before assigning a rider.');
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

    public function verifyPickupRequest(Request $request, int $order): RedirectResponse
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $order, $centerId) {
            $record = Order::query()->where('logistics_center_id', $centerId)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'ready_for_pickup' && ! $record->courier_id
                && $record->pickup_request_status === 'pending', 409, 'This request is no longer awaiting verification.');
            $record->update(['pickup_request_status' => 'verified', 'pickup_verified_at' => now(), 'pickup_decline_reason' => null]);
            DB::table('order_logistics_assignments')->insert([
                'order_id' => $record->id, 'actor_id' => $request->user()->id,
                'action' => 'pickup_request_verified', 'to_logistics_center_id' => $centerId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Pickup request verified. Assign a pickup rider next.');
    }

    public function declinePickupRequest(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $order, $centerId, $data) {
            $record = Order::query()->where('logistics_center_id', $centerId)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'ready_for_pickup' && ! $record->courier_id
                && $record->pickup_request_status === 'pending', 409, 'This request is no longer awaiting verification.');
            $record->update(['status' => 'preparing', 'pickup_request_status' => 'declined',
                'pickup_decline_reason' => $data['reason'], 'pickup_verified_at' => null]);
            $record->statusEvents()->create(['user_id' => $request->user()->id,
                'from_status' => 'ready_for_pickup', 'to_status' => 'preparing',
                'note' => 'Pickup request declined: '.$data['reason']]);
            Notification::create([
                'user_id' => $record->seller_id, 'type' => 'pickup_request_declined',
                'title' => 'Pickup request needs attention '.$record->number,
                'message' => $data['reason'].'. Prepare the parcel and mark it ready again.',
                'link' => route('seller.orders.show', $record),
            ]);
        }, 3);

        return back()->with('success', 'Pickup request sent back to the seller for preparation.');
    }

    public function markArrived(Request $request, int $order): RedirectResponse
    {
        $this->transition($request, $order, 'picked_up', 'at_sorting_center');

        return back()->with('success', 'Arrival at the sorting center recorded.');
    }

    public function markSorted(Request $request, int $order, \App\Services\LogisticsRoutePlanner $planner): RedirectResponse
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $order, $centerId, $planner) {
            $record = Order::query()->where('logistics_center_id', $centerId)
                ->with(['logisticsCenter', 'destinationLogisticsCenter'])
                ->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'at_sorting_center', 409, 'This order is no longer awaiting sorting.');
            abort_unless($record->courier_id, 409, 'A pickup courier is required.');

            $plan = $record->destination_logistics_center_id !== $centerId
                ? $planner->selectPlan($record)
                : null;
            $firstStop = $plan?->stops->first();
            $record->route_plan_id = $plan?->id;
            $record->route_step = $firstStop ? 1 : null;
            $record->next_route_checkpoint_id = $firstStop?->checkpoint_id;
            $record->status = 'sorted';
            $record->save();
            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => 'at_sorting_center',
                'to_status' => 'sorted',
                'note' => $firstStop
                    ? 'Parcel sorted at '.$request->user()->logisticsCenterDetail->business_name.'. Planned virtual checkpoint: '.$firstStop->checkpoint->name.' ('.$firstStop->checkpoint->code.').'
                    : ($plan
                        ? 'Parcel sorted at '.$request->user()->logisticsCenterDetail->business_name.'. The configured route goes directly to '.$record->destinationLogisticsCenter?->business_name.'.'
                        : 'Parcel sorted at '.$request->user()->logisticsCenterDetail->business_name.'. No active route plan is configured for the destination Main Hub.'),
            ]);
        }, 3);

        return back()->with('success', 'Sorting completed. The next virtual checkpoint follows the active route plan for this Main Hub pair.');
    }

    public function assignLinehaulRider(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'rider_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);
        $centerId = $request->user()->logisticsCenterDetail->id;

        DB::transaction(function () use ($request, $order, $data, $centerId) {
            $record = Order::query()->where('logistics_center_id', $centerId)
                ->lockForUpdate()->findOrFail($order);
            $validPlan = $record->routePlan()->where('from_logistics_center_id', $centerId)
                ->where('to_logistics_center_id', $record->destination_logistics_center_id)
                ->exists();
            abort_unless($record->status === 'sorted'
                && $record->destination_logistics_center_id
                && (int) $record->destination_logistics_center_id !== (int) $centerId
                && $validPlan, 409,
                'A sorted cross-hub parcel with a configured route plan is required for truck assignment.');

            $riderDetail = CourierDetail::query()->where('logistics_center_id', $centerId)
                ->where('user_id', $data['rider_id'])->where('vehicle_type', 'Truck')
                ->with('user')->firstOrFail();
            $rider = $riderDetail->user;
            abort_unless($rider && $rider->role === 'courier' && $rider->status === 'approved'
                && ! $rider->archived_at && (! $rider->account_status || $rider->account_status === 'active'),
                422, 'Select an active, approved Truck Rider linked to this Main Hub.');

            $previousRiderId = $record->linehaul_rider_id;
            if ((int) $previousRiderId === (int) $rider->id) {
                return;
            }

            $record->linehaul_rider_id = $rider->id;
            $record->save();

            DB::table('order_logistics_assignments')->insert([
                'order_id' => $record->id,
                'actor_id' => $request->user()->id,
                'action' => $previousRiderId ? 'linehaul_rider_reassigned' : 'linehaul_rider_assigned',
                'from_logistics_center_id' => $centerId,
                'to_logistics_center_id' => $record->destination_logistics_center_id,
                'courier_id' => $rider->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Truck Rider assigned to the configured linehaul route. SH arrival and sorting are recorded by the separate SH scanner.');
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
            abort_unless($courier->vehicle_type !== 'Truck' && $user && $user->role === 'courier' && $user->status === 'approved'
                && ! $user->archived_at && (! $user->account_status || $user->account_status === 'active'),
                422, 'Select an active approved courier from your center.');

            $this->assignDeliveryLocked($record, $user->id, $request, $centerId, $local);
        }, 3);

        return back()->with('success', 'Delivery rider assigned. Waiting for the rider app workflow.');
    }

    public function assignDeliveryCouriersBulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1|max:100',
            'order_ids.*' => 'required|integer|distinct',
            'courier_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);
        $centerId = $request->user()->logisticsCenterDetail->id;
        DB::transaction(function () use ($request, $data, $centerId) {
            $courier = CourierDetail::query()->where('logistics_center_id', $centerId)
                ->where('user_id', $data['courier_id'])->with('user')->firstOrFail();
            $user = $courier->user;
            abort_unless($courier->vehicle_type !== 'Truck' && $user && $user->role === 'courier' && $user->status === 'approved'
                && ! $user->archived_at && (! $user->account_status || $user->account_status === 'active'),
                422, 'Select an active approved rider from your center.');
            $orders = Order::query()->where('destination_logistics_center_id', $centerId)
                ->whereIn('id', $data['order_ids'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($orders->count() === count($data['order_ids']), 403, 'One or more parcels do not belong to this Main Hub.');
            abort_unless($orders->pluck('shipping_city_code')->unique()->count() === 1, 422,
                'Select parcels for the same delivery area.');
            foreach ($orders as $record) {
                $local = $record->logistics_center_id === $centerId && $record->status === 'sorted';
                abort_unless(($local || $record->status === 'at_destination_hub') && ! $record->delivery_courier_id,
                    409, 'One or more parcels are no longer ready for assignment.');
                $this->assignDeliveryLocked($record, $user->id, $request, $centerId, $local);
            }
        }, 3);

        return back()->with('success', count($data['order_ids']).' parcels assigned to the delivery rider.');
    }

    private function assignDeliveryLocked(Order $record, int $courierId, Request $request, int $centerId, bool $local): void
    {
        $record->delivery_courier_id = $courierId;
        $record->status = 'assigned_to_rider';
        $record->save();
        $record->statusEvents()->create([
            'user_id' => $request->user()->id,
            'from_status' => $local ? 'sorted' : 'at_destination_hub',
            'to_status' => 'assigned_to_rider',
            'note' => 'Delivery rider assigned by '.$request->user()->logisticsCenterDetail->business_name.'.',
        ]);
        DB::table('order_logistics_assignments')->insert([
            'order_id' => $record->id, 'actor_id' => $request->user()->id,
            'action' => 'delivery_courier_assigned', 'to_logistics_center_id' => $centerId,
            'courier_id' => $courierId, 'created_at' => now(), 'updated_at' => now(),
        ]);
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
