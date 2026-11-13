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
use Illuminate\Validation\ValidationException;

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
        ], [
            'handoff_ids.required' => 'No parcels are selected. Tick at least one parcel in the queue.',
            'handoff_ids.min' => 'No parcels are selected. Tick at least one parcel in the queue.',
            'handoff_ids.max' => 'Too many parcels for one manifest. Select 100 or fewer.',
            'handoff_ids.*.integer' => 'A selected parcel is not valid. Reload the page and select again.',
            'handoff_ids.*.distinct' => 'A parcel was selected twice. Reload the page and select again.',
            'driver_id.required' => 'Choose a Transport Driver for this manifest.',
            'vehicle_id.required' => 'Choose a vehicle for this manifest.',
            'expected_arrival_at.required' => 'Set the expected arrival. Pick the date and time the receiving hub should expect the parcels.',
            'expected_arrival_at.date' => 'The expected arrival is not a valid date and time. Pick it from the calendar.',
            'expected_arrival_at.after' => 'The expected arrival is in the past. Pick a time after now.',
        ]);

        $manifest = DB::transaction(function () use ($request, $company, $data) {
            $ids = collect($data['handoff_ids'])->map(fn ($id) => (int) $id)->sort()->values();
            $handoffs = ManualLogisticsHandoff::query()->where('origin_company_id', $company->id)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $this->reject($handoffs->count() === $ids->count(), 'A selected parcel is no longer in your queue. Reload the page and select again.');
            $first = $handoffs->first();
            foreach ($handoffs as $handoff) {
                $this->reject($handoff->status === 'awaiting_origin' && ! $handoff->manifest_id
                    && (int) $handoff->origin_facility_id === (int) $first->origin_facility_id
                    && (int) $handoff->destination_facility_id === (int) $first->destination_facility_id,
                    'These parcels cannot share a manifest. Select parcels that are waiting at origin and share the same sending and receiving Province Hubs.');
            }
            $origin = $first->originFacility;
            $destination = $first->destinationFacility;
            $this->reject($origin?->isProvinceHub() && $origin->is_active && $destination?->isProvinceHub()
                && $destination->is_active && $destination->company?->status === 'approved'
                && (int) $origin->logistics_company_id === (int) $company->id
                && (int) $destination->logistics_company_id === (int) $first->destination_company_id
                && (int) $first->destination_company_id !== (int) $company->id,
                'This route cannot be dispatched. Both Province Hubs must be active and belong to different approved companies.');
            $driver = TransportDriverDetail::query()->where('province_hub_id', $origin->id)
                ->whereKey($data['driver_id'])->lockForUpdate()->first();
            $this->reject((bool) $driver, 'That Driver is not at the sending hub. Choose a Driver from the list.');
            $driver->load('user');
            $this->reject($driver->status === 'available' && $driver->user?->status === 'approved'
                && $driver->user?->account_status === 'active'
                && $driver->license_expires_at?->toDateString() >= today()->toDateString(),
                'That Driver is not available. Choose an approved Driver at the sending hub who is not on a trip and has a valid license.');
            $vehicle = LogisticsCompanyVehicle::query()->where('logistics_company_id', $company->id)
                ->where('province_hub_id', $origin->id)->whereKey($data['vehicle_id'])
                ->lockForUpdate()->first();
            $this->reject((bool) $vehicle, 'That vehicle is not at the sending hub. Choose a vehicle from the list.');
            $this->reject($vehicle->status === 'available', 'That vehicle is not available. Choose a vehicle at the sending hub that is not on a trip.');

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
                $this->reject($order->status === 'sorted' && $stop->sorted_at && ! $stop->departed_at
                    && ! $order->logisticsExceptions()->where('status', 'open')->exists(),
                    'A selected parcel is not ready. Sort it at the origin and resolve any open exception first.');
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

    /** A failed check goes back to the form with a message that says how to fix it. It does not open an error page. */
    private function reject(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['handoff_ids' => [$message]]);
        }
    }
}
