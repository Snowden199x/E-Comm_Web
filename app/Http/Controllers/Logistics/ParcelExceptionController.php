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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParcelExceptionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $role = (string) $request->route('portal_role');
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) > 100) {
            return redirect()->route('lgp.'.$role.'.exceptions')
                ->withErrors(['q' => 'The search is too long. Use a tracking number of 100 characters or fewer.']);
        }
        $facility = $request->attributes->get('logistics_facility');
        $company = $role === 'company' ? $request->user()->logisticsCenterDetail->company : $facility->company;
        $facilityIds = match ($role) {
            'company' => $company->facilities()->pluck('id'),
            'province' => $facility->children()->pluck('id')->push($facility->id),
            default => collect([$facility->id]),
        };
        $mine = fn () => LogisticsParcelException::query()->whereIn('facility_id', $facilityIds);
        $statusCounts = $mine()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $status = in_array($request->query('status'), ['open', 'resolved'], true) ? $request->query('status') : null;
        $exceptions = $mine()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->whereHas('order', fn ($order) =>
                $order->where('tracking_number', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->with(['order', 'facility'])->latest()->paginate(30)->withQueryString();
        $eligible = $role === 'company' ? collect() : Order::query()
            ->whereNotIn('status', ['cancelled', 'returned', 'delivered', 'completed'])
            ->whereHas('facilityStops', fn ($query) => $query->where('facility_id', $facility->id)
                ->whereNotNull('arrived_at')->whereNull('departed_at'))
            ->whereDoesntHave('logisticsExceptions', fn ($query) => $query->where('status', 'open'))
            ->latest()->limit(100)->get(['id', 'tracking_number']);
        $detours = $role === 'company' ? collect() : $company->facilities()->where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'province', 'municipality']);

        return view('logistics.shared.parcel-exceptions', compact('role', 'facility', 'exceptions', 'eligible', 'detours', 'statusCounts', 'status', 'term'));
    }

    public function store(Request $request): RedirectResponse
    {
        $facility = $request->attributes->get('logistics_facility');
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', 'in:damaged,label_unreadable,missing,wrong_route,other'],
            'details' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'order_id.required' => 'Choose the parcel with the problem.',
            'order_id.exists' => 'That parcel was not found. Reload the page and choose again.',
            'reason.required' => 'Choose a reason for the exception.',
            'reason.in' => 'Choose a reason from the list.',
            'details.required' => 'Describe what happened so the next person can act on it.',
            'details.min' => 'Add more detail. Use at least 5 characters.',
            'details.max' => 'The details are too long. Use 1,000 characters or fewer.',
        ]);
        DB::transaction(function () use ($request, $facility, $data) {
            $order = Order::query()->whereKey($data['order_id'])->lockForUpdate()->firstOrFail();
            $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->lockForUpdate()->firstOrFail();
            $this->reject($facility->is_active && $stop->arrived_at && ! $stop->departed_at
                && ! in_array($order->status, ['cancelled', 'returned', 'delivered', 'completed'], true),
                'This parcel is not at your facility. Report an exception only while it is here and your facility is active.', 'order_id');
            $this->reject(! $order->logisticsExceptions()->where('status', 'open')->exists(),
                'This parcel already has an open exception. Resolve it first, or choose another parcel.', 'order_id');
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

        return back()->with('success', 'Exception recorded. Scans for this parcel stay paused until you resolve it.');
    }

    public function resolve(Request $request, LogisticsParcelException $exception): RedirectResponse
    {
        $facility = $request->attributes->get('logistics_facility');
        abort_unless((int) $exception->facility_id === (int) $facility->id, 404);
        $data = $request->validate([
            'resolution' => ['required', 'in:resume,detour'],
            'resolution_note' => ['required', 'string', 'min:5', 'max:1000'],
            'detour_facility_id' => ['required_if:resolution,detour', 'nullable', 'integer'],
        ], [
            'resolution.required' => 'Choose how to resolve this exception.',
            'resolution.in' => 'Choose Resume route or Add a route stop.',
            'resolution_note.required' => 'Add a resolution note so the next person knows what was done.',
            'resolution_note.min' => 'Add more detail. Use at least 5 characters.',
            'resolution_note.max' => 'The note is too long. Use 1,000 characters or fewer.',
            'detour_facility_id.required_if' => 'Choose the facility to add as the next stop.',
        ]);
        DB::transaction(function () use ($request, $facility, $exception, $data) {
            $order = Order::query()->whereKey($exception->order_id)->lockForUpdate()->firstOrFail();
            $exception = LogisticsParcelException::query()->whereKey($exception->id)->lockForUpdate()->firstOrFail();
            $this->reject($exception->status === 'open', 'This exception is already resolved. Reload the page to see its latest status.', 'resolution');
            $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->lockForUpdate()->firstOrFail();
            $this->reject($facility->is_active && $stop->arrived_at && ! $stop->departed_at,
                'The parcel has already left this facility, so it cannot be resolved here. Reload the page.', 'resolution');
            if ($data['resolution'] === 'detour') {
                $this->reject($stop->sorted_at && $order->route_type !== 'manual_handoff',
                    'A route stop can only be added after the parcel is sorted, on a same-company route. Sort the parcel first, or choose Resume route.', 'resolution');
                $stops = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->orderByDesc('position')->lockForUpdate()->get();
                $next = $stops->firstWhere('position', $stop->position + 1);
                $this->reject($next && ! $next->arrived_at,
                    'There is no route leg left after this facility to add a stop to. Choose Resume route.', 'resolution');
                $this->reject(! $order->transportTrips()->whereIn('transport_trips.status', ['scheduled', 'in_transit'])
                    ->where('origin_facility_id', $facility->id)->exists(),
                    'This leg has a scheduled trip. Cancel that trip first, then add the stop.', 'resolution');
                $detour = LogisticsFacility::query()->whereKey($data['detour_facility_id'])->firstOrFail();
                $this->reject($detour->is_active && (int) $detour->logistics_company_id === (int) $facility->logistics_company_id
                    && (int) $next->facility?->logistics_company_id === (int) $facility->logistics_company_id
                    && ! $stops->contains('facility_id', $detour->id),
                    'That facility cannot be used. Choose an active facility in your company that this parcel has not visited.', 'detour_facility_id');
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

        return back()->with('success', 'Exception resolved. Normal handling can resume.');
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

    /** A failed check goes back to the form with a message that says how to fix it. It does not open an error page. */
    private function reject(bool $ok, string $message, string $field): void
    {
        if (! $ok) {
            throw ValidationException::withMessages([$field => [$message]]);
        }
    }
}
