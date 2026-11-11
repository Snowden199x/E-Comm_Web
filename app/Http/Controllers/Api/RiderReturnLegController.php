<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Services\ReturnTripService;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rider app scans for the approved return trip: collect the parcel from the Buyer, then hand it back
 * to the Seller. Same request shape as the other rider scans (tracking number + UUID key), so the app
 * can reuse its scanner screen.
 */
class RiderReturnLegController extends Controller
{
    public function pickup(Request $request, int $order, RiderAccessService $access, ReturnTripService $trips): JsonResponse
    {
        return response()->json($trips->pickup(...$this->resolve($request, $order, $access)));
    }

    public function handover(Request $request, int $order, RiderAccessService $access, ReturnTripService $trips): JsonResponse
    {
        return response()->json($trips->handover(...$this->resolve($request, $order, $access)));
    }

    /** @return array{0: \App\Models\User, 1: Order, 2: string} */
    private function resolve(Request $request, int $order, RiderAccessService $access): array
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'scan_key' => ['required', 'uuid'],
        ]);

        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center, 403, 'Approved logistics membership required.');

        $record = Order::query()
            ->whereKey($order)
            ->where('tracking_number', trim($data['tracking_number']))
            ->where('destination_logistics_center_id', $center->id)
            ->firstOrFail();

        return [$rider, $record, $data['scan_key']];
    }
}
