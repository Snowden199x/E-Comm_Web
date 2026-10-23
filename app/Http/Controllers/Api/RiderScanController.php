<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\RiderTask;
use App\Services\OrderScanWorkflow;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiderScanController extends Controller
{
    public function assignments(Request $request, RiderAccessService $access): JsonResponse
    {
        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');
        $isTruck = $rider->courierDetail?->vehicle_type === 'Truck';
        $relations = [
            'logisticsCenter:id,business_name,municipality,province',
            'logisticsCenter.mainHub',
            'destinationLogisticsCenter:id,business_name,municipality,province',
            'nextRouteCheckpoint', 'routePlan.stops.checkpoint',
            'facilityStops.facility',
            'seller:id,name,phone_number', 'seller.sellerDetail',
        ];
        if (! $isTruck) {
            $relations[] = 'buyer:id,name,phone_number';
        }

        $orders = Order::query()->where(function ($query) use ($rider, $center, $isTruck) {
            $query->where(fn ($pickup) => $pickup->where('courier_id', $rider->id)
                ->where('logistics_center_id', $center->id)
                ->where(fn ($stage) => $stage->whereIn('status', ['ready_for_pickup', 'picked_up'])
                    ->orWhere(fn ($interHub) => $interHub->whereIn('status', ['sorted', 'to_soc5', 'to_soc6'])
                        ->whereDoesntHave('facilityStops')
                        ->whereNotNull('destination_logistics_center_id')
                        ->whereColumn('destination_logistics_center_id', '!=', 'logistics_center_id'))));
            if ($isTruck) {
                $query->orWhere(fn ($linehaul) => $linehaul->where('linehaul_rider_id', $rider->id)
                    ->where('logistics_center_id', $center->id)
                    ->where(fn ($route) => $route->whereNotNull('route_plan_id')->orWhereHas('facilityStops'))
                    ->whereIn('status', ['sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub']));
            } else {
                $query->orWhere(fn ($delivery) => $delivery->where('delivery_courier_id', $rider->id)
                    ->where('destination_logistics_center_id', $center->id)
                    ->whereIn('status', ['assigned_to_rider', 'out_for_delivery']));
            }
        })->with($relations)
            ->latest()->limit(100)->get();

        $tasks = RiderTask::query()->whereIn('order_id', $orders->pluck('id'))
            ->where('rider_id', $rider->courierDetail?->id)
            ->whereIn('status', ['assigned', 'in_progress', 'failed'])
            ->latest('id')->get()->unique('order_id')->keyBy('order_id');

        $assignments = $orders->map(function (Order $order) use ($rider, $center, $isTruck, $tasks) {
            $facilityStops = $order->facilityStops;
            $linehaul = $isTruck && (int) $order->linehaul_rider_id === (int) $rider->id
                && in_array($order->status, ['sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub'], true);
            $pickup = ! $linehaul && (int) $order->courier_id === (int) $rider->id
                && (int) $order->logistics_center_id === (int) $center->id
                && (in_array($order->status, ['ready_for_pickup', 'picked_up'], true)
                    || ($facilityStops->isEmpty() && in_array($order->status, ['sorted', 'to_soc5', 'to_soc6'], true)));
            $seller = $order->seller;
            $detail = $seller?->sellerDetail;
            $origin = $order->logisticsCenter;
            $destination = $order->destinationLogisticsCenter;
            $nextFacility = $facilityStops->first(fn ($stop) => ! $stop->arrived_at);
            $deliveryFacility = $facilityStops->last()?->facility;
            $scanTarget = null;
            $awaitingOriginDeparture = $linehaul && $order->status === 'sorted'
                && $facilityStops->isNotEmpty();
            if ($awaitingOriginDeparture) {
                $originHub = $origin?->mainHub;
                if ($originHub?->is_active) {
                    $scanTarget = $this->facilityScanTarget($originHub);
                }
            }
            if ($linehaul && ! $awaitingOriginDeparture) {
                $lastPosition = (int) $facilityStops->max('position');
                $previousStop = null;
                foreach ($facilityStops as $facilityStop) {
                    if (! $facilityStop->arrived_at) {
                        $scanTarget = $previousStop && ! $previousStop->departed_at
                            ? $this->facilityScanTarget($previousStop->facility)
                            : $this->facilityScanTarget($facilityStop->facility);
                        break;
                    }
                    if ((int) $facilityStop->position < $lastPosition
                        && $facilityStop->sorted_at && ! $facilityStop->departed_at) {
                        $scanTarget = $this->facilityScanTarget($facilityStop->facility);
                        break;
                    }
                    $previousStop = $facilityStop;
                }
            }

            return [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'assignment' => $linehaul ? 'linehaul' : ($pickup ? 'pickup' : 'delivery'),
                'task' => isset($tasks[$order->id]) ? [
                    'id' => $tasks[$order->id]->id,
                    'status' => $tasks[$order->id]->status,
                    'failure_reason' => $tasks[$order->id]->failure_reason,
                ] : null,
                'stop' => $linehaul ? [
                    'name' => $origin?->business_name ?: 'Origin Main Hub',
                    'address' => implode(', ', array_filter([$origin?->municipality, $origin?->province])),
                ] : ($pickup ? [
                    'name' => $detail?->business_name ?: $seller?->name,
                    'phone' => $seller?->phone_number,
                    'address' => implode(', ', array_filter([
                        $detail?->house_no, $detail?->street, $detail?->barangay,
                        $detail?->municipality, $detail?->province, $detail?->zip_code,
                    ])),
                ] : [
                    'name' => $order->buyer?->name,
                    'phone' => $order->buyer?->phone_number,
                    'address' => $order->shipping_address,
                    'latitude' => $order->shipping_latitude,
                    'longitude' => $order->shipping_longitude,
                ]),
                'pickup_center' => $linehaul || $pickup ? $origin?->business_name
                    : ($deliveryFacility?->name ?: $destination?->business_name),
                'dispatch_facility' => ! $linehaul && ! $pickup && $deliveryFacility ? [
                    'name' => $deliveryFacility->name,
                    'municipality' => $deliveryFacility->municipality,
                    'province' => $deliveryFacility->province,
                    'address' => $deliveryFacility->address,
                ] : null,
                'delivery_proof_required' => ! $linehaul && ! $pickup
                    && $facilityStops->isNotEmpty() && $order->destination_station_id !== null,
                'destination_center' => $destination?->business_name,
                'planned_route' => $linehaul ? [
                    'current_step' => $facilityStops->isNotEmpty() ? $facilityStops->filter(fn ($stop) => $stop->arrived_at)->count() + 1 : $order->route_step,
                    'next_checkpoint' => $nextFacility ? [
                        'facility_id' => $nextFacility->facility?->id,
                        'code' => null,
                        'name' => $nextFacility->facility?->name,
                        'municipality' => $nextFacility->facility?->municipality,
                        'province' => $nextFacility->facility?->province,
                    ] : ($order->nextRouteCheckpoint ? [
                        'code' => $order->nextRouteCheckpoint->code,
                        'name' => $order->nextRouteCheckpoint->name,
                        'municipality' => $order->nextRouteCheckpoint->municipality,
                        'province' => $order->nextRouteCheckpoint->province,
                    ] : null),
                    'origin_main_hub' => $origin?->business_name,
                    'checkpoints' => $facilityStops->isNotEmpty() ? $facilityStops->map(fn ($stop) => [
                        'position' => $stop->position,
                        'facility_id' => $stop->facility?->id,
                        'code' => null,
                        'name' => $stop->facility?->name,
                        'municipality' => $stop->facility?->municipality,
                        'province' => $stop->facility?->province,
                    ])->values() : ($order->routePlan?->stops->map(fn ($stop) => [
                        'position' => $stop->position,
                        'code' => $stop->checkpoint?->code,
                        'name' => $stop->checkpoint?->name,
                        'municipality' => $stop->checkpoint?->municipality,
                        'province' => $stop->checkpoint?->province,
                    ])->values() ?? []),
                    'destination_main_hub' => $destination?->business_name,
                    'scan_target' => $scanTarget,
                ] : null,
            ];
        });

        return response()->json([
            'assignments' => $assignments,
            'summary' => [
                'loaded' => $assignments->count(),
                'pickup' => $assignments->where('assignment', 'pickup')->count(),
                'delivery' => $assignments->where('assignment', 'delivery')->count(),
                'linehaul' => $assignments->where('assignment', 'linehaul')->count(),
            ],
        ]);
    }

    private function facilityScanTarget(?LogisticsFacility $facility): ?array
    {
        if (! $facility) {
            return null;
        }

        return [
            'facility_id' => $facility->id,
            'name' => $facility->name,
            'municipality' => $facility->municipality,
            'province' => $facility->province,
        ];
    }

    public function store(Request $request, RiderAccessService $access, OrderScanWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:50'],
            'scan_type' => ['required', Rule::in(['pickup', 'origin_arrival', 'soc5', 'soc6', 'destination_hub', 'out_for_delivery', 'delivered'])],
            'scan_key' => ['required', 'uuid'],
        ]);

        $center = $access->approvedCenter($request->user());
        abort_unless($center, 403, 'Approved logistics membership required.');
        $result = $workflow->record($request->user(), $center, $data);

        return response()->json($result);
    }
}
