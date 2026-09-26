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

        $orders = Order::query()->where(function ($query) use ($rider, $center) {
            $query->where(fn ($pickup) => $pickup->where('courier_id', $rider->id)
                ->where('logistics_center_id', $center->id)
                ->whereIn('status', ['ready_for_pickup', 'picked_up']))
                ->orWhere(fn ($delivery) => $delivery->where('delivery_courier_id', $rider->id)
                    ->where('destination_logistics_center_id', $center->id)
                    ->whereIn('status', ['assigned_to_rider', 'out_for_delivery']));
        })->with(['logisticsCenter:id,business_name,municipality,province',
            'destinationLogisticsCenter:id,business_name,municipality,province',
            'seller:id,name,phone_number', 'seller.sellerDetail', 'buyer:id,name,phone_number'])
            ->latest()->limit(100)->get();

        return response()->json(['assignments' => $orders->map(function (Order $order) use ($rider, $center) {
            $pickup = $order->courier_id === $rider->id && $order->logistics_center_id === $center->id
                && in_array($order->status, ['ready_for_pickup', 'picked_up'], true);
            $seller = $order->seller;
            $detail = $seller?->sellerDetail;

            return [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'assignment' => $pickup ? 'pickup' : 'delivery',
                'stop' => $pickup ? [
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
                ],
                'pickup_center' => $order->logisticsCenter?->business_name,
                'destination_center' => $order->destinationLogisticsCenter?->business_name,
            ];
        })]);
    }

    public function store(Request $request, RiderAccessService $access, OrderScanWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:50'],
            'scan_type' => ['required', Rule::in(['pickup', 'origin_arrival', 'out_for_delivery'])],
            'scan_key' => ['required', 'uuid'],
        ]);

        $center = $access->approvedCenter($request->user());
        abort_unless($center, 403, 'Approved logistics membership required.');
        $result = $workflow->record($request->user(), $center, $data);

        return response()->json($result);
    }
}
