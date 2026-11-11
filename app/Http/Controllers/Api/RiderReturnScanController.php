<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Services\DeliveryCutoff;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Scanner-style endpoint for the Rider app: after a failed delivery attempt the Rider scans the parcel
 * back at the destination Station Hub. This records the physical return (it used to be a status note
 * only), keeps the order in `delivery_failed` so the final attempt can be booked, and is idempotent by
 * `scan_key` like the other Rider scans.
 */
class RiderReturnScanController extends Controller
{
    public function store(Request $request, int $order, RiderAccessService $access): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'scan_key' => ['required', 'uuid'],
        ]);

        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');

        $result = DB::transaction(function () use ($data, $order, $rider, $center) {
            $record = Order::query()
                ->whereKey($order)
                ->where('tracking_number', trim($data['tracking_number']))
                ->where('destination_logistics_center_id', $center->id)
                ->where('delivery_courier_id', $rider->id)
                ->lockForUpdate()->firstOrFail();

            $existing = DB::table('order_scan_events')
                ->where('order_id', $record->id)->where('scan_key', $data['scan_key'])->first();
            if ($existing) {
                abort_unless($existing->scan_type === 'returned_to_station' && (int) $existing->actor_id === (int) $rider->id,
                    409, 'This scan key was already used.');

                return $this->payload($record, $center->business_name, true);
            }

            abort_unless($record->status === 'delivery_failed', 409,
                'Only a parcel whose delivery attempt failed can be scanned back to the Station Hub.');

            $note = 'Rider '.$rider->name.' scanned the parcel back at '.$center->business_name
                .'. It is held at the hub for the next delivery attempt.';

            $record->statusEvents()->create([
                'user_id' => $rider->id,
                'from_status' => 'delivery_failed',
                'to_status' => 'delivery_failed',
                'note' => $note,
            ]);
            DB::table('order_scan_events')->insert([
                'order_id' => $record->id,
                'actor_id' => $rider->id,
                'logistics_center_id' => $record->destination_logistics_center_id,
                'scan_type' => 'returned_to_station',
                'from_status' => 'delivery_failed',
                'to_status' => 'delivery_failed',
                'scan_key' => $data['scan_key'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => 'Parcel back at the hub '.$record->number,
                'message' => 'Your parcel was scanned back at '.$center->business_name
                    .' after the failed attempt. It is kept there for the next delivery attempt.',
                'link' => route('buyer.orders.show', $record),
            ]);
            Notification::create([
                'user_id' => $record->seller_id,
                'type' => 'shipment_update',
                'title' => 'Parcel back at the hub '.$record->number,
                'message' => 'The rider scanned the parcel back at '.$center->business_name.' after a failed delivery.',
                'link' => route('seller.orders.show', $record),
            ]);
            if ($center->user_id) {
                Notification::create([
                    'user_id' => $center->user_id,
                    'type' => 'shipment_update',
                    'title' => 'Parcel received back '.$record->number,
                    'message' => 'Rider '.$rider->name.' scanned this parcel back at your hub after a failed attempt. Schedule the next attempt.',
                    'link' => route('lgp.station.delivery-queue'),
                ]);
            }

            return $this->payload($record, $center->business_name, false);
        }, 3);

        return response()->json($result);
    }

    private function payload(Order $order, string $station, bool $duplicate): array
    {
        $maxAttempts = DeliveryCutoff::MAX_DELIVERY_ATTEMPTS + (int) $order->delivery_extra_attempts;

        return [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'status' => $order->status,
            'station' => $station,
            'action' => 'returned_to_station',
            'delivery_attempts' => (int) $order->delivery_attempts,
            'max_attempts' => $maxAttempts,
            'attempts_remaining' => max(0, $maxAttempts - (int) $order->delivery_attempts),
            'duplicate' => $duplicate,
            'message' => 'Parcel recorded back at the Station Hub. Book the next attempt with the reschedule endpoint.',
        ];
    }
}
