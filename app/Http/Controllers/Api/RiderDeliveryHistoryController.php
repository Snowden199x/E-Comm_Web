<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\OrderDeliveryProof;
use App\Services\RiderAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The parcels this Rider handed over on one day. The Home screen reads the
 * summary count ("completed today"); the Delivery history screen lists the
 * rows. A delivery counts when the handover proof was captured, which is the
 * moment the parcel left the Rider's hands.
 */
class RiderDeliveryHistoryController extends Controller
{
    public function index(Request $request, RiderAccessService $access): JsonResponse
    {
        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');

        $requested = (string) $request->query('date', '');
        $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)
            ? Carbon::createFromFormat('Y-m-d', $requested)->startOfDay()
            : now()->startOfDay();

        $proofs = OrderDeliveryProof::query()
            ->where('rider_id', $rider->id)
            ->whereBetween('captured_at', [$day, $day->copy()->endOfDay()])
            ->with([
                'order:id,tracking_number,shipping_address,buyer_id,cod_amount,money_status',
                'order.buyer:id,name',
            ])
            ->latest('captured_at')
            ->limit(200)
            ->get()
            ->unique('order_id');

        $cashCents = 0;
        $deliveries = $proofs->map(function (OrderDeliveryProof $proof) use (&$cashCents) {
            $order = $proof->order;
            if (! $order) {
                return null;
            }

            $cash = in_array($order->money_status, ['collected_by_rider', 'remitted'], true)
                && $order->cod_amount !== null;
            if ($cash) {
                $cashCents += (int) round((float) $order->cod_amount * 100);
            }

            return [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'buyer_name' => $order->buyer?->name,
                'recipient_name' => $proof->recipient_name,
                'address' => $order->shipping_address,
                'cash_collected' => $cash ? '₱'.number_format((float) $order->cod_amount, 2) : null,
                'delivered_at_label' => $proof->captured_at?->format('g:i A'),
            ];
        })->filter()->values();

        return response()->json([
            'date' => $day->toDateString(),
            'summary' => [
                'count' => $deliveries->count(),
                'cash_total' => '₱'.number_format($cashCents / 100, 2),
                'cash_total_value' => number_format($cashCents / 100, 2, '.', ''),
            ],
            'deliveries' => $deliveries,
        ]);
    }
}
