<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\LogisticsParcelException;
use App\Models\Profiles\OrderFacilityStop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ParcelExceptionController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->route('portal_role');
        $facility = $request->attributes->get('logistics_facility');
        $company = $role === 'company' ? $request->user()->logisticsCenterDetail->company : $facility->company;
        $facilityIds = match ($role) {
            'company' => $company->facilities()->pluck('id'),
            'province' => $facility->children()->pluck('id')->push($facility->id),
            default => collect([$facility->id]),
        };
        $exceptions = LogisticsParcelException::query()->whereIn('facility_id', $facilityIds)
            ->with(['order', 'facility'])->latest()->paginate(30);
        $eligible = $role === 'company' ? collect() : Order::query()
            ->whereNotIn('status', ['cancelled', 'returned', 'delivered', 'completed'])
            ->whereHas('facilityStops', fn ($query) => $query->where('facility_id', $facility->id)
                ->whereNotNull('arrived_at')->whereNull('departed_at'))
            ->whereDoesntHave('logisticsExceptions', fn ($query) => $query->where('status', 'open'))
            ->latest()->limit(100)->get(['id', 'tracking_number']);
        $detours = $role === 'company' ? collect() : $company->facilities()->where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'province', 'municipality']);

        return view('logistics.shared.parcel-exceptions', compact('role', 'facility', 'exceptions', 'eligible', 'detours'));
    }

    public function store(Request $request): RedirectResponse
    {
        $facility = $request->attributes->get('logistics_facility');
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', 'in:damaged,label_unreadable,missing,wrong_route,other'],
            'details' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $facility, $data) {
            $order = Order::query()->whereKey($data['order_id'])->lockForUpdate()->firstOrFail();
            $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->lockForUpdate()->firstOrFail();
            abort_unless($facility->is_active && $stop->arrived_at && ! $stop->departed_at
                && ! in_array($order->status, ['cancelled', 'returned', 'delivered', 'completed'], true),
                409, 'Report an exception only while the parcel is at your active facility.');
            abort_if($order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                'This parcel already has an open exception.');
            LogisticsParcelException::create([
                'order_id' => $order->id, 'facility_id' => $facility->id,
                'reported_by' => $request->user()->id, 'reason' => $data['reason'],
                'details' => $data['details'], 'status' => 'open',
            ]);
            $note = 'Parcel exception reported at '.$facility->name.': '.str_replace('_', ' ', $data['reason']).'.';
            $order->statusEvents()->create(['user_id' => $request->user()->id,
                'from_status' => $order->status, 'to_status' => $order->status, 'note' => $note]);
            $this->notify($order, $facility, $note);
        }, 3);

        return back()->with('success', 'Parcel exception recorded. Scans are paused until it is resolved.');
    }

    public function resolve(Request $request, LogisticsParcelException $exception): RedirectResponse
    {
        $facility = $request->attributes->get('logistics_facility');
        abort_unless((int) $exception->facility_id === (int) $facility->id, 404);
        $data = $request->validate([
            'resolution' => ['required', 'in:resume,detour'],
            'resolution_note' => ['required', 'string', 'min:5', 'max:1000'],
            'detour_facility_id' => ['required_if:resolution,detour', 'nullable', 'integer'],
        ]);
        DB::transaction(function () use ($request, $facility, $exception, $data) {
            $order = Order::query()->whereKey($exception->order_id)->lockForUpdate()->firstOrFail();
            $exception = LogisticsParcelException::query()->whereKey($exception->id)->lockForUpdate()->firstOrFail();
            abort_unless($exception->status === 'open', 409, 'This exception has already been resolved.');
            $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->lockForUpdate()->firstOrFail();
            abort_unless($facility->is_active && $stop->arrived_at && ! $stop->departed_at,
                409, 'The parcel has already left this facility.');
            if ($data['resolution'] === 'detour') {
                abort_unless($stop->sorted_at && $order->route_type !== 'manual_handoff', 409,
                    'A detour can be inserted only after sorting on a same-company route.');
                $stops = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->orderByDesc('position')->lockForUpdate()->get();
                $next = $stops->firstWhere('position', $stop->position + 1);
                abort_unless($next && ! $next->arrived_at, 409,
                    'There is no unvisited route leg after this facility.');
                abort_if($order->transportTrips()->whereIn('transport_trips.status', ['scheduled', 'in_transit'])
                    ->where('origin_facility_id', $facility->id)->exists(), 409,
                    'Cancel the scheduled trip for this leg before changing its route.');
                $detour = LogisticsFacility::query()->whereKey($data['detour_facility_id'])->firstOrFail();
                abort_unless($detour->is_active && (int) $detour->logistics_company_id === (int) $facility->logistics_company_id
                    && (int) $next->facility?->logistics_company_id === (int) $facility->logistics_company_id
                    && ! $stops->contains('facility_id', $detour->id), 409,
                    'Choose an active, unvisited facility in the same Logistics company.');
                foreach ($stops as $later) {
                    if ($later->position > $stop->position) {
                        $later->update(['position' => $later->position + 1]);
                    }
                }
                OrderFacilityStop::create(['order_id' => $order->id,
                    'facility_id' => $detour->id, 'position' => $stop->position + 1]);
            }
            $exception->update(['status' => 'resolved', 'resolved_by' => $request->user()->id,
                'resolution_note' => $data['resolution_note'], 'resolved_at' => now()]);
            $note = $data['resolution'] === 'detour'
                ? 'Parcel route updated at '.$facility->name.' after resolving an exception.'
                : 'Parcel exception resolved at '.$facility->name.'; normal handling may resume.';
            $order->statusEvents()->create(['user_id' => $request->user()->id,
                'from_status' => $order->status, 'to_status' => $order->status, 'note' => $note]);
            $this->notify($order, $facility, $note);
        }, 3);

        return back()->with('success', 'Parcel exception resolved.');
    }

    private function notify(Order $order, LogisticsFacility $facility, string $note): void
    {
        foreach (array_unique(array_filter([$order->seller_id, $order->buyer_id,
            $facility->company?->logisticsCenter?->user_id])) as $userId) {
            $link = (int) $userId === (int) $order->seller_id ? route('seller.orders.show', $order)
                : ((int) $userId === (int) $order->buyer_id ? route('buyer.orders.show', $order)
                    : route('lgp.company.tracking', ['q' => $order->tracking_number]));
            Notification::create(['user_id' => $userId, 'type' => 'shipment_update',
                'title' => 'Parcel '.$order->number.' update', 'message' => $note, 'link' => $link]);
        }
    }
}
