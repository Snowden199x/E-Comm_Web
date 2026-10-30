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

class RiderDeliveryRescheduleController extends Controller
{
    /** Book the next delivery attempt after the daily window closed. */
    public function store(Request $request, int $order, RiderAccessService $access, DeliveryCutoff $cutoff): JsonResponse
    {
        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');

        $result = DB::transaction(function () use ($order, $rider, $center, $cutoff) {
            $record = Order::query()
                ->where('destination_logistics_center_id', $center->id)
                ->where('delivery_courier_id', $rider->id)
                ->lockForUpdate()->findOrFail($order);

            abort_unless($record->status === 'delivery_failed', 409,
                'Only a parcel whose delivery window has closed can be rescheduled.');

            $date = today()->addDay();
            $label = $date->format('j M Y');

            $record->status = 'assigned_to_rider';
            $record->delivery_scheduled_for = $date;
            // Quiet save: the generic "rider assigned" buyer and seller updates
            // would be misleading here, so the reschedule gets its own notices.
            $record->saveQuietly();

            $record->statusEvents()->create([
                'user_id' => $rider->id,
                'from_status' => 'delivery_failed',
                'to_status' => 'assigned_to_rider',
                'note' => 'Rider rescheduled the delivery for '.$label.' after the '.$cutoff->label().' window closed.',
            ]);

            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => 'Delivery rescheduled '.$record->number,
                'message' => 'Your parcel was not delivered before the '.$cutoff->label()
                    .' cutoff. The next delivery attempt is scheduled for '.$label.'.',
                'link' => route('buyer.orders.show', $record),
            ]);
            Notification::create([
                'user_id' => $record->seller_id,
                'type' => 'shipment_update',
                'title' => 'Delivery rescheduled '.$record->number,
                'message' => 'The rider did not finish this delivery before the '.$cutoff->label()
                    .' window and rescheduled it for '.$label.'.',
                'link' => route('seller.orders.show', $record),
            ]);
            $hubUserId = $record->destinationLogisticsCenter?->user_id;
            if ($hubUserId) {
                Notification::create([
                    'user_id' => $hubUserId,
                    'type' => 'shipment_update',
                    'title' => 'Delivery rescheduled '.$record->number,
                    'message' => 'Rider '.$rider->name.' rescheduled this parcel for '.$label.'. It stays with the same rider for that day\'s run.',
                    'link' => route('lgp.station.delivery-queue'),
                ]);
            }

            return [
                'order_id' => $record->id,
                'tracking_number' => $record->tracking_number,
                'status' => $record->status,
                'delivery_scheduled_for' => $date->toDateString(),
                'delivery_scheduled_for_label' => $label,
                'message' => 'Delivery rescheduled for '.$label.'. Scan the parcel out for delivery that day.',
            ];
        }, 3);

        return response()->json($result);
    }
}
