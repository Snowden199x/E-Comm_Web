<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Services\FacilityScanWorkflow;
use App\Services\RiderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderFacilityScanController extends Controller
{
    public function store(
        Request $request,
        RiderAccessService $access,
        FacilityScanWorkflow $workflow,
    ): JsonResponse {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'facility_id' => ['required', 'integer', 'min:1'],
            'scan_key' => ['required', 'uuid'],
        ]);

        $rider = $request->user();
        $center = $access->approvedCenter($rider);
        abort_unless($center && $rider->courierDetail?->vehicle_type === 'Truck', 403,
            'An approved Truck Rider account is required.');

        $order = Order::query()
            ->where('tracking_number', trim($data['tracking_number']))
            ->where('linehaul_rider_id', $rider->id)
            ->where('logistics_center_id', $center->id)
            ->whereIn('status', ['sorted', 'in_transit_to_hub', 'at_destination_hub'])
            ->firstOrFail();

        $facility = LogisticsFacility::query()
            ->whereKey($data['facility_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $isOriginHub = $facility->kind === 'main_hub'
            && (int) $facility->logistics_center_id === (int) $order->logistics_center_id;
        $isAssignedRouteStop = $order->facilityStops()
            ->where('facility_id', $facility->id)->exists();

        abort_unless($isOriginHub || $isAssignedRouteStop, 403,
            'This facility is not an active stop on your assigned parcel route.');

        return response()->json($workflow->record(
            $rider,
            $facility,
            trim($data['tracking_number']),
            'auto',
            $data['scan_key'],
            $order,
        ));
    }
}
