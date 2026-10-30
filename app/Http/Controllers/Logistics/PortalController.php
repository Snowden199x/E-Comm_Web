<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\FacilityStaff;
use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsCompanyVehicle;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\ManualLogisticsHandoff;
use App\Models\Profiles\CrossCompanyManifest;
use App\Models\Profiles\RiderTask;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use App\Services\LocationCatalog;
use App\Services\PortalParcelScanService;
use App\Services\TransportTripWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function companyDashboard(Request $request): View
    {
        $company = $this->company($request);
        $hubs = $company->provinceHubs()->with(['children.staff.user', 'staff.user'])->get()
            ->mapWithKeys(fn ($hub) => [(string) $hub->id => $this->hubCard($hub)]);
        $facilityIds = $company->facilities()->pluck('id');
        $orders = \App\Models\Ecommerce\Order::query()->where(function ($query) use ($facilityIds) {
            $query->whereIn('origin_station_id', $facilityIds)->orWhereIn('destination_station_id', $facilityIds)
                ->orWhereIn('origin_province_hub_id', $facilityIds)->orWhereIn('destination_province_hub_id', $facilityIds);
        });
        $tripCount = $company->trips()->whereIn('status', ['scheduled', 'in_transit'])->count();
        $delayedTripOrders = DB::table('transport_trip_orders as tto')
            ->join('transport_trips as trips', 'trips.id', '=', 'tto.trip_id')
            ->where('trips.logistics_company_id', $company->id)
            ->whereIn('trips.status', ['scheduled', 'in_transit'])
            ->where('trips.expected_arrival_at', '<', now())->pluck('tto.order_id');
        $delayedHandoffOrders = DB::table('manual_logistics_handoffs as handoffs')
            ->join('cross_company_manifests as manifests', 'manifests.id', '=', 'handoffs.manifest_id')
            ->where(fn ($query) => $query->where('manifests.origin_company_id', $company->id)
                ->orWhere('manifests.destination_company_id', $company->id))
            ->where('manifests.status', 'in_transit')->where('manifests.expected_arrival_at', '<', now())
            ->where('handoffs.status', 'pending')->pluck('handoffs.order_id');
        $activity = DB::table('order_status_events as events')
            ->join('orders', 'orders.id', '=', 'events.order_id')
            ->where(fn ($query) => $query->whereIn('orders.origin_station_id', $facilityIds)
                ->orWhereIn('orders.destination_station_id', $facilityIds)
                ->orWhereIn('orders.origin_province_hub_id', $facilityIds)
                ->orWhereIn('orders.destination_province_hub_id', $facilityIds))
            ->whereIn('events.to_status', ['picked_up', 'sorted', 'in_transit_to_hub',
                'at_destination_hub', 'out_for_delivery', 'delivered', 'delivery_failed'])
            ->orderByDesc('events.id')->limit(8)
            ->get(['events.note', 'events.to_status', 'events.created_at', 'orders.tracking_number'])
            ->map(fn ($event) => ['text' => $event->note ?: ucfirst(str_replace('_', ' ', $event->to_status)),
                'at' => $event->created_at, 'track' => $event->tracking_number])->all();

        return view('logistics.company.dashboard', [
            'hubs' => $hubs->all(),
            'drivers' => $company->provinceHubs()->get()->sum(fn ($hub) => $hub->transportDrivers()->count()),
            'vehicles' => $company->vehicles()->count(),
            'parcelsInTransit' => (clone $orders)->whereIn('status', ['picked_up', 'in_transit_to_hub'])->count(),
            'deliveredToday' => (clone $orders)->whereIn('status', ['delivered', 'completed'])->whereDate('updated_at', today())->count(),
            'delayedParcels' => $delayedTripOrders->concat($delayedHandoffOrders)->unique()->count(),
            'failedParcels' => (clone $orders)->where('status', 'delivery_failed')->count(),
            'delayedRule' => 'The planned arrival time has passed and the parcel has not reached its destination.',
            'activity' => $activity,
            'activeTripCount' => $tripCount + CrossCompanyManifest::query()
                ->where('origin_company_id', $company->id)->where('status', 'in_transit')->count(),
        ]);
    }

    public function companyHubs(Request $request, LocationCatalog $locations): View
    {
        $company = $this->company($request);
        $hubs = $company->provinceHubs()->with(['staff.user', 'children'])->get()
            ->map(fn ($hub) => $this->hubCard($hub))->all();

        return view('logistics.company.hubs', [
            'hubs' => $hubs,
            'provinces' => collect($locations->all())->pluck('name')->values(),
            'showUrl' => route('lgp.company.hubs.show', '__hub__'),
            'refreshUrl' => route('lgp.company.hubs.manager-feed'),
            'createUrl' => route('lgp.company.hubs.store'),
            'updateUrl' => route('lgp.company.hubs.update', '__hub__'),
            'deleteUrl' => route('lgp.company.hubs.destroy', '__hub__'),
            'statusUrl' => route('lgp.company.hubs.status', '__hub__'),
        ]);
    }

    public function companyHubManagersFeed(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $hubs = $company->provinceHubs()->with('staff.user')->get();
        $managerIds = $hubs->map(fn ($hub) => $hub->staff?->user?->id)->filter()->values();
        $pendingEmails = $managerIds->isEmpty() ? collect() : DB::table('logistics_email_changes')
            ->whereIn('user_id', $managerIds)->where('expires_at', '>', now())->pluck('new_email', 'user_id');
        $managers = $hubs->map(function ($hub) use ($pendingEmails) {
            $user = $hub->staff?->user;

            return [
                'id' => $hub->id,
                'manager' => $user?->name ?? 'Manager not assigned',
                'email' => $user?->email ?? '',
                'pending_email' => $user ? ($pendingEmails[$user->id] ?? null) : null,
                'phone' => $user?->phone_number ?? '',
            ];
        })->values();

        return response()->json(['managers' => $managers])
            ->header('Cache-Control', 'private, no-store');
    }

    public function createProvinceHub(Request $request, LocationCatalog $locations): JsonResponse
    {
        $company = $this->company($request);
        $data = $request->validate([
            'province' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:200'],
            'manager' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'lowercase', 'max:120', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);
        $provinceCode = $locations->provinceCodeForName($data['province']);
        abort_unless($provinceCode, 422, 'Choose a valid province.');
        $province = $locations->all()[$provinceCode]['name'];
        $manager = $this->managerName($data['manager']);
        $hub = DB::transaction(function () use ($company, $data, $provinceCode, $province, $manager) {
            LogisticsCompany::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $exists = $company->provinceHubs()->where('province_code', $provinceCode)->where('is_active', true)->exists();
            if ($exists) {
                throw ValidationException::withMessages(['province' => 'This company already has a Province Hub in that province.']);
            }
            $user = User::create([
                'name' => $manager['name'], 'first_name' => $manager['first_name'], 'last_name' => $manager['last_name'],
                'email' => $data['email'], 'phone_number' => $data['phone'], 'role' => 'province_hub',
                'status' => 'approved', 'account_status' => 'active', 'email_verified_at' => now(),
                'password' => Hash::make(str()->random(64)), 'must_change_password' => true, 'temp_password_plain' => null,
            ]);
            $facility = $company->facilities()->create([
                'logistics_center_id' => $company->logistics_center_id,
                'kind' => 'province_hub', 'name' => $data['name'], 'province_code' => $provinceCode,
                'province' => $province, 'municipality' => $province,
                'address' => $data['address'], 'is_active' => true,
            ]);
            FacilityStaff::create(['facility_id' => $facility->id, 'user_id' => $user->id, 'staff_title' => 'province_manager']);
            $this->sendManagerPasswordSetupLink($user);

            return $facility->load('staff.user');
        }, 3);

        return response()->json(['hub' => $this->hubCard($hub), 'message' => 'Province Hub created. A password setup link was emailed to its manager. The link opens the Create New Password page.'], 201);
    }

    public function updateProvinceHub(Request $request, LogisticsFacility $hub): JsonResponse
    {
        $company = $this->company($request);
        abort_unless((int) $hub->logistics_company_id === (int) $company->id && $hub->isProvinceHub(), 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($data['is_active']) {
            abort_if($company->provinceHubs()->where('province_code', $hub->province_code)->where('is_active', true)->where('id', '!=', $hub->id)->exists(), 409,
                'This company already has an active Province Hub in that province.');
        }
        if (! $data['is_active'] && $hub->is_active) {
            $this->ensureNoOpenWork($hub);
        }
        $hub->update(['is_active' => $data['is_active']]);
        if ($hub->staff?->user) {
            $hub->staff->user->update(['account_status' => $data['is_active'] ? 'active' : 'deactivated']);
        }

        return response()->json(['hub' => $this->hubCard($hub->fresh('staff.user', 'children'))]);
    }

    public function updateProvinceHubDetails(Request $request, LogisticsFacility $hub): JsonResponse
    {
        $company = $this->company($request);
        abort_unless((int) $hub->logistics_company_id === (int) $company->id && $hub->isProvinceHub(), 404);
        $managerUserId = $hub->staff()->value('user_id');
        abort_if(! $managerUserId, 409, 'This Province Hub has no linked manager account to update.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:200'],
            'manager' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($managerUserId)],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);
        $manager = $this->managerName($data['manager']);
        $emailToken = null;

        $hub = DB::transaction(function () use ($company, $hub, $data, $manager, $managerUserId, &$emailToken) {
            $lockedHub = LogisticsFacility::query()->whereKey($hub->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedHub->logistics_company_id === (int) $company->id && $lockedHub->isProvinceHub(), 404);
            $staff = $lockedHub->staff()->with('user')->first();
            abort_if(! $staff?->user, 409, 'This Province Hub has no linked manager account to update.');
            abort_unless((int) $staff->user_id === (int) $managerUserId, 409);

            $lockedHub->update(['name' => $data['name'], 'address' => $data['address']]);
            $staff->user->update([
                'name' => $manager['name'], 'first_name' => $manager['first_name'],
                'last_name' => $manager['last_name'], 'phone_number' => $data['phone'],
            ]);

            if ($staff->user->email !== $data['email']) {
                DB::table('logistics_email_changes')->where('expires_at', '<=', now())->delete();
                if (DB::table('logistics_email_changes')->where('new_email', $data['email'])
                    ->where('user_id', '!=', $staff->user_id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'This email has another pending change request.']);
                }
                $emailToken = bin2hex(random_bytes(32));
                DB::table('logistics_email_changes')->updateOrInsert(['user_id' => $staff->user_id], [
                    'new_email' => $data['email'], 'token_hash' => hash('sha256', $emailToken),
                    'expires_at' => now()->addMinutes(30), 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                DB::table('logistics_email_changes')->where('user_id', $staff->user_id)->delete();
            }

            return $lockedHub->fresh(['staff.user', 'children']);
        }, 3);

        $message = 'Province Hub updated.';
        if ($emailToken) {
            $relativeUrl = URL::temporarySignedRoute('lgp.account.manager-email.confirm', now()->addMinutes(30),
                ['token' => $emailToken], absolute: false);
            $link = rtrim(config('app.url'), '/').$relativeUrl;
            try {
                Mail::raw('Confirm the new email address for your Vendo Province Hub manager account within 30 minutes: '.$link
                    .' If you did not expect this change, ignore this email.',
                    fn ($mail) => $mail->to($data['email'])->subject('Confirm Province Hub manager email'));
                $message .= ' A confirmation link was sent to the new manager email. The new address will appear as pending until confirmed; opening the link will continue to password setup.';
            } catch (\Throwable $exception) {
                report($exception);
                DB::table('logistics_email_changes')->where('user_id', $hub->staff->user_id)
                    ->where('token_hash', hash('sha256', $emailToken))->delete();
                $message .= ' The email confirmation could not be sent, so the manager email remains unchanged.';
            }
        }

        return response()->json(['hub' => $this->hubCard($hub), 'message' => $message]);
    }

    public function deleteProvinceHub(Request $request, LogisticsFacility $hub): JsonResponse
    {
        $company = $this->company($request);
        abort_unless((int) $hub->logistics_company_id === (int) $company->id && $hub->isProvinceHub(), 404);

        DB::transaction(function () use ($company, $hub) {
            $lockedHub = LogisticsFacility::query()->whereKey($hub->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedHub->logistics_company_id === (int) $company->id && $lockedHub->isProvinceHub(), 404);
            abort_if($lockedHub->children()->exists(), 409, 'Delete or move this Province Hub’s Station Hubs before deleting the Province Hub.');
            abort_if($this->provinceHubHasHistory($lockedHub), 409, 'This Province Hub has parcel, trip, driver, vehicle, scan, settlement, or message history and cannot be deleted. Deactivate it instead.');

            $staff = $lockedHub->staff()->with('user')->first();
            $manager = $staff?->user;
            if ($manager && $manager->role === 'province_hub') {
                $manager->update(['account_status' => 'deactivated']);
            }

            $lockedHub->delete();
        }, 3);

        return response()->json(['id' => $hub->id, 'message' => 'Province Hub deleted. Its manager account was deactivated.']);
    }

    public function companyHubShow(Request $request, LogisticsFacility $hub): View
    {
        $company = $this->company($request);
        abort_unless((int) $hub->logistics_company_id === (int) $company->id && $hub->isProvinceHub(), 404);
        $hub->load(['staff.user', 'children.staff.user']);
        $card = $this->hubCard($hub);

        return view('logistics.company.hub-show', ['slug' => $hub->id, 'hub' => $card]);
    }

    public function companyNetwork(Request $request): View
    {
        $company = $this->company($request);
        $hubs = $company->provinceHubs()->with(['staff.user', 'children.staff.user'])->get()
            ->mapWithKeys(fn ($hub) => [(string) $hub->id => $this->hubCard($hub)])->all();

        return view('logistics.company.network', compact('hubs'));
    }

    public function companyTracking(Request $request): View
    {
        $company = $this->company($request);
        $facilityIds = $company->facilities()->pluck('id');
        $query = \App\Models\Ecommerce\Order::query()->with(['buyer', 'seller', 'items'])
            ->where(function ($q) use ($facilityIds) {
                $q->whereIn('origin_station_id', $facilityIds)->orWhereIn('destination_station_id', $facilityIds)
                    ->orWhereIn('origin_province_hub_id', $facilityIds)->orWhereIn('destination_province_hub_id', $facilityIds)
                    ->orWhereHas('facilityStops', fn ($stops) => $stops->whereIn('facility_id', $facilityIds));
            });

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $term = trim($request->string('q')->toString());
                $q->where('tracking_number', 'like', "%{$term}%")->orWhere('id', $term);
            });
        }

        return $this->trackingView($query->latest()->get(), 'company');
    }

    public function companyHandoffs(Request $request): View
    {
        $company = $this->company($request);
        $handoffs = ManualLogisticsHandoff::query()->where(fn ($query) =>
            $query->where('origin_company_id', $company->id)->orWhere('destination_company_id', $company->id))
            ->with(['order', 'originCompany', 'destinationCompany', 'originFacility', 'destinationFacility', 'manifest'])
            ->latest()->paginate(30);
        $hubIds = $company->provinceHubs()->pluck('id');
        $drivers = TransportDriverDetail::query()->whereIn('province_hub_id', $hubIds)
            ->where('status', 'available')->whereDate('license_expires_at', '>=', today())
            ->whereHas('user', fn ($query) => $query->where('status', 'approved')->where('account_status', 'active'))
            ->with(['user', 'provinceHub'])->get();
        $vehicles = $company->vehicles()->where('status', 'available')->with('provinceHub')->get();
        $manifests = CrossCompanyManifest::query()
            ->where(fn ($query) => $query->where('origin_company_id', $company->id)
                ->orWhere('destination_company_id', $company->id))
            ->with(['originFacility', 'destinationFacility', 'driver.user', 'vehicle'])
            ->withCount(['handoffs', 'handoffs as accepted_count' => fn ($query) => $query->where('status', 'accepted')])
            ->latest()->limit(20)->get();

        return view('logistics.company.handoffs', compact('company', 'handoffs', 'drivers', 'vehicles', 'manifests'));
    }

    public function facilityTracking(Request $request): View
    {
        $facility = $this->facility($request);
        $companyIds = collect([$facility->id]);
        if ($facility->isProvinceHub()) {
            $companyIds = $companyIds->merge($facility->children()->where('kind', 'station_hub')->pluck('id'))->unique()->values();
        }
        $query = \App\Models\Ecommerce\Order::query()->with(['statusEvents', 'facilityStops.facility'])
            ->where(function ($q) use ($companyIds) {
                $q->whereIn('origin_station_id', $companyIds)->orWhereIn('destination_station_id', $companyIds)
                    ->orWhereIn('origin_province_hub_id', $companyIds)->orWhereIn('destination_province_hub_id', $companyIds)
                    ->orWhereHas('facilityStops', fn ($stops) => $stops->whereIn('facility_id', $companyIds));
            });
        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where(fn ($q) => $q->where('tracking_number', 'like', "%{$term}%")->orWhere('id', $term));
        }

        return $this->trackingView($query->latest()->get(), $facility->isProvinceHub() ? 'province' : 'station');
    }

    private function trackingView($orders, string $role): View
    {
        $parcels = $orders->map(function ($order) {
            $events = $order->statusEvents->map(fn ($event) => [
                'status' => \Illuminate\Support\Str::headline($event->to_status),
                'where' => $event->note ?: 'Vendo logistics network', 'at' => $event->created_at?->toIso8601String(),
                'trip' => null, 'type' => null, 'note' => null,
            ])->values();
            if ($events->isEmpty() || $events->last()['status'] !== \Illuminate\Support\Str::headline($order->status)) {
                $events->push(['status' => \Illuminate\Support\Str::headline($order->status), 'where' => 'Current order status', 'at' => $order->updated_at?->toIso8601String(), 'trip' => null, 'type' => null, 'note' => null]);
            }
            $stops = $order->facilityStops->map(fn ($stop) => $stop->facility?->name)->filter()->values()->all();
            $flag = $order->status === 'delivery_failed' ? 'failed' : null;
            return [
                'code' => $order->tracking_number ?? 'VN-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'order' => $order->number, 'route' => $order->route_type ?: 'local', 'from' => $stops[0] ?? $order->shipping_city ?? 'Origin',
                'to' => $stops[count($stops) - 1] ?? $order->shipping_city ?? 'Destination', 'stops' => $stops ?: ['Awaiting facility route'],
                'events' => $events->all(), 'flag' => $flag, 'reason' => $flag ? 'Delivery failed. Review the order and assign its next action.' : null,
            ];
        })->values()->all();

        return view('logistics.shared.tracking', [
            'parcels' => $parcels,
            'routeLabels' => ['local' => 'Local delivery', 'intra_province' => 'Within province', 'inter_province' => 'Inter-province', 'manual_handoff' => 'Manual company handoff'],
            'role' => $role,
            'intro' => 'Search parcels currently routed through your authorized Logistics scope.',
            'delayedRule' => 'Delay flags are not shown because planned arrival times are not configured.',
            'scopeNote' => 'Results are limited to facilities in your assignment.',
        ]);
    }

    public function provinceDashboard(Request $request): View
    {
        $hub = $this->facility($request);
        $stations = $hub->children()->where('kind', 'station_hub')->with(['staff.user', 'riders.user'])->get()
            ->map(fn ($station) => $this->stationCard($station));
        $orderScope = $this->ordersAtFacilityAndChildren($hub, $stations->pluck('id'));
        $activeTrips = $hub->company?->trips()->with(['origin', 'destination', 'driver.user', 'vehicle'])
            ->where(fn ($q) => $q->where('origin_facility_id', $hub->id)->orWhere('destination_facility_id', $hub->id))
            ->whereIn('status', ['scheduled', 'in_transit'])->latest()->get() ?? collect();
        $tripRows = $activeTrips->map(fn ($trip) => [
            'id' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT), 'type' => $trip->trip_type,
            'origin' => $trip->origin?->name ?? '—', 'destination' => $trip->destination?->name ?? '—',
            'driver' => $trip->driver?->user?->name ?? 'Unassigned', 'vehicle' => $trip->vehicle?->vehicle_code ?? 'Unassigned',
            'parcels' => $trip->orders()->count(), 'status' => $trip->status,
            'scheduled' => $trip->scheduled_departure_at, 'departed' => $trip->departed_at,
        ])->values();
        $transferTrips = $hub->company?->trips()->with(['origin', 'destination'])
            ->whereIn('status', ['scheduled', 'in_transit'])->where(fn ($q) => $q->where('origin_facility_id', $hub->id)->orWhere('destination_facility_id', $hub->id))
            ->get() ?? collect();
        $incoming = $transferTrips->where('destination_facility_id', $hub->id)->map(fn ($trip) => ['id' => $trip->id, 'from' => $trip->origin?->name ?? '—', 'parcels' => $trip->orders()->count(), 'scheduled' => $trip->scheduled_departure_at])->values()->all();
        $outgoing = $transferTrips->where('origin_facility_id', $hub->id)->map(fn ($trip) => ['id' => $trip->id, 'to' => $trip->destination?->name ?? '—', 'parcels' => $trip->orders()->count(), 'scheduled' => $trip->scheduled_departure_at])->values()->all();
        $vehicles = $hub->company?->vehicles()->where('province_hub_id', $hub->id)->get()->map(fn ($vehicle) => ['status' => $vehicle->status])->values() ?? collect();

        return view('logistics.province.dashboard', [
            'hub' => $this->hubCard($hub->load('staff.user')),
            'drivers' => $hub->transportDrivers()->with('user')->get()->map(fn ($driver) => ['status' => $driver->status, 'expiry' => $driver->license_expires_at?->toDateString() ?? '2999-12-31'])->values(),
            'vehicles' => $vehicles,
            'activeTrips' => $tripRows,
            'incoming' => $incoming, 'outgoing' => $outgoing,
            'awaitingSorting' => (clone $orderScope)->whereIn('status', ['at_sorting_center', 'sorted'])->count(),
            'waitingForTrip' => (clone $orderScope)->where('status', 'sorted')->whereDoesntHave('transportTrips', fn ($q) => $q->whereIn('transport_trips.status', ['scheduled', 'in_transit']))->count(),
            'delayed' => $activeTrips->filter(fn ($trip) => $trip->expected_arrival_at?->isPast())
                ->map(fn ($trip) => ['id' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT),
                    'text' => 'Expected at '.$trip->destination?->name.' by '.$trip->expected_arrival_at->format('M j, g:i A').'.'])
                ->values()->all(),
            'delayedRule' => 'A trip is delayed when its expected arrival has passed before completion.',
        ]);
    }

    public function provinceStations(Request $request, LocationCatalog $locations): View
    {
        $hub = $this->facility($request);
        $stations = $hub->children()->where('kind', 'station_hub')->with(['staff.user'])->get()
            ->map(fn ($station) => $this->stationCard($station))->all();
        $cities = $hub->province_code ? ($locations->all()[$hub->province_code]['cities'] ?? []) : [];

        return view('logistics.province.stations', [
            'hub' => ['province' => $hub->province, 'name' => $hub->name, 'id' => $hub->id],
            'stations' => $stations,
            'municipalities' => array_values($cities),
            'showUrl' => route('lgp.province.stations.show', '__station__'),
            'createUrl' => route('lgp.province.stations.store'),
            'statusUrl' => route('lgp.province.stations.status', '__station__'),
        ]);
    }

    public function createStation(Request $request, LocationCatalog $locations): JsonResponse
    {
        $hub = $this->facility($request);
        $data = $request->validate([
            'municipality' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:200'],
            'manager' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'lowercase', 'max:120', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);
        $codes = $locations->codesForNames($hub->province, $data['municipality']);
        abort_unless($codes && $codes[0] === $hub->province_code, 422, 'Choose a municipality inside this Province Hub.');
        $manager = $this->managerName($data['manager']);
        $station = DB::transaction(function () use ($hub, $data, $codes, $manager) {
            LogisticsFacility::query()->whereKey($hub->id)->lockForUpdate()->firstOrFail();
            if ($hub->children()->where('kind', 'station_hub')->where('municipality_code', $codes[1])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['municipality' => 'This company already has a Station Hub in that municipality.']);
            }
            $user = User::create([
                'name' => $manager['name'], 'first_name' => $manager['first_name'], 'last_name' => $manager['last_name'],
                'email' => $data['email'], 'phone_number' => $data['phone'], 'role' => 'station_hub',
                'status' => 'approved', 'account_status' => 'active', 'email_verified_at' => now(),
                'password' => Hash::make(str()->random(64)), 'must_change_password' => true, 'temp_password_plain' => null,
            ]);
            $facility = $hub->children()->create([
                'logistics_center_id' => $hub->logistics_center_id,
                'logistics_company_id' => $hub->logistics_company_id,
                'kind' => 'station_hub', 'name' => $data['name'],
                'province_code' => $hub->province_code, 'province' => $hub->province,
                'municipality_code' => $codes[1], 'municipality' => $data['municipality'],
                'station_municipality_key' => $codes[1], 'address' => $data['address'], 'is_active' => true,
            ]);
            FacilityStaff::create(['facility_id' => $facility->id, 'user_id' => $user->id, 'staff_title' => 'station_manager']);
            $this->sendManagerPasswordSetupLink($user);

            return $facility->load('staff.user');
        }, 3);

        return response()->json(['station' => $this->stationCard($station), 'message' => 'Station Hub created. A password setup link was emailed to its manager. The link opens the Create New Password page.'], 201);
    }

    public function updateStation(Request $request, LogisticsFacility $station): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $station->parent_id === (int) $hub->id && $station->kind === 'station_hub', 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($data['is_active']) {
            abort_if($hub->children()->where('kind', 'station_hub')->where('municipality_code', $station->municipality_code)
                ->where('is_active', true)->where('id', '!=', $station->id)->exists(), 409,
                'This Province Hub already has an active Station Hub in that municipality.');
        }
        if (! $data['is_active'] && $station->is_active) {
            $this->ensureNoOpenWork($station);
        }
        $station->update(['is_active' => $data['is_active']]);
        if ($station->staff?->user) {
            $station->staff->user->update(['account_status' => $data['is_active'] ? 'active' : 'deactivated']);
        }

        return response()->json(['station' => $this->stationCard($station->fresh('staff.user'))]);
    }

    public function stationShow(Request $request, LogisticsFacility $station): View
    {
        $hub = $this->facility($request);
        abort_unless((int) $station->parent_id === (int) $hub->id && $station->kind === 'station_hub', 404);
        $station->load('staff.user');

        $stationData = $this->stationCard($station);
        $stationData['since'] = $station->created_at?->format('M j, Y') ?? '—';
        $stationData['riders_list'] = $station->riders()->with(['user', 'serviceAreas', 'tasks'])->get()->map(fn ($rider) => [
            'name' => $rider->user?->name ?? 'Rider', 'vehicle' => $rider->vehicle_type,
            'barangays' => $rider->serviceAreas->pluck('barangay')->join(', ') ?: 'Not assigned',
            'tasks' => $rider->tasks()->whereIn('status', ['assigned', 'in_progress'])->count(),
            'status' => $rider->user?->account_status === 'active' ? 'Available' : 'Off duty',
        ])->all();
        $stationData['stage'] = [
            'Awaiting sorting' => $this->ordersAtFacilityAndChildren($station, collect())->where('status', 'at_sorting_center')->count(),
            'Ready for delivery' => $this->ordersAtFacilityAndChildren($station, collect())->whereIn('status', ['sorted', 'at_destination_hub'])->count(),
            'Waiting for transfer' => $this->waitingForTransferCount($station),
            'Out for delivery' => $this->ordersAtFacilityAndChildren($station, collect())->where('status', 'out_for_delivery')->count(),
        ];
        $stationData['perf'] = ['on_time' => 0, 'failed' => 0, 'hours' => 0];
        $stationData['trips'] = $station->company?->trips()->with(['origin', 'destination', 'driver.user', 'vehicle'])
            ->where(fn ($q) => $q->where('origin_facility_id', $station->id)->orWhere('destination_facility_id', $station->id))
            ->latest()->take(10)->get()->map(fn ($trip) => [
                'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT), $trip->trip_type,
                ($trip->origin?->name ?? '—').' to '.($trip->destination?->name ?? '—'),
                $trip->driver?->user?->name ?? 'Unassigned', $trip->vehicle?->vehicle_code ?? 'Unassigned',
                $trip->orders()->count(), ucfirst(str_replace('_', ' ', $trip->status)),
            ])->all() ?? [];

        return view('logistics.province.station-show', [
            'hub' => ['province' => $hub->province, 'name' => $hub->name],
            'station' => $stationData,
            'riders' => $station->riders()->with(['user', 'serviceAreas'])->get(),
            'stage' => $this->stageCounts($station),
            'trips' => $station->company?->trips()->with(['origin', 'destination', 'driver.user', 'vehicle'])
                ->where(fn ($q) => $q->where('origin_facility_id', $station->id)->orWhere('destination_facility_id', $station->id))
                ->latest()->take(10)->get() ?? collect(),
        ]);
    }

    public function stationDashboard(Request $request): View
    {
        $station = $this->facility($request);
        // Close a delivery window that passed before counting today's queue, so
        // the dashboard's Delivery Failed figure matches the delivery list.
        app(\App\Services\DeliveryCutoff::class)->sweep();
        $stage = $this->stageCounts($station);
        $riders = $station->riders()->with('user')->get();
        $incomingTrip = $station->company?->trips()->with('origin')->where('destination_facility_id', $station->id)
            ->whereIn('status', ['scheduled', 'in_transit'])->oldest('scheduled_departure_at')->first();
        $incoming = $incomingTrip ? [
            'id' => 'TRP-'.str_pad((string) $incomingTrip->id, 5, '0', STR_PAD_LEFT),
            'from' => $incomingTrip->origin?->name ?? 'another facility',
            'parcels' => $incomingTrip->orders()->count(), 'scheduled' => $incomingTrip->scheduled_departure_at,
        ] : null;

        return view('logistics.station.dashboard', [
            'station' => $this->stationCard($station->load('staff.user')),
            'stage' => [
                'Awaiting sorting' => $stage['awaiting_sorting'],
                'Ready for delivery' => $stage['ready_for_delivery'],
                'Waiting for transfer' => $this->waitingForTransferCount($station),
                'Out for delivery' => $stage['out_for_delivery'],
            ],
            'riders' => $riders->map(fn ($rider) => [
                'name' => $rider->user?->name,
                'vehicle' => $rider->vehicle_type,
                'pickup' => $rider->tasks()->where('type', 'pickup')->whereIn('status', ['assigned', 'in_progress'])->count(),
                'delivery' => $rider->tasks()->where('type', 'delivery')->whereIn('status', ['assigned', 'in_progress'])->count(),
                'status' => $rider->user?->account_status === 'active' ? 'Available' : 'Off duty',
                'barangays' => $rider->serviceAreas()->pluck('barangay')->join(', ') ?: 'Not assigned',
            ])->all(),
            'pendingPickups' => $stage['pickup_requests'] ?? 0,
            'receivedToday' => $stage['received_today'] ?? 0,
            'deliveredToday' => $stage['delivered_today'] ?? 0,
            'failed' => $this->ordersAtFacilityAndChildren($station, collect())->where('status', 'delivery_failed')->latest()->take(10)->get()->map(fn ($order) => [
                'code' => $order->tracking_number ?? $order->number, 'area' => $order->shipping_city ?? 'Delivery area not recorded',
            ])->all(),
            'incoming' => $incoming,
        ]);
    }

    public function stationRiders(Request $request): View
    {
        $station = $this->facility($request);
        $riders = $station->riders()->with(['user', 'serviceAreas'])->get()->map(fn ($rider) => [
            'id' => $rider->id,
            'name' => $rider->user?->name ?? 'Rider', 'email' => $rider->user?->email ?? '',
            'vehicle' => $rider->vehicle_type ?: 'Not specified',
            'area' => $rider->serviceAreas->pluck('barangay')->join(', ') ?: $rider->municipality,
            'parcels' => $rider->tasks()->whereIn('status', ['assigned', 'in_progress'])->count(),
            'status' => match ($rider->user?->status) {
                'pending' => 'applications', 'disapproved' => 'rejected',
                default => $rider->user?->account_status === 'deactivated' ? 'inactive' : 'active',
            },
        ])->values()->all();

        return view('logistics.hub.riders', [
            'role' => 'station', 'liveRiders' => $riders,
            'reviewUrl' => route('lgp.station.riders.review', '__rider__'),
            'statusUrl' => route('lgp.station.riders.status', '__rider__'),
        ]);
    }

    public function provinceVehicles(Request $request): View
    {
        $hub = $this->facility($request);
        $vehicles = $hub->company->vehicles()->where('province_hub_id', $hub->id)->with(['trips.driver.user', 'trips.origin', 'trips.destination'])
            ->latest()->get()->map(fn ($vehicle) => $this->vehicleCard($vehicle))->values()->all();

        return view('logistics.province.vehicles', [
            'hub' => ['id' => $hub->id, 'name' => $hub->name], 'vehicles' => $vehicles,
            'types' => ['L300 Van', 'Closed Van', '6-Wheeler Truck', '10-Wheeler'],
            'today' => today()->toDateString(),
            'storeUrl' => route('lgp.province.vehicles.store'),
            'updateUrl' => route('lgp.province.vehicles.update', '__vehicle__'),
            'statusUrl' => route('lgp.province.vehicles.status', '__vehicle__'),
        ]);
    }

    public function provinceDrivers(Request $request): View
    {
        $hub = $this->facility($request);
        $drivers = $hub->transportDrivers()->with(['user', 'trips.origin', 'trips.destination'])->latest()->get()
            ->map(fn ($driver) => $this->driverCard($driver))->values()->all();

        return view('logistics.province.drivers', [
            'hub' => ['id' => $hub->id, 'name' => $hub->name], 'drivers' => $drivers,
            'today' => today()->toDateString(),
            'reviewUrl' => route('lgp.province.drivers.review', '__driver__'),
            'statusUrl' => route('lgp.province.drivers.status', '__driver__'),
        ]);
    }

    public function reviewDriver(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $driver->province_hub_id === (int) $hub->id, 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:10', 'max:300']]);

        $driver = DB::transaction(function () use ($driver, $data) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driver->status === 'pending' && $user->status === 'pending', 409, 'This application has already been reviewed.');
            if ($data['decision'] === 'approve') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver license has expired.');
                $driver->update(['status' => 'available']);
                $user->update(['status' => 'approved', 'account_status' => 'active']);
            } else {
                $driver->update(['status' => 'inactive']);
                $user->update(['status' => 'disapproved', 'rejection_reason' => $data['reason'], 'account_status' => 'deactivated']);
            }
            \App\Models\Communication\Notification::create(['user_id' => $user->id,
                'type' => 'driver_application_decision',
                'title' => $data['decision'] === 'approve' ? 'Driver application approved' : 'Driver application declined',
                'message' => $data['decision'] === 'approve' ? 'Your Province Hub approved your application.' : $data['reason']]);

            return $driver->load(['user', 'trips.origin', 'trips.destination']);
        }, 3);

        return response()->json(['driver' => $this->driverCard($driver), 'message' => 'Driver application reviewed.']);
    }

    public function updateDriverStatus(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $driver->province_hub_id === (int) $hub->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'inactive'])]]);

        $driver = DB::transaction(function () use ($driver, $data) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($user->status === 'approved' && $driver->status !== 'on_trip', 409,
                'A driver must be approved and off trip before changing availability.');
            if ($data['status'] === 'available') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver license has expired.');
            }
            $driver->update(['status' => $data['status']]);
            $user->update(['account_status' => $data['status'] === 'available' ? 'active' : 'deactivated']);

            return $driver->load(['user', 'trips.origin', 'trips.destination']);
        }, 3);

        return response()->json(['driver' => $this->driverCard($driver), 'message' => 'Driver availability updated.']);
    }

    public function driverDocument(Request $request, TransportDriverDetail $driver, string $document)
    {
        $hub = $this->facility($request);
        abort_unless((int) $driver->province_hub_id === (int) $hub->id, 404);
        $path = $document === 'license' ? $driver->drivers_license_path : $driver->valid_id_path;
        abort_unless(str_starts_with((string) $path, 'private/transport-drivers/')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function provinceParcels(Request $request): View
    {
        $hub = $this->facility($request);
        $stops = OrderFacilityStop::query()->where('facility_id', $hub->id)
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']))
            ->with(['order.facilityStops.facility', 'facility'])->latest()->paginate(40);
        $rows = $stops->through(function ($stop) {
            $next = $stop->order->facilityStops->firstWhere('position', $stop->position + 1);
            $stage = ! $stop->arrived_at ? 'Incoming' : (! $stop->sorted_at ? 'Sorting'
                : ($stop->departed_at ? 'Departed' : 'Outgoing'));
            return ['tracking' => $stop->order->tracking_number, 'status' => $stop->order->status_label,
                'stage' => $stage, 'next' => $next?->facility?->name ?? 'Final delivery',
                'arrived' => $stop->arrived_at?->format('M j, g:i A'),
                'departed' => $stop->departed_at?->format('M j, g:i A')];
        });

        return view('logistics.province.live-parcels', compact('hub', 'rows'));
    }

    public function provinceTrips(Request $request): View
    {
        $hub = $this->facility($request);
        $scopeIds = $hub->children()->where('kind', 'station_hub')->pluck('id')->push($hub->id);
        $trips = $hub->company->trips()->with(['origin', 'destination', 'driver.user', 'vehicle', 'orders'])
            ->where(fn ($q) => $q->whereIn('origin_facility_id', $scopeIds)->orWhereIn('destination_facility_id', $scopeIds))
            ->latest()->get()->map(fn ($trip) => $this->tripCard($trip) + [
                'can_cancel' => $scopeIds->contains($trip->origin_facility_id),
                'can_depart' => $scopeIds->contains($trip->origin_facility_id),
                'can_arrive' => $scopeIds->contains($trip->destination_facility_id),
            ])->values()->all();

        return view('logistics.province.trips', [
            'hub' => ['id' => $hub->id, 'name' => $hub->name], 'trips' => $trips,
            'now' => now()->toIso8601String(), 'createUrl' => route('lgp.province.trips.create'),
            'cancelUrl' => route('lgp.province.trips.cancel', '__trip__'),
            'departUrl' => route('lgp.province.trips.depart', '__trip__'),
            'arriveUrl' => route('lgp.province.trips.arrive', '__trip__'),
        ]);
    }

    public function createTripPage(Request $request): View
    {
        $hub = $this->facility($request);
        $stations = $hub->children()->where('kind', 'station_hub')->where('is_active', true)->get()
            ->map(fn ($facility) => ['id' => $facility->id, 'slug' => (string) $facility->id, 'name' => $facility->name])->values();
        $otherHubs = $hub->company->provinceHubs()->where('id', '!=', $hub->id)->where('is_active', true)->get()
            ->map(fn ($facility) => ['id' => $facility->id, 'slug' => (string) $facility->id, 'name' => $facility->name])->values();
        $drivers = $hub->transportDrivers()->where('status', 'available')->whereDate('license_expires_at', '>=', today())
            ->whereHas('user', fn ($q) => $q->where('status', 'approved')->where('account_status', 'active'))
            ->with('user')->get()->map(fn ($driver) => [
                'id' => $driver->id, 'name' => $driver->user?->name ?? 'Transport Driver',
                'category' => $driver->license_category, 'expiry' => $driver->license_expires_at?->toDateString(),
            ])->values();
        $vehicles = $hub->company->vehicles()->where('province_hub_id', $hub->id)->where('status', 'available')->get()
            ->map(fn ($vehicle) => ['id' => $vehicle->id, 'code' => $vehicle->vehicle_code, 'type' => $vehicle->vehicle_type, 'capacity' => round(($vehicle->capacity_kg ?? 0) / 1000, 1)])->values();
        $parcels = [];
        $origins = collect([['id' => $hub->id, 'slug' => (string) $hub->id]])->concat($stations);
        foreach ($origins as $origin) {
            $destinations = $origin['id'] === $hub->id ? $stations : collect([['id' => $hub->id, 'slug' => (string) $hub->id]]);
            foreach ($destinations as $destination) {
                $key = ($origin['id'] === $hub->id ? 'to:' : 'from:').$destination['slug'];
                $parcels[$key] = $this->eligibleTripOrders($hub, (int) $origin['id'], (int) $destination['id'])->get()
                    ->map(fn ($order) => ['order' => $order->id, 'tracking' => $order->tracking_number, 'destination' => $order->shipping_city ?? '—', 'weight' => 0])->values();
            }
        }
        foreach ($otherHubs as $destination) {
            $key = 'to:'.$destination['slug'];
            $parcels[$key] = $this->eligibleTripOrders($hub, (int) $hub->id, (int) $destination['id'])->get()
                ->map(fn ($order) => ['order' => $order->id, 'tracking' => $order->tracking_number, 'destination' => $order->shipping_city ?? '—', 'weight' => 0])->values();
        }

        return view('logistics.province.trip-create', [
            'hub' => ['id' => $hub->id, 'name' => $hub->name], 'stations' => $stations->all(), 'otherHubs' => $otherHubs->all(),
            'drivers' => $drivers->all(), 'vehicles' => $vehicles->all(), 'parcels' => $parcels,
            'now' => now()->format('Y-m-d\TH:i'), 'defaultDeparture' => now()->addHour()->format('Y-m-d\TH:i'),
            'defaultArrival' => now()->addHours(6)->format('Y-m-d\TH:i'),
            'listUrl' => route('lgp.province.trips'), 'storeUrl' => route('lgp.province.trips.store'),
        ]);
    }

    public function storeTrip(Request $request): JsonResponse
    {
        $hub = $this->facility($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(['station_transfer', 'inter_province'])],
            'origin_id' => ['required', 'integer'], 'destination_id' => ['required', 'integer', 'different:origin_id'],
            'driver_id' => ['required', 'integer'], 'vehicle_id' => ['required', 'integer'],
            'departure' => ['required', 'date'], 'expected_arrival_at' => ['required', 'date', 'after:departure'],
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
        ]);
        $allowedFacilityIds = $hub->children()->where('kind', 'station_hub')->where('is_active', true)->pluck('id')->push($hub->id)->all();
        $originId = (int) $data['origin_id'];
        $destinationId = (int) $data['destination_id'];
        abort_if(\Illuminate\Support\Carbon::parse($data['departure'])->isPast(), 422, 'Scheduled departure must be in the future.');
        abort_unless(in_array($originId, array_map('intval', $allowedFacilityIds), true), 404);
        $destination = LogisticsFacility::query()->where('logistics_company_id', $hub->logistics_company_id)
            ->where('is_active', true)->whereKey($destinationId)->firstOrFail();
        $validDestination = $data['type'] === 'station_transfer'
            ? ($destination->id === $hub->id || ($destination->kind === 'station_hub' && (int) $destination->parent_id === (int) $hub->id))
            : ($originId === (int) $hub->id && $destination->kind === 'province_hub' && $destination->id !== $hub->id);
        abort_unless($validDestination, 422, 'Choose a destination allowed for this trip type.');

        $trip = DB::transaction(function () use ($hub, $data, $originId, $destinationId) {
            $driver = $hub->transportDrivers()->whereKey($data['driver_id'])->lockForUpdate()->firstOrFail();
            abort_unless($driver->status === 'available' && $driver->license_expires_at?->toDateString() >= substr($data['departure'], 0, 10), 422, 'The selected Driver is unavailable or has an expired license.');
            $driverUser = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driverUser->status === 'approved' && $driverUser->account_status === 'active', 422, 'The selected Driver account is not active.');
            $vehicle = $hub->company->vehicles()->where('province_hub_id', $hub->id)->whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
            abort_unless($vehicle->status === 'available', 422, 'The selected vehicle is no longer available.');

            $orders = collect($data['orders'])->map(fn ($id) => $this->eligibleTripOrders($hub, $originId, $destinationId)->lockForUpdate()->findOrFail($id));
            $trip = LogisticsTransportTrip::create([
                'logistics_company_id' => $hub->logistics_company_id, 'trip_type' => $data['type'],
                'origin_facility_id' => $originId, 'destination_facility_id' => $destinationId,
                'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'status' => 'scheduled',
                'scheduled_departure_at' => $data['departure'],
                'expected_arrival_at' => $data['expected_arrival_at'], 'created_by' => auth()->id(),
            ]);
            $trip->orders()->attach($orders->modelKeys());
            $driver->update(['status' => 'on_trip']);
            $vehicle->update(['status' => 'on_trip']);

            return $trip->load(['origin', 'destination', 'driver.user', 'vehicle', 'orders']);
        }, 3);

        return response()->json(['trip' => $this->tripCard($trip), 'message' => 'Transport trip scheduled and parcels assigned.'], 201);
    }

    public function cancelTrip(Request $request, LogisticsTransportTrip $trip): JsonResponse
    {
        $hub = $this->facility($request);
        $facilityScopeIds = $hub->children()->pluck('id')->map(fn ($id) => (int) $id)->push((int) $hub->id)->all();
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && in_array((int) $trip->origin_facility_id, $facilityScopeIds, true), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        DB::transaction(function () use ($trip, $data) {
            $lockedTrip = LogisticsTransportTrip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedTrip->status === 'scheduled', 409, 'Only a scheduled trip can be cancelled.');
            abort_if(\App\Models\Profiles\OrderFacilityStop::query()->where('facility_id', $lockedTrip->origin_facility_id)
                ->whereNotNull('departed_at')->whereIn('order_id', $lockedTrip->orders()->select('orders.id'))->exists(),
                409, 'A parcel has already been scanned for departure. Complete the trip or resolve the scan first.');
            $lockedTrip->update(['status' => 'cancelled', 'cancel_reason' => $data['reason']]);
            $lockedTrip->driver()->where('status', 'on_trip')->update(['status' => 'available']);
            $lockedTrip->vehicle()->where('status', 'on_trip')->update(['status' => 'available']);
        }, 3);

        return response()->json(['trip' => $this->tripCard($trip->fresh(['origin', 'destination', 'driver.user', 'vehicle', 'orders'])) + ['can_cancel' => true], 'message' => 'Trip cancelled and assignments released.']);
    }

    public function departTrip(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        $hub = $this->facility($request);
        $scopeIds = $hub->children()->where('kind', 'station_hub')->pluck('id')->push($hub->id);
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && $scopeIds->contains($trip->origin_facility_id), 404);
        $trip = $workflow->depart($trip, $request->user());

        return response()->json(['trip' => $this->tripCard($trip) + ['can_cancel' => false, 'can_depart' => true,
            'can_arrive' => $scopeIds->contains($trip->destination_facility_id)], 'message' => 'Trip departure recorded.']);
    }

    public function arriveTrip(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        $hub = $this->facility($request);
        $scopeIds = $hub->children()->where('kind', 'station_hub')->pluck('id')->push($hub->id);
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && $scopeIds->contains($trip->destination_facility_id), 404);
        $trip = $workflow->arrive($trip);

        return response()->json(['trip' => $this->tripCard($trip) + ['can_cancel' => false, 'can_depart' => false,
            'can_arrive' => true], 'message' => 'Trip arrival recorded; Driver and vehicle are available again.']);
    }

    public function scanParcel(Request $request, PortalParcelScanService $workflow): JsonResponse
    {
        $facility = $this->facility($request);
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:100'],
            'action' => ['required', Rule::in(['auto'])], 'scan_key' => ['required', 'uuid']]);

        return response()->json($workflow->record($request->user(), $facility, trim($data['tracking_number']), $data['scan_key']));
    }

    public function storeVehicle(Request $request): JsonResponse
    {
        $hub = $this->facility($request);
        $data = $this->validateVehicle($request, $hub);
        $vehicle = $hub->company->vehicles()->create([
            'province_hub_id' => $hub->id, 'vehicle_code' => strtoupper($data['code']),
            'plate_number' => $this->normalizePlate($data['plate']), 'vehicle_type' => $data['type'],
            'capacity_kg' => (int) round((float) $data['capacity'] * 1000), 'status' => 'available',
        ]);

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => 'Vehicle added and available for a trip.'], 201);
    }

    public function updateVehicle(Request $request, LogisticsCompanyVehicle $vehicle): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $vehicle->province_hub_id === (int) $hub->id && (int) $vehicle->logistics_company_id === (int) $hub->logistics_company_id, 404);
        abort_if($vehicle->status === 'on_trip', 409, 'A vehicle cannot be edited while it is on a trip.');
        $data = $this->validateVehicle($request, $hub, $vehicle);
        $vehicle->update([
            'plate_number' => $this->normalizePlate($data['plate']), 'vehicle_type' => $data['type'],
            'capacity_kg' => (int) round((float) $data['capacity'] * 1000),
        ]);

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => 'Vehicle updated.']);
    }

    public function updateVehicleStatus(Request $request, LogisticsCompanyVehicle $vehicle): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $vehicle->province_hub_id === (int) $hub->id && (int) $vehicle->logistics_company_id === (int) $hub->logistics_company_id, 404);
        abort_if($vehicle->status === 'on_trip', 409, 'A vehicle on a trip cannot change status.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'maintenance_note' => ['required_if:status,maintenance', 'nullable', 'string', 'min:5', 'max:500'],
        ]);
        $vehicle->update([
            'status' => $data['status'],
            'maintenance_note' => $data['status'] === 'maintenance' ? $data['maintenance_note'] : null,
            'maintenance_at' => $data['status'] === 'maintenance' ? ($vehicle->maintenance_at ?? now()) : null,
        ]);

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => 'Vehicle status updated.']);
    }

    public function reviewRider(Request $request, \App\Models\Profiles\CourierDetail $courierDetail): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $courierDetail->station_hub_id === (int) $station->id, 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:500']]);

        DB::transaction(function () use ($courierDetail, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
            abort_unless($user->role === 'courier' && $user->status === 'pending', 409, 'This Rider application has already been reviewed.');
            $user->update($data['decision'] === 'approve'
                ? ['status' => 'approved', 'account_status' => 'active']
                : ['status' => 'disapproved', 'rejection_reason' => $data['reason'], 'rejection_notes' => null]);
        }, 3);

        return response()->json(['status' => $data['decision'] === 'approve' ? 'active' : 'rejected']);
    }

    public function updateRiderStatus(Request $request, \App\Models\Profiles\CourierDetail $courierDetail): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $courierDetail->station_hub_id === (int) $station->id, 404);
        $data = $request->validate(['active' => ['required', 'boolean']]);
        if (! $data['active']) {
            abort_if($courierDetail->tasks()->whereIn('status', ['assigned', 'in_progress'])->exists(), 409, 'Reassign this Rider’s open tasks before deactivation.');
        }
        $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
        abort_unless($user->role === 'courier' && $user->status === 'approved', 409, 'Only approved Riders can be activated or deactivated.');
        $user->update(['account_status' => $data['active'] ? 'active' : 'deactivated']);

        return response()->json(['status' => $data['active'] ? 'active' : 'inactive']);
    }

    public function plannedPage(Request $request, ?string $portal = null, ?string $page = null): View
    {
        $portal ??= $request->route('role') === 'station' ? 'station' : 'province';
        $page ??= (string) $request->route('page');
        $titles = [
            'drivers' => ['Transport Drivers', 'Manage transport driver applications and assignments.', 'users'],
            'vehicles' => ['Company Vehicles', 'Manage vehicles assigned to this Province Hub.', 'truck'],
            'parcels' => ['Parcel Operations', 'Parcel transfer workflow for this Province Hub.', 'package'],
            'trips' => ['Transport Trips', 'Schedule and track company transport trips.', 'calendar'],
            'create-trip' => ['Create Transport Trip', 'Create a company vehicle trip between network facilities.', 'plus'],
            'tracking' => ['Parcel Tracking', 'Search parcel movement in your network.', 'search'],
            'reports' => ['Reports', 'Operational reporting for your Logistics network.', 'bar-chart'],
            'messages' => ['Messages', 'Network messaging is not configured yet.', 'message'],
            'account' => ['Account Management', 'Manage the account for this Logistics facility.', 'shield'],
            'pickups' => ['Pickup Requests', 'Verify seller pickup requests and assign a Rider.', 'package'],
            'tasks' => ['Task Assignment', 'Assign pickup and delivery tasks to approved Riders.', 'bike'],
            'delivery-queue' => ['Delivery Queue', 'Review parcels ready for last-mile delivery.', 'map-pin'],
            'incoming' => ['Incoming Parcels', 'Receive parcel transfers arriving at this Station Hub.', 'inbox'],
            'sorting' => ['Parcel Sorting', 'Sort received parcels to the next route step.', 'layers'],
            'outgoing' => ['Outgoing Transfers', 'Review parcels waiting for a Province Hub transport trip.', 'truck'],
            'riders' => ['Rider Management', 'Review and manage Riders assigned to this Station Hub.', 'users'],
            'linehaul' => ['Transport Trips', 'Trips between Stations and Province Hubs are managed by the Province Hub.', 'truck'],
        ];
        [$title, $text, $icon] = $titles[$page] ?? ['Logistics', 'This workflow is not available yet.', 'info'];

        return view('logistics.preview.coming-next', [
            'title' => $title, 'text' => $text, 'icon' => $icon, 'role' => $portal,
            'spec' => 'the Logistics backend plan',
            'shows' => ['This workflow is awaiting its operational actions and validation.'],
        ]);
    }

    public function managerIndex(Request $request): View
    {
        $company = $this->company($request);
        return view('logistics.company.managers', [
            'hubNames' => $company->provinceHubs()->with('children')->get()->flatMap(fn ($hub) => [$hub->name, ...$hub->children->pluck('name')->all()])->all(),
            'managers' => FacilityStaff::query()->whereHas('facility', fn ($q) => $q->where('logistics_company_id', $company->id))
                ->with(['facility', 'user'])->get()->map(fn ($staff) => [
                    'name' => $staff->user->name, 'email' => $staff->user->email,
                    'hub' => $staff->facility->name, 'role' => ucfirst(str_replace('_', ' ', $staff->staff_title)),
                    'last_login' => $staff->user->last_login_at?->diffForHumans() ?? 'Never',
                    'status' => $staff->user->account_status === 'active' ? 'Active' : 'Inactive',
                ])->all(),
        ]);
    }

    private function company(Request $request): LogisticsCompany
    {
        return $request->user()->logisticsCenterDetail->company;
    }

    private function facility(Request $request): LogisticsFacility
    {
        return $request->attributes->get('logistics_facility');
    }

    private function hubCard(LogisticsFacility $hub): array
    {
        $hub->loadMissing(['staff.user', 'children.staff.user', 'children.riders.user', 'company']);
        $manager = $hub->staff?->user;
        $orders = $this->ordersAtFacilityAndChildren($hub, $hub->children->pluck('id'));
        $stations = $hub->children->where('kind', 'station_hub')->map(fn ($station) => $this->stationCard($station))->values()->all();
        $trips = $hub->company?->trips()->with(['origin', 'destination', 'driver.user', 'vehicle'])
            ->where(fn ($q) => $q->where('origin_facility_id', $hub->id)->orWhere('destination_facility_id', $hub->id))
            ->whereIn('status', ['scheduled', 'in_transit'])->latest()->get() ?? collect();
        $drivers = $hub->transportDrivers()->get();
        $vehicles = $hub->company?->vehicles()->where('province_hub_id', $hub->id)->get() ?? collect();

        return [
            'slug' => (string) $hub->id, 'id' => $hub->id, 'name' => $hub->name,
            'province' => $hub->province, 'place' => trim($hub->municipality.', '.$hub->province),
            'address' => $hub->address, 'manager' => $manager?->name ?? 'Manager not assigned',
            'email' => $manager?->email ?? '',
            'pending_email' => $manager ? DB::table('logistics_email_changes')->where('user_id', $manager->id)
                ->where('expires_at', '>', now())->value('new_email') : null,
            'phone' => $manager?->phone_number ?? '',
            'stations' => $stations,
            'drivers' => $drivers->count(),
            'vehicles' => $vehicles->count(),
            'parcels' => (clone $orders)->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])->count(),
            'open' => (clone $orders)->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])->count(),
            'status' => $hub->is_active ? 'Active' : 'Inactive', 'is_active' => $hub->is_active,
            'trips' => $trips->map(fn ($trip) => [
                $trip->id, $trip->trip_type, ($trip->origin?->name ?? '—').' to '.($trip->destination?->name ?? '—'),
                $trip->driver?->user?->name ?? 'Unassigned', $trip->vehicle?->vehicle_code ?? '—',
                $trip->orders()->count(), ucfirst(str_replace('_', ' ', $trip->status)),
            ])->all(),
            'fleet' => [
                $drivers->where('status', 'available')->count(), $drivers->where('status', 'on_trip')->count(), $drivers->where('status', 'off_duty')->count(),
                $vehicles->where('status', 'available')->count(), $vehicles->where('status', 'on_trip')->count(), $vehicles->where('status', 'maintenance')->count(),
            ],
            'perf' => ['on_time' => 0, 'failed' => 0, 'hours' => 0],
        ];
    }

    private function stationCard(LogisticsFacility $station): array
    {
        $station->loadMissing('staff.user');
        $manager = $station->staff?->user;
        $orders = $this->ordersAtFacilityAndChildren($station, collect());

        return [
            'id' => $station->id, 'slug' => (string) $station->id, 'name' => $station->name,
            'municipality' => $station->municipality, 'address' => $station->address,
            'manager' => $manager?->name ?? 'Manager not assigned', 'email' => $manager?->email ?? '',
            'phone' => $manager?->phone_number ?? '', 'riders' => $station->riders()->whereHas('user', fn ($q) => $q->where('status', 'approved'))->count(),
            'parcels' => (clone $orders)->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])->count(),
            'open' => (clone $orders)->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])->count(),
            'status' => $station->is_active ? 'Active' : 'Inactive', 'is_active' => $station->is_active,
            'perf' => ['on_time' => 0, 'failed' => 0, 'hours' => 0],
        ];
    }

    private function validateVehicle(Request $request, LogisticsFacility $hub, ?LogisticsCompanyVehicle $vehicle = null): array
    {
        $plate = strtoupper(preg_replace('/\s+/', '', (string) $request->input('plate')));
        if (preg_match('/^([A-Z]{3})(\d{3,4})$/', $plate, $matches)) {
            $plate = $matches[1].' '.$matches[2];
        }
        $request->merge(['plate' => $plate]);
        $companyId = $hub->logistics_company_id;
        $codeRule = Rule::unique('company_vehicles', 'vehicle_code')->where('logistics_company_id', $companyId);
        $plateRule = Rule::unique('company_vehicles', 'plate_number')->where('logistics_company_id', $companyId);
        if ($vehicle) {
            $codeRule->ignore($vehicle->id);
            $plateRule->ignore($vehicle->id);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9-]+$/i', $codeRule],
            'plate' => ['required', 'string', 'regex:/^[A-Z]{3} \d{3,4}$/', $plateRule],
            'type' => ['required', Rule::in(['L300 Van', 'Closed Van', '6-Wheeler Truck', '10-Wheeler'])],
            'capacity' => ['required', 'numeric', 'min:0.5', 'max:40'],
        ]);
    }

    private function normalizePlate(string $plate): string
    {
        $plate = strtoupper(preg_replace('/\s+/', '', $plate));
        preg_match('/^([A-Z]{3})(\d{3,4})$/', $plate, $matches);
        return $matches[1].' '.$matches[2];
    }

    private function vehicleCard(LogisticsCompanyVehicle $vehicle): array
    {
        $currentTrip = $vehicle->trips()->with('driver.user')->whereIn('status', ['scheduled', 'in_transit'])->latest()->first();
        return [
            'id' => $vehicle->id, 'code' => $vehicle->vehicle_code, 'plate' => $vehicle->plate_number,
            'type' => $vehicle->vehicle_type, 'capacity' => round(($vehicle->capacity_kg ?? 0) / 1000, 1),
            'status' => $vehicle->status, 'done' => $vehicle->trips()->where('status', 'completed')->count(),
            'trip' => $currentTrip ? 'TRP-'.str_pad((string) $currentTrip->id, 5, '0', STR_PAD_LEFT) : null,
            'driver' => $currentTrip?->driver?->user?->name,
            'note' => $vehicle->maintenance_note, 'since' => $vehicle->maintenance_at?->toDateString(),
        ];
    }

    private function eligibleTripOrders(LogisticsFacility $hub, int $originId, int $destinationId)
    {
        return \App\Models\Ecommerce\Order::query()->where('status', 'sorted')
            ->whereExists(function ($query) use ($originId, $destinationId) {
                $query->selectRaw('1')->from('order_facility_stops as current_stop')
                    ->join('order_facility_stops as next_stop', function ($join) {
                        $join->on('next_stop.order_id', '=', 'current_stop.order_id')
                            ->whereRaw('next_stop.position = current_stop.position + 1');
                    })
                    ->whereColumn('current_stop.order_id', 'orders.id')
                    ->where('current_stop.facility_id', $originId)
                    ->where('next_stop.facility_id', $destinationId)
                    ->whereNotNull('current_stop.sorted_at')->whereNull('current_stop.departed_at')
                    ->whereNull('next_stop.arrived_at');
            })
            ->whereDoesntHave('transportTrips', fn ($trips) => $trips->whereIn('transport_trips.status', ['scheduled', 'in_transit']))
            ->whereDoesntHave('logisticsExceptions', fn ($query) => $query->where('status', 'open'))
            ->whereHas('facilityStops', fn ($stops) => $stops->where('facility_id', $hub->id));
    }

    private function tripCard(LogisticsTransportTrip $trip): array
    {
        return [
            'key' => $trip->id, 'id' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT),
            'type' => $trip->trip_type, 'origin' => $trip->origin?->name ?? '—',
            'destination' => $trip->destination?->name ?? '—', 'driver' => $trip->driver?->user?->name ?? 'Unassigned',
            'vehicle' => $trip->vehicle?->vehicle_code ?? 'Unassigned', 'parcels' => $trip->orders->count(),
            'status' => $trip->status, 'scheduled' => $trip->scheduled_departure_at?->toIso8601String(),
            'expectedArrival' => $trip->expected_arrival_at?->toIso8601String(),
            'departed' => $trip->departed_at?->toIso8601String(), 'arrived' => $trip->arrived_at?->toIso8601String(),
            'note' => $trip->cancel_reason,
            'manifest' => $trip->orders->map(fn ($order) => $order->tracking_number)->values()->all(),
        ];
    }

    private function driverCard(TransportDriverDetail $driver): array
    {
        $current = $driver->trips->first(fn ($trip) => in_array($trip->status, ['scheduled', 'in_transit'], true));
        return [
            'id' => $driver->id, 'name' => $driver->user?->name ?? 'Driver',
            'email' => $driver->user?->email ?? '', 'phone' => $driver->user?->phone_number ?? '',
            'birthday' => $driver->birthday?->toDateString(), 'sex' => $driver->sex ?: '—',
            'address' => $driver->home_address ?: '—',
            'license' => $driver->license_number, 'category' => $driver->license_category,
            'expiry' => $driver->license_expires_at?->toDateString(),
            'status' => $driver->status, 'trip' => $current ? 'TRP-'.str_pad((string) $current->id, 5, '0', STR_PAD_LEFT) : null,
            'done' => $driver->trips->where('status', 'completed')->count(),
            'applied' => $driver->created_at?->toDateString(),
            'license_url' => route('lgp.province.drivers.document', [$driver, 'license']),
            'valid_id_url' => route('lgp.province.drivers.document', [$driver, 'valid-id']),
        ];
    }

    private function ordersAtFacilityAndChildren(LogisticsFacility $facility, $children)
    {
        $ids = collect([$facility->id])->merge($children)->unique()->values();
        return \App\Models\Ecommerce\Order::query()->where(function ($query) use ($ids) {
            $query->whereIn('origin_station_id', $ids)->orWhereIn('destination_station_id', $ids)
                ->orWhereIn('origin_province_hub_id', $ids)->orWhereIn('destination_province_hub_id', $ids)
                ->orWhereHas('facilityStops', fn ($stops) => $stops->whereIn('facility_id', $ids));
        });
    }

    private function stageCounts(LogisticsFacility $station): array
    {
        $orders = $this->ordersAtFacilityAndChildren($station, collect());
        return [
            'pickup_requests' => (clone $orders)->where('status', 'ready_for_pickup')->count(),
            'awaiting_sorting' => (clone $orders)->where('status', 'at_sorting_center')->count(),
            'ready_for_delivery' => (clone $orders)->whereIn('status', ['sorted', 'at_destination_hub'])->count(),
            'out_for_delivery' => (clone $orders)->where('status', 'out_for_delivery')->count(),
            'received_today' => (clone $orders)->whereDate('updated_at', today())->whereIn('status', ['at_sorting_center', 'sorted'])->count(),
            'delivered_today' => (clone $orders)->whereDate('updated_at', today())->whereIn('status', ['delivered', 'completed'])->count(),
        ];
    }

    private function waitingForTransferCount(LogisticsFacility $station): int
    {
        return OrderFacilityStop::query()->where('facility_id', $station->id)
            ->whereNotNull('arrived_at')->whereNotNull('sorted_at')->whereNull('departed_at')
            ->whereHas('order', fn ($query) => $query->where('status', 'sorted'))
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('order_facility_stops as next_stop')
                    ->whereColumn('next_stop.order_id', 'order_facility_stops.order_id')
                    ->whereColumn('next_stop.position', '>', 'order_facility_stops.position');
            })->count();
    }

    private function ensureNoOpenWork(LogisticsFacility $facility): void
    {
        $orderUsesFacility = \App\Models\Ecommerce\Order::query()->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])
            ->where(fn ($q) => $q->where('origin_station_id', $facility->id)->orWhere('destination_station_id', $facility->id)
                ->orWhere('origin_province_hub_id', $facility->id)->orWhere('destination_province_hub_id', $facility->id)
                ->orWhereHas('facilityStops', fn ($stops) => $stops->where('facility_id', $facility->id)))->exists();
        $tripUsesFacility = LogisticsTransportTrip::query()->whereIn('status', ['scheduled', 'in_transit'])
            ->where(fn ($q) => $q->where('origin_facility_id', $facility->id)->orWhere('destination_facility_id', $facility->id))->exists();
        abort_if($orderUsesFacility || $tripUsesFacility, 409, 'Move or finish all open parcels and trips before deactivating this facility.');
    }

    private function provinceHubHasHistory(LogisticsFacility $hub): bool
    {
        $id = $hub->id;
        $orderUsesHub = DB::table('orders')->where(fn ($query) => $query
            ->where('origin_station_id', $id)->orWhere('destination_station_id', $id)
            ->orWhere('origin_province_hub_id', $id)->orWhere('destination_province_hub_id', $id))->exists();

        if ($orderUsesHub) {
            return true;
        }

        $facilityReferences = [
            'order_facility_stops' => ['facility_id'],
            'facility_scan_events' => ['facility_id'],
            'transport_trips' => ['origin_facility_id', 'destination_facility_id'],
            'transport_driver_details' => ['province_hub_id'],
            'company_vehicles' => ['province_hub_id'],
            'manual_logistics_handoffs' => ['origin_facility_id', 'destination_facility_id'],
            'cross_company_manifests' => ['origin_facility_id', 'destination_facility_id'],
            'logistics_parcel_exceptions' => ['facility_id'],
            'logistics_messages' => ['facility_id'],
            'order_shipping_earning_lines' => ['facility_id'],
        ];

        foreach ($facilityReferences as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));
            if ($columns === []) {
                continue;
            }

            $query = DB::table($table)->where(function ($query) use ($columns, $id) {
                foreach ($columns as $index => $column) {
                    $query->{$index === 0 ? 'where' : 'orWhere'}($column, $id);
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    private function managerName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $parts = explode(' ', $name, 2);
        return ['name' => $name, 'first_name' => $parts[0], 'last_name' => $parts[1] ?? $parts[0]];
    }

    private function sendManagerPasswordSetupLink(User $user): void
    {
        try {
            $token = Password::createToken($user);
            $link = route('lgp.manager-password.create', ['token' => $token, 'email' => $user->email]);
            Mail::raw(
                "Hello {$user->name},\n\nYour Vendo Logistics manager account is ready. Open this one-time link to create your password:\n\n{$link}\n\nThe link expires after the password setup period and can only be used once. If you did not expect this email, you can ignore it.\n",
                fn ($message) => $message->to($user->email)->subject('Create your Vendo Logistics manager password')
            );
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'email' => 'The password setup email could not be sent. No hub or manager account was created; check the mail configuration and try again.',
            ]);
        }
    }
}
