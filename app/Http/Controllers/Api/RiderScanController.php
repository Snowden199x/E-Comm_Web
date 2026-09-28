<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Services\OrderScanWorkflow;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'destinationLogisticsCenter:id,business_name,municipality,province',
            'nextRouteCheckpoint', 'routePlan.stops.checkpoint',
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
                        ->whereNotNull('destination_logistics_center_id')
                        ->whereColumn('destination_logistics_center_id', '!=', 'logistics_center_id'))));
            if ($isTruck) {
                $query->orWhere(fn ($linehaul) => $linehaul->where('linehaul_rider_id', $rider->id)
                    ->where('logistics_center_id', $center->id)
                    ->whereNotNull('route_plan_id')
                    ->whereIn('status', ['sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub']));
            } else {
                $query->orWhere(fn ($delivery) => $delivery->where('delivery_courier_id', $rider->id)
                    ->where('destination_logistics_center_id', $center->id)
                    ->whereIn('status', ['assigned_to_rider', 'out_for_delivery']));
            }
        })->with($relations)
            ->latest()->limit(100)->get();

        return response()->json(['assignments' => $orders->map(function (Order $order) use ($rider, $center, $isTruck) {
            $linehaul = $isTruck && (int) $order->linehaul_rider_id === (int) $rider->id
                && in_array($order->status, ['sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub'], true);
            $pickup = ! $linehaul && (int) $order->courier_id === (int) $rider->id
                && (int) $order->logistics_center_id === (int) $center->id
                && in_array($order->status, ['ready_for_pickup', 'picked_up', 'sorted', 'to_soc5', 'to_soc6'], true);
            $seller = $order->seller;
            $detail = $seller?->sellerDetail;
            $origin = $order->logisticsCenter;
            $destination = $order->destinationLogisticsCenter;

            return [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'assignment' => $linehaul ? 'linehaul' : ($pickup ? 'pickup' : 'delivery'),
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
                ]),
                'pickup_center' => $origin?->business_name,
                'destination_center' => $destination?->business_name,
                'planned_route' => $linehaul ? [
                    'current_step' => $order->route_step,
                    'next_checkpoint' => $order->nextRouteCheckpoint ? [
                        'code' => $order->nextRouteCheckpoint->code,
                        'name' => $order->nextRouteCheckpoint->name,
                        'municipality' => $order->nextRouteCheckpoint->municipality,
                        'province' => $order->nextRouteCheckpoint->province,
                    ] : null,
                    'origin_main_hub' => $origin?->business_name,
                    'checkpoints' => $order->routePlan?->stops->map(fn ($stop) => [
                        'position' => $stop->position,
                        'code' => $stop->checkpoint?->code,
                        'name' => $stop->checkpoint?->name,
                        'municipality' => $stop->checkpoint?->municipality,
                        'province' => $stop->checkpoint?->province,
                    ])->values() ?? [],
                    'destination_main_hub' => $destination?->business_name,
                ] : null,
            ];
        })]);
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
