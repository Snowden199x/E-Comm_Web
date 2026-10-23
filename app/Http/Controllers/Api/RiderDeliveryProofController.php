<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\OrderDeliveryProof;
use App\Services\OrderScanWorkflow;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RiderDeliveryProofController extends Controller
{
    public function store(Request $request, RiderAccessService $access, OrderScanWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'scan_key' => ['required', 'uuid'],
            'recipient_name' => ['required', 'string', 'min:2', 'max:120'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');
        $order = Order::query()->where('tracking_number', $data['tracking_number'])->firstOrFail();
        abort_unless((int) $order->delivery_courier_id === (int) $rider->id
            && (int) $order->destination_logistics_center_id === (int) $center->id, 403,
            'This parcel is not assigned to you for delivery.');

        $path = $request->file('photo')->store('private/delivery-proofs/'.$order->id, 'local');
        try {
            $result = DB::transaction(function () use ($rider, $center, $order, $data, $path, $workflow) {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $existing = OrderDeliveryProof::query()->where('order_id', $locked->id)->first();
                if ($existing) {
                    abort_unless($existing->scan_key === $data['scan_key']
                        && (int) $existing->rider_id === (int) $rider->id, 409,
                        'This delivery already has proof recorded.');
                    return [$workflow->record($rider, $center, [
                        'tracking_number' => $locked->tracking_number,
                        'scan_type' => 'delivered', 'scan_key' => $data['scan_key'],
                    ]), false];
                }
                abort_unless($locked->status === 'out_for_delivery'
                    && ! $locked->logisticsExceptions()->where('status', 'open')->exists(), 409,
                    'This parcel is not ready for delivery completion.');
                $stationId = $rider->courierDetail?->station_hub_id;
                abort_unless($stationId && (int) $locked->destination_station_id === (int) $stationId
                    && $locked->facilityStops()->where('facility_id', $stationId)
                        ->whereNotNull('arrived_at')->whereNotNull('sorted_at')->exists(), 409,
                    'The destination Station must receive the parcel before delivery proof can be recorded.');
                OrderDeliveryProof::create([
                    'order_id' => $locked->id, 'rider_id' => $rider->id,
                    'station_hub_id' => $stationId, 'scan_key' => $data['scan_key'],
                    'recipient_name' => $data['recipient_name'], 'photo_path' => $path,
                    'notes' => $data['notes'] ?? null, 'captured_at' => now(),
                ]);
                return [$workflow->record($rider, $center, [
                    'tracking_number' => $locked->tracking_number,
                    'scan_type' => 'delivered', 'scan_key' => $data['scan_key'],
                ]), true];
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if (! $result[1]) {
            Storage::disk('local')->delete($path);
        }

        return response()->json($result[0]);
    }
}
