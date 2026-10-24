<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\CrossCompanyManifest;
use App\Models\Profiles\LogisticsCompanyVehicle;
use App\Models\Profiles\ManualLogisticsHandoff;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\TransportDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrossCompanyManifestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->logisticsCenterDetail->company;
        $data = $request->validate([
            'handoff_ids' => ['required', 'array', 'min:1', 'max:100'],
            'handoff_ids.*' => ['required', 'integer', 'distinct'],
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'expected_arrival_at' => ['required', 'date', 'after:now'],
        ]);

        $manifest = DB::transaction(function () use ($request, $company, $data) {
            $ids = collect($data['handoff_ids'])->map(fn ($id) => (int) $id)->sort()->values();
            $handoffs = ManualLogisticsHandoff::query()->where('origin_company_id', $company->id)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($handoffs->count() === $ids->count(), 404);
            $first = $handoffs->first();
            foreach ($handoffs as $handoff) {
                abort_unless($handoff->status === 'awaiting_origin' && ! $handoff->manifest_id
                    && (int) $handoff->origin_facility_id === (int) $first->origin_facility_id
                    && (int) $handoff->destination_facility_id === (int) $first->destination_facility_id,
                    409, 'Choose parcels with the same sending and receiving Province Hubs that are ready for handoff.');
            }
            $origin = $first->originFacility;
            $destination = $first->destinationFacility;
            abort_unless($origin?->isProvinceHub() && $origin->is_active && $destination?->isProvinceHub()
                && $destination->is_active && $destination->company?->status === 'approved'
                && (int) $origin->logistics_company_id === (int) $company->id
                && (int) $destination->logistics_company_id === (int) $first->destination_company_id
                && (int) $first->destination_company_id !== (int) $company->id,
                409, 'Both Province Hubs must be active.');
            $driver = TransportDriverDetail::query()->where('province_hub_id', $origin->id)
                ->whereKey($data['driver_id'])->lockForUpdate()->firstOrFail();
            $driver->load('user');
            abort_unless($driver->status === 'available' && $driver->user?->status === 'approved'
                && $driver->user?->account_status === 'active'
                && $driver->license_expires_at?->toDateString() >= today()->toDateString(),
                409, 'Choose an approved available Driver at the sending hub.');
            $vehicle = LogisticsCompanyVehicle::query()->where('logistics_company_id', $company->id)
                ->where('province_hub_id', $origin->id)->whereKey($data['vehicle_id'])
                ->lockForUpdate()->firstOrFail();
            abort_unless($vehicle->status === 'available', 409, 'Choose an available vehicle at the sending hub.');

            $manifest = CrossCompanyManifest::create([
                'origin_company_id' => $company->id,
                'destination_company_id' => $first->destination_company_id,
                'origin_facility_id' => $origin->id,
                'destination_facility_id' => $destination->id,
                'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id,
                'status' => 'in_transit', 'expected_arrival_at' => $data['expected_arrival_at'],
                'departed_at' => now(), 'created_by' => $request->user()->id,
            ]);

            foreach ($handoffs as $handoff) {
                $order = Order::query()->whereKey($handoff->order_id)->lockForUpdate()->firstOrFail();
                $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $origin->id)->lockForUpdate()->firstOrFail();
                abort_unless($order->status === 'sorted' && $stop->sorted_at && ! $stop->departed_at
                    && ! $order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                    'A selected parcel is not sorted at the origin or has an open exception.');
                $stop->update(['departed_at' => now()]);
                $handoff->update(['manifest_id' => $manifest->id, 'status' => 'pending',
                    'initiated_by' => $request->user()->id, 'initiated_at' => now()]);
                $order->status = 'in_transit_to_hub';
                $order->saveQuietly();
                $note = 'Cross-company manifest CCM-'.str_pad((string) $manifest->id, 5, '0', STR_PAD_LEFT)
                    .' departed '.$origin->name.' for '.$destination->name.'. Receipt is pending.';
                $order->statusEvents()->create(['user_id' => $request->user()->id,
                    'from_status' => 'sorted', 'to_status' => $order->status, 'note' => $note]);
                foreach (array_unique(array_filter([$order->seller_id, $order->buyer_id,
                    $handoff->destinationCompany?->logisticsCenter?->user_id,
                    $destination->staff?->user_id])) as $userId) {
                    $link = (int) $userId === (int) $order->seller_id ? route('seller.orders.show', $order)
                        : ((int) $userId === (int) $order->buyer_id ? route('buyer.orders.show', $order)
                            : ((int) $userId === (int) $destination->staff?->user_id
                                ? route('lgp.province.tracking', ['q' => $order->tracking_number])
                                : route('lgp.company.handoffs')));
                    Notification::create(['user_id' => $userId, 'type' => 'logistics_handoff',
                        'title' => 'Parcel '.$order->number.' handoff', 'message' => $note, 'link' => $link]);
                }
            }
            $driver->update(['status' => 'on_trip']);
            $vehicle->update(['status' => 'on_trip']);
            Notification::create(['user_id' => $driver->user_id, 'type' => 'transport_manifest_assigned',
                'title' => 'Cross-company manifest CCM-'.str_pad((string) $manifest->id, 5, '0', STR_PAD_LEFT),
                'message' => $handoffs->count().' parcel(s) departed '.$origin->name.' for '.$destination->name.'.']);

            return $manifest;
        }, 3);

        return back()->with('success', 'Manifest CCM-'.str_pad((string) $manifest->id, 5, '0', STR_PAD_LEFT)
            .' departed with '.count($data['handoff_ids']).' parcel(s).');
    }
}
