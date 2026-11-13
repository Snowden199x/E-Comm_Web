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
        $this->normalizeHubPhone($request);
        $data = $request->validate([
            'province' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:200'],
            'manager' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'lowercase', 'max:120', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ], $this->hubMessages());
        $provinceCode = $locations->provinceCodeForName($data['province']);
        abort_unless($provinceCode, 422, 'Choose a province from the list.');
        $province = $locations->all()[$provinceCode]['name'];
        $manager = $this->managerName($data['manager']);
        $hub = DB::transaction(function () use ($company, $data, $provinceCode, $province, $manager) {
            LogisticsCompany::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $exists = $company->provinceHubs()->where('province_code', $provinceCode)->where('is_active', true)->exists();
            if ($exists) {
                throw ValidationException::withMessages(['province' => 'This province already has an active hub. Choose another province, or deactivate that hub first.']);
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

        return response()->json(['hub' => $this->hubCard($hub), 'message' => 'Province Hub created. We emailed the manager a link to set a password.'], 201);
    }

    public function updateProvinceHub(Request $request, LogisticsFacility $hub): JsonResponse
    {
        $company = $this->company($request);
        abort_unless((int) $hub->logistics_company_id === (int) $company->id && $hub->isProvinceHub(), 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($data['is_active']) {
            abort_if($company->provinceHubs()->where('province_code', $hub->province_code)->where('is_active', true)->where('id', '!=', $hub->id)->exists(), 409,
                'This province already has an active hub. Deactivate that hub first, then activate this one.');
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
        abort_if(! $managerUserId, 409, 'This hub has no manager account. Contact support to link one before editing.');
        $this->normalizeHubPhone($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:200'],
            'manager' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($managerUserId)],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ], $this->hubMessages());
        $manager = $this->managerName($data['manager']);
        $emailToken = null;

        $hub = DB::transaction(function () use ($company, $hub, $data, $manager, $managerUserId, &$emailToken) {
            $lockedHub = LogisticsFacility::query()->whereKey($hub->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedHub->logistics_company_id === (int) $company->id && $lockedHub->isProvinceHub(), 404);
            $staff = $lockedHub->staff()->with('user')->first();
            abort_if(! $staff?->user, 409, 'This hub has no manager account. Contact support to link one before editing.');
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
                    throw ValidationException::withMessages(['email' => 'This email is waiting to be confirmed for another manager. Use a different email.']);
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
                $message .= ' We sent a confirmation link to the new email. It stays pending until the manager confirms it.';
            } catch (\Throwable $exception) {
                report($exception);
                DB::table('logistics_email_changes')->where('user_id', $hub->staff->user_id)
                    ->where('token_hash', hash('sha256', $emailToken))->delete();
                $message .= ' We could not send the confirmation email, so the manager’s email did not change. Save again to retry.';
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
            abort_if($lockedHub->children()->exists(), 409, 'This hub still has Station Hubs. Delete or move them first, then delete the hub.');
            abort_if($this->provinceHubHasHistory($lockedHub), 409, 'This hub has parcel, trip, driver, vehicle, scan, settlement or message history, so it cannot be deleted. Deactivate it instead.');

            $staff = $lockedHub->staff()->with('user')->first();
            $manager = $staff?->user;
            if ($manager && $manager->role === 'province_hub') {
                $manager->update(['account_status' => 'deactivated']);
            }

            $lockedHub->delete();
        }, 3);

        return response()->json(['id' => $hub->id, 'message' => 'Province Hub deleted. Its manager account is now deactivated.']);
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

    public function companyTracking(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($tooLong = $this->trackingSearchTooLong($request, 'lgp.company.tracking')) {
            return $tooLong;
        }

        $company = $this->company($request);
        $facilityIds = $company->facilities()->pluck('id');
        $query = \App\Models\Ecommerce\Order::query()->with(['buyer', 'seller', 'items', 'statusEvents', 'facilityStops.facility'])
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

    public function companyHandoffs(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $company = $this->company($request);
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) > 100) {
            return redirect()->route('lgp.company.handoffs')
                ->withErrors(['q' => 'The search is too long. Use a tracking number of 100 characters or fewer.']);
        }

        $mine = fn () => ManualLogisticsHandoff::query()->where(fn ($query) =>
            $query->where('origin_company_id', $company->id)->orWhere('destination_company_id', $company->id));
        $statusCounts = $mine()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $status = in_array($request->query('status'), ['awaiting_origin', 'pending', 'accepted'], true) ? $request->query('status') : null;
        $handoffs = $mine()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->whereHas('order', fn ($order) =>
                $order->where('tracking_number', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->with(['order', 'originCompany', 'destinationCompany', 'originFacility', 'destinationFacility', 'manifest'])
            ->latest()->paginate(30)->withQueryString();
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

        return view('logistics.company.handoffs', compact('company', 'handoffs', 'drivers', 'vehicles', 'manifests', 'statusCounts', 'status', 'term'));
    }

    public function facilityTracking(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $facility = $this->facility($request);
        if ($tooLong = $this->trackingSearchTooLong($request, $facility->isProvinceHub() ? 'lgp.province.tracking' : 'lgp.station.tracking')) {
            return $tooLong;
        }

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

    /** A search longer than 100 characters goes back to the clean page with a message that says how to fix it. */
    private function trackingSearchTooLong(Request $request, string $routeName): ?\Illuminate\Http\RedirectResponse
    {
        if (mb_strlen(trim((string) $request->query('q', ''))) <= 100) {
            return null;
        }

        return redirect()->route($routeName)->withErrors(['q' => 'The search is too long. Use a tracking number or order ID of 100 characters or fewer.']);
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
                'events' => $events->all(), 'flag' => $flag, 'reason' => $flag ? 'Delivery failed. The Station Hub must reassign the parcel or return it to the seller.' : null,
            ];
        })->values()->all();

        return view('logistics.shared.tracking', [
            'parcels' => $parcels,
            'routeLabels' => ['local' => 'Local delivery', 'intra_province' => 'Within province', 'inter_province' => 'Inter-province', 'manual_handoff' => 'Manual company handoff'],
            'role' => $role,
            'intro' => 'Search parcels that pass through your facilities and follow each journey.',
            'delayedRule' => 'Parcels are not flagged as delayed on this page. Check Active trips on the dashboard for late trips.',
            'scopeNote' => 'You only see parcels that pass through your facilities.',
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
        ], [
            'municipality.required' => 'Municipality is not chosen. Pick one from the list.',
            'municipality.max' => 'Municipality name is too long. Pick one from the list.',
            'name.required' => 'Station name is empty. Enter a name, like Santa Cruz Station.',
            'name.max' => 'Station name is longer than 80 characters. Shorten it.',
            'address.required' => 'Address is empty. Add the unit, street, barangay and municipality.',
            'address.min' => 'Address is too short. Add the unit, street, barangay and municipality so riders can find the station.',
            'address.max' => 'Address is longer than 200 characters. Shorten it.',
            'manager.required' => 'Manager name is empty. Enter the manager’s full name.',
            'manager.max' => 'Manager name is longer than 80 characters. Shorten it.',
            'email.required' => 'Manager email is empty. Enter an email like name@company.com.',
            'email.email' => 'That doesn’t look like an email address. Use the format name@company.com.',
            'email.lowercase' => 'Email has capital letters. Type it in lowercase.',
            'email.max' => 'Email is longer than 120 characters. Use a shorter address.',
            'email.unique' => 'This email is already in use. Enter a different email.',
            'phone.required' => 'Phone number is empty. Enter 11 digits starting with 09, like 0917 123 4567.',
            'phone.regex' => 'Phone number isn’t a mobile number. Enter 11 digits starting with 09, like 0917 123 4567.',
        ]);
        $codes = $locations->codesForNames($hub->province, $data['municipality']);
        abort_unless($codes && $codes[0] === $hub->province_code, 422, 'That municipality is outside '.$hub->province.'. Pick one from the list.');
        $manager = $this->managerName($data['manager']);
        $station = DB::transaction(function () use ($hub, $data, $codes, $manager) {
            LogisticsFacility::query()->whereKey($hub->id)->lockForUpdate()->firstOrFail();
            if ($hub->children()->where('kind', 'station_hub')->where('municipality_code', $codes[1])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['municipality' => 'This municipality already has an active Station Hub. Pick another, or deactivate that station first.']);
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

        return response()->json(['station' => $this->stationCard($station), 'message' => 'Station Hub created. We emailed its manager a link to set a password.'], 201);
    }

    public function updateStation(Request $request, LogisticsFacility $station): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $station->parent_id === (int) $hub->id && $station->kind === 'station_hub', 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($data['is_active']) {
            abort_if($hub->children()->where('kind', 'station_hub')->where('municipality_code', $station->municipality_code)
                ->where('is_active', true)->where('id', '!=', $station->id)->exists(), 409,
                'This municipality already has an active Station Hub. Deactivate that station first, then activate this one.');
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
            // The tile and the attention item use the real total; the list below is only the latest 10, to name a few of them.
            'failedTotal' => $this->ordersAtFacilityAndChildren($station, collect())->where('status', 'delivery_failed')->count(),
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
        $drivers = $hub->transportDrivers()->with(['user', 'trips.origin', 'trips.destination', 'stationHub', 'approvedBy'])
            ->latest()->get()->map(fn ($driver) => $this->driverCard($driver))->values()->all();

        return view('logistics.province.drivers', [
            'hub' => ['id' => $hub->id, 'name' => $hub->name], 'drivers' => $drivers,
            'today' => today()->toDateString(),
            'reviewUrl' => route('lgp.province.drivers.review', '__driver__'),
            'statusUrl' => route('lgp.province.drivers.status', '__driver__'),
            'documentUrl' => route('lgp.province.drivers.document', ['driver' => '__driver__', 'document' => '__document__']),
        ]);
    }

    public function reviewDriver(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $driver->province_hub_id === (int) $hub->id, 404);
        abort_if($driver->apply_to === TransportDriverDetail::APPLY_STATION, 409,
            'This driver applied to a Station Hub only, so its Station Hub reviews the application. Nothing to do here.');
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:10', 'max:300']], [
            'decision.required' => 'Choose Approve driver or Reject application.',
            'decision.in' => 'Choose Approve driver or Reject application.',
            'reason.required_if' => 'Reason is empty. Write at least 10 characters so the applicant knows what to fix.',
            'reason.min' => 'Reason is too short. Write at least 10 characters so the applicant knows what to fix.',
            'reason.max' => 'Reason is longer than 300 characters. Shorten it.',
        ]);

        $driver = DB::transaction(function () use ($driver, $data, $hub) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driver->status === 'pending' && $user->status === 'pending', 409, 'This application was already reviewed. Reload the page to see its current status.');
            if ($data['decision'] === 'approve') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver’s licence is expired, so it can’t be approved. Ask for a renewed licence.');
                $driver->update(['status' => 'available', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                $user->update(['status' => 'approved', 'account_status' => 'active', 'rejection_reason' => null]);
            } else {
                // approved_by only records an approval; a rejection is kept on
                // the account so the applicant sees the reason.
                $driver->update(['status' => 'inactive']);
                $user->update(['status' => 'disapproved', 'rejection_reason' => $data['reason'], 'account_status' => 'deactivated']);
            }
            \App\Models\Communication\Notification::create(['user_id' => $user->id,
                'type' => 'driver_application_decision',
                'title' => $data['decision'] === 'approve' ? 'Driver application approved' : 'Driver application declined',
                'message' => $data['decision'] === 'approve' ? 'Your Province Hub approved your application.' : $data['reason']]);

            return $driver->load(['user', 'trips.origin', 'trips.destination', 'stationHub', 'approvedBy']);
        }, 3);

        $name = $driver->user?->name ?? 'The driver';

        return response()->json(['driver' => $this->driverCard($driver), 'message' => $data['decision'] === 'approve'
            ? $name.' is approved. They can now be assigned to trips.'
            : $name.'’s application is rejected. They see your reason in the Driver app.']);
    }

    public function updateDriverStatus(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $driver->province_hub_id === (int) $hub->id, 404);
        abort_if($driver->apply_to === TransportDriverDetail::APPLY_STATION, 409,
            'This driver works for a Station Hub only, so that hub manages them. Ask its station manager to make the change.');
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'inactive'])]], [
            'status.required' => 'Choose Activate driver or Deactivate driver.',
            'status.in' => 'Choose Activate driver or Deactivate driver.',
        ]);

        $driver = DB::transaction(function () use ($driver, $data) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($user->status === 'approved' && $driver->status !== 'on_trip', 409,
                'This driver is on a trip or not approved yet. Change availability after the trip ends or the application is approved.');
            if ($data['status'] === 'available') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver’s licence is expired, so they can’t be activated. Ask for a renewed licence.');
            }
            $driver->update(['status' => $data['status']]);
            $user->update(['account_status' => $data['status'] === 'available' ? 'active' : 'deactivated']);

            return $driver->load(['user', 'trips.origin', 'trips.destination', 'stationHub', 'approvedBy']);
        }, 3);

        $name = $driver->user?->name ?? 'The driver';

        return response()->json(['driver' => $this->driverCard($driver), 'message' => $data['status'] === 'available'
            ? $name.' is active again. They can take trips.'
            : $name.' is deactivated. They won’t be assigned to new trips.']);
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

    /**
     * Station Hub Drivers page. A station-only driver is reviewed here. Drivers
     * who applied to the Province Hub or to both are listed read-only, because
     * only the Province Hub may approve them.
     */
    public function stationDrivers(Request $request): View
    {
        $station = $this->facility($request);
        $drivers = $station->stationTransportDrivers()
            ->with(['user', 'trips.origin', 'trips.destination', 'provinceHub', 'approvedBy'])
            ->latest()->get()->map(fn ($driver) => $this->driverCard($driver))->values()->all();

        return view('logistics.station.drivers', [
            'station' => ['id' => $station->id, 'name' => $station->name],
            'drivers' => $drivers,
            'today' => today()->toDateString(),
            'reviewUrl' => route('lgp.station.drivers.review', '__driver__'),
            'statusUrl' => route('lgp.station.drivers.status', '__driver__'),
            'documentUrl' => route('lgp.station.drivers.document', ['driver' => '__driver__', 'document' => '__document__']),
            // Assigning a driver is done on the Driver Assignments page, where
            // the destination hub and the parcels are chosen too.
            'assignUrl' => route('lgp.station.trips', ['driver' => '__driver__']),
        ]);
    }

    public function reviewStationDriver(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $driver->station_hub_id === (int) $station->id, 404);
        abort_unless($driver->apply_to === TransportDriverDetail::APPLY_STATION, 403,
            'This driver applied to the Province Hub, so only the Province Hub reviews the application. Nothing to do here.');
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:10', 'max:300']], [
            'decision.required' => 'Choose Approve driver or Reject application.',
            'decision.in' => 'Choose Approve driver or Reject application.',
            'reason.required_if' => 'Reason is empty. Write at least 10 characters so the applicant knows what to fix.',
            'reason.min' => 'Reason is too short. Write at least 10 characters so the applicant knows what to fix.',
            'reason.max' => 'Reason is longer than 300 characters. Shorten it.',
        ]);

        $driver = DB::transaction(function () use ($driver, $data) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driver->status === 'pending' && $user->status === 'pending', 409, 'This application was already reviewed. Reload the page to see its current status.');
            if ($data['decision'] === 'approve') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver’s licence is expired, so it can’t be approved. Ask for a renewed licence.');
                $driver->update(['status' => 'available', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                $user->update(['status' => 'approved', 'account_status' => 'active', 'rejection_reason' => null]);
            } else {
                // approved_by only records an approval; a rejection is kept on
                // the account so the applicant sees the reason.
                $driver->update(['status' => 'inactive']);
                $user->update(['status' => 'disapproved', 'rejection_reason' => $data['reason'], 'account_status' => 'deactivated']);
            }
            \App\Models\Communication\Notification::create(['user_id' => $user->id,
                'type' => 'driver_application_decision',
                'title' => $data['decision'] === 'approve' ? 'Driver application approved' : 'Driver application declined',
                'message' => $data['decision'] === 'approve' ? 'Your Station Hub approved your application.' : $data['reason']]);

            return $driver->load(['user', 'trips.origin', 'trips.destination', 'stationHub', 'approvedBy']);
        }, 3);

        $name = $driver->user?->name ?? 'The driver';

        return response()->json(['driver' => $this->driverCard($driver), 'message' => $data['decision'] === 'approve'
            ? $name.' is approved. They can now be assigned to trips.'
            : $name.'’s application is rejected. They see your reason in the Driver app.']);
    }

    public function updateStationDriverStatus(Request $request, TransportDriverDetail $driver): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $driver->station_hub_id === (int) $station->id, 404);
        abort_unless($driver->apply_to === TransportDriverDetail::APPLY_STATION, 403,
            'This driver is managed by the Province Hub, so only the Province Hub can make that change.');
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'inactive'])]], [
            'status.required' => 'Choose Activate driver or Deactivate driver.',
            'status.in' => 'Choose Activate driver or Deactivate driver.',
        ]);

        $driver = DB::transaction(function () use ($driver, $data) {
            $driver = TransportDriverDetail::query()->lockForUpdate()->findOrFail($driver->id);
            $user = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($user->status === 'approved' && $driver->status !== 'on_trip', 409,
                'This driver is on a trip or not approved yet. Change availability after the trip ends or the application is approved.');
            if ($data['status'] === 'available') {
                abort_unless($driver->license_expires_at && $driver->license_expires_at->toDateString() >= today()->toDateString(), 409, 'The driver’s licence is expired, so they can’t be activated. Ask for a renewed licence.');
            }
            $driver->update(['status' => $data['status']]);
            $user->update(['account_status' => $data['status'] === 'available' ? 'active' : 'deactivated']);

            return $driver->load(['user', 'trips.origin', 'trips.destination', 'stationHub', 'approvedBy']);
        }, 3);

        $name = $driver->user?->name ?? 'The driver';

        return response()->json(['driver' => $this->driverCard($driver), 'message' => $data['status'] === 'available'
            ? $name.' is active again. They can take trips.'
            : $name.' is deactivated. They won’t be assigned to new trips.']);
    }

    public function stationDriverDocument(Request $request, TransportDriverDetail $driver, string $document)
    {
        $station = $this->facility($request);
        abort_unless((int) $driver->station_hub_id === (int) $station->id, 404);
        $path = $document === 'license' ? $driver->drivers_license_path : $driver->valid_id_path;
        abort_unless(str_starts_with((string) $path, 'private/transport-drivers/')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * Station Hub → Driver Assignments. The Station Hub picks one of the
     * Transport Drivers linked to it, the Province Hub or Station Hub that the
     * driver must reach, and the parcels to load. Saving creates a scheduled
     * trip, which is what shows up on the driver's phone in the Driver app.
     */
    public function stationTrips(Request $request): View
    {
        $station = $this->facility($request);
        abort_unless($station->kind === 'station_hub', 404);
        $trips = LogisticsTransportTrip::query()
            ->where('logistics_company_id', $station->logistics_company_id)
            ->where(fn ($query) => $query->where('origin_facility_id', $station->id)
                ->orWhere('destination_facility_id', $station->id))
            ->with(['origin', 'destination', 'driver.user', 'vehicle', 'orders'])
            ->latest()->limit(60)->get()
            ->map(fn (LogisticsTransportTrip $trip) => $this->tripCard($trip) + [
                'can_cancel' => (int) $trip->origin_facility_id === (int) $station->id && $trip->status === 'scheduled',
                'can_depart' => (int) $trip->origin_facility_id === (int) $station->id,
                'can_arrive' => (int) $trip->destination_facility_id === (int) $station->id,
            ])->values()->all();

        $destinations = $this->stationTripDestinations($station);
        // Only parcels already sorted here for that destination, and not yet on
        // another trip, can be loaded (the same rule the Province Hub form uses).
        // Each row carries the details the station needs to check the load, and
        // whether the parcel's delivery address is inside the destination hub
        // (it ends there) or is only passing through it.
        $parcels = $destinations->mapWithKeys(fn ($destination) => [
            (string) $destination['id'] => $this->eligibleTripOrders($station, (int) $station->id, (int) $destination['id'])
                ->with(['buyer:id,name', 'facilityStops' => fn ($query) => $query->where('facility_id', $station->id)])
                ->get()->map(fn ($order) => [
                    'order' => $order->id,
                    'tracking' => $order->tracking_number ?: $order->number,
                    'number' => $order->number,
                    'buyer' => $order->buyer?->name,
                    'town' => $order->shipping_city ?: null,
                    'province' => $order->shipping_province ?: null,
                    'barangay' => $order->shipping_barangay ?: null,
                    'where' => collect([$order->shipping_barangay, $order->shipping_city, $order->shipping_province])
                        ->filter()->unique()->implode(', '),
                    'sorted' => $order->facilityStops->first()?->sorted_at?->toIso8601String(),
                    'delivers_here' => $this->addressIsInsideHub($order, $destination),
                ])->sortByDesc('delivers_here')->values()->all(),
        ])->all();
        $drivers = $station->stationTransportDrivers()->where('status', 'available')
            ->whereDate('license_expires_at', '>=', today())
            ->whereHas('user', fn ($query) => $query->where('status', 'approved')->where('account_status', 'active'))
            ->with('user')->get()
            ->map(fn (TransportDriverDetail $driver) => [
                'id' => $driver->id, 'name' => $driver->user?->name ?? 'Transport Driver',
                'category' => $driver->license_category, 'expiry' => $driver->license_expires_at?->toDateString(),
                'apply_label' => $driver->applyLabel(),
            ])->values()->all();
        $vehicles = ($station->company?->vehicles()->where('province_hub_id', $station->parent_id)
            ->where('status', 'available')->get() ?? collect())
            ->map(fn (LogisticsCompanyVehicle $vehicle) => [
                'id' => $vehicle->id, 'code' => $vehicle->vehicle_code, 'type' => $vehicle->vehicle_type,
                'plate' => $vehicle->plate_number,
            ])->values()->all();

        return view('logistics.station.trips', [
            'station' => ['id' => $station->id, 'name' => $station->name],
            'trips' => $trips, 'destinations' => $destinations->all(),
            'drivers' => $drivers, 'vehicles' => $vehicles, 'parcels' => $parcels,
            // Same shape as the datetime-local inputs, so the page can check a
            // past departure before it is sent.
            'now' => now()->format('Y-m-d\\TH:i'),
            'selectedDriver' => (int) $request->query('driver', 0),
            'defaultDeparture' => now()->addHour()->format('Y-m-d\\TH:i'),
            'defaultArrival' => now()->addHours(6)->format('Y-m-d\\TH:i'),
            'storeUrl' => route('lgp.station.trips.store'),
            'trackingUrl' => route('lgp.station.tracking'),
            'driversUrl' => route('lgp.station.drivers'),
            'cancelUrl' => route('lgp.station.trips.cancel', '__trip__'),
            'departUrl' => route('lgp.station.trips.depart', '__trip__'),
            'arriveUrl' => route('lgp.station.trips.arrive', '__trip__'),
        ]);
    }

    /**
     * The facilities this Station Hub may send a driver to: its Province Hub and
     * the other active Station Hubs in the same network.
     */
    private function stationTripDestinations(LogisticsFacility $station)
    {
        $label = fn (LogisticsFacility $facility) => [
            'id' => $facility->id, 'name' => $facility->name,
            'kind' => $facility->isProvinceHub() ? 'Province Hub' : 'Station Hub',
            'municipality' => $facility->municipality,
            'province' => $facility->province,
            'area' => trim(($facility->municipality ?? '').', '.($facility->province ?? ''), ', '),
        ];
        $provinceHub = $station->parent_id
            ? LogisticsFacility::query()->whereKey($station->parent_id)->where('is_active', true)->first()
            : null;
        $stations = LogisticsFacility::query()->where('kind', 'station_hub')->where('is_active', true)
            ->where('parent_id', $station->parent_id)->where('id', '!=', $station->id)
            ->orderBy('municipality')->orderBy('name')->get();

        return collect($provinceHub ? [$label($provinceHub)] : [])
            ->concat($stations->map($label))->values();
    }

    /**
     * True when the delivery address sits in the same municipality (and province,
     * when both are known) as the hub, so the parcel is delivered from there
     * instead of only passing through.
     */
    private function addressIsInsideHub(\App\Models\Ecommerce\Order $order, array $destination): bool
    {
        $normalize = fn (?string $value) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
        $town = $normalize($order->shipping_city);
        $hubTown = $normalize($destination['municipality'] ?? null);
        if ($town === '' || $hubTown === '' || $town !== $hubTown) {
            return false;
        }
        $province = $normalize($order->shipping_province);
        $hubProvince = $normalize($destination['province'] ?? null);

        return $province === '' || $hubProvince === '' || $province === $hubProvince;
    }

    public function storeStationTrip(Request $request): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless($station->kind === 'station_hub' && $station->parent_id, 404);
        $data = $request->validate([
            'destination_id' => ['required', 'integer', Rule::in($this->stationTripDestinations($station)->pluck('id')->all())],
            'driver_id' => ['required', 'integer'], 'vehicle_id' => ['required', 'integer'],
            'departure' => ['required', 'date'], 'expected_arrival_at' => ['required', 'date', 'after:departure'],
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
        ], [
            'destination_id.required' => 'Hub is not chosen. Pick the hub the driver must reach.',
            'destination_id.in' => 'That hub isn’t available for this station. Reload the page and pick another hub.',
            'driver_id.required' => 'Driver is not chosen. Pick an available driver.',
            'vehicle_id.required' => 'Vehicle is not chosen. Pick an available vehicle.',
            'departure.required' => 'Departure is empty. Set when the trip leaves.',
            'departure.date' => 'Departure isn’t a valid date and time. Pick it from the calendar.',
            'expected_arrival_at.required' => 'Expected arrival is empty. Set when the trip should arrive.',
            'expected_arrival_at.date' => 'Expected arrival isn’t a valid date and time. Pick it from the calendar.',
            'expected_arrival_at.after' => 'Expected arrival is before the departure. Pick a time after the departure.',
            'orders.required' => 'No parcels are selected. Select at least one parcel to load.',
            'orders.min' => 'No parcels are selected. Select at least one parcel to load.',
            'orders.*.exists' => 'A selected parcel no longer exists. Reload the page and select the parcels again.',
            'orders.*.distinct' => 'A parcel is selected twice. Reload the page and select the parcels again.',
        ]);
        $destinationId = (int) $data['destination_id'];
        abort_if(\Illuminate\Support\Carbon::parse($data['departure'])->isPast(), 422, 'Departure is in the past. Pick a time from now on.');

        $trip = DB::transaction(function () use ($station, $data, $destinationId) {
            // Only a driver linked to this Station Hub can be assigned here, and
            // the licence is checked again against the departure date.
            $driver = $station->stationTransportDrivers()->whereKey($data['driver_id'])->lockForUpdate()->first()
                ?? abort(422, 'This driver isn’t linked to this station. Reload the page and pick another driver.');
            abort_unless($driver->status === 'available'
                && $driver->license_expires_at?->toDateString() >= substr($data['departure'], 0, 10), 422,
                'This driver is no longer available, or their licence expires before the departure. Reload the page and pick another driver.');
            $driverUser = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driverUser->status === 'approved' && $driverUser->account_status === 'active', 422,
                'This driver’s account is not active. Pick another driver.');
            $vehicle = $station->company->vehicles()->where('province_hub_id', $station->parent_id)
                ->whereKey($data['vehicle_id'])->lockForUpdate()->first()
                ?? abort(422, 'This vehicle isn’t part of your Province Hub. Reload the page and pick another vehicle.');
            abort_unless($vehicle->status === 'available', 422, 'This vehicle is no longer available. Reload the page and pick another vehicle.');

            $orders = collect($data['orders'])->map(fn ($id) => $this->eligibleTripOrders($station, (int) $station->id, $destinationId)
                ->lockForUpdate()->find($id)
                ?? abort(409, 'A selected parcel is no longer ready for this hub. Reload the page and select the parcels again.'));
            $trip = LogisticsTransportTrip::create([
                'logistics_company_id' => $station->logistics_company_id, 'trip_type' => 'station_transfer',
                'origin_facility_id' => $station->id, 'destination_facility_id' => $destinationId,
                'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'status' => 'scheduled',
                'scheduled_departure_at' => $data['departure'],
                'expected_arrival_at' => $data['expected_arrival_at'], 'created_by' => auth()->id(),
            ]);
            $trip->orders()->attach($orders->pluck('id')->all());
            $driver->update(['status' => 'on_trip']);
            $vehicle->update(['status' => 'on_trip']);

            return $trip->load(['origin', 'destination', 'driver.user', 'vehicle', 'orders']);
        }, 3);

        $code = 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT);
        $parcels = $trip->orders->count();

        return response()->json([
            'trip' => $this->tripCard($trip) + ['can_cancel' => true, 'can_depart' => true, 'can_arrive' => false],
            'message' => $code.' is assigned to '.($trip->driver?->user?->name ?? 'the driver').' for '
                .($trip->destination?->name ?? 'the destination hub').' with '.$parcels.' '
                .($parcels === 1 ? 'parcel' : 'parcels').'. It is in the driver app now. Scan each parcel at departure, then record the departure.',
        ], 201);
    }

    public function provinceParcels(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $hub = $this->facility($request);
        if ($tooLong = $this->trackingSearchTooLong($request, 'lgp.province.parcels')) {
            return $tooLong;
        }

        // Read-only filters: a stage chip (?stage=) and a tracking number search (?q=). The stage comes from the stop's own timestamps.
        $stageScopes = [
            'incoming' => fn ($query) => $query->whereNull('arrived_at'),
            'sorting' => fn ($query) => $query->whereNotNull('arrived_at')->whereNull('sorted_at'),
            'outgoing' => fn ($query) => $query->whereNotNull('sorted_at')->whereNull('departed_at'),
            'departed' => fn ($query) => $query->whereNotNull('sorted_at')->whereNotNull('departed_at'),
        ];
        $base = fn () => OrderFacilityStop::query()->where('facility_id', $hub->id)
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']));
        $stage = array_key_exists((string) $request->query('stage'), $stageScopes) ? (string) $request->query('stage') : 'all';
        $term = trim((string) $request->query('q', ''));

        $counts = ['all' => $base()->count()];
        foreach ($stageScopes as $key => $scope) {
            $counts[$key] = $scope($base())->count();
        }

        $query = $base();
        if ($stage !== 'all') {
            $stageScopes[$stage]($query);
        }
        if ($term !== '') {
            $query->whereHas('order', fn ($order) => $order->where('tracking_number', 'like', '%'.$term.'%'));
        }
        $stops = $query->with(['order.facilityStops.facility', 'facility'])->latest()->paginate(40)->withQueryString();
        $rows = $stops->through(function ($stop) {
            $next = $stop->order->facilityStops->firstWhere('position', $stop->position + 1);
            $stage = ! $stop->arrived_at ? 'Incoming' : (! $stop->sorted_at ? 'Sorting'
                : ($stop->departed_at ? 'Departed' : 'Outgoing'));
            return ['tracking' => $stop->order->tracking_number, 'status' => $stop->order->status_label,
                'stage' => $stage, 'next' => $next?->facility?->name ?? 'Final delivery',
                'arrived' => $stop->arrived_at?->format('M j, g:i A'),
                'departed' => $stop->departed_at?->format('M j, g:i A'),
                'url' => route('lgp.province.tracking', ['q' => $stop->order->tracking_number])];
        });

        return view('logistics.province.live-parcels', [
            'hub' => $hub, 'rows' => $rows, 'counts' => $counts, 'stage' => $stage, 'q' => $term,
            'createUrl' => route('lgp.province.trips.create'), 'trackingUrl' => route('lgp.province.tracking'),
        ]);
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
            'trackingUrl' => route('lgp.province.tracking'),
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
        // A station-only driver never leaves its Station Hub, so it cannot be
        // assigned to an inter-hub trip.
        $drivers = $hub->transportDrivers()->where('status', 'available')->whereDate('license_expires_at', '>=', today())
            ->where('apply_to', '!=', TransportDriverDetail::APPLY_STATION)
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
        ], [
            'type.required' => 'Trip type is missing. Pick Station transfer or Inter-province.',
            'type.in' => 'That trip type isn’t available. Pick Station transfer or Inter-province.',
            'origin_id.required' => 'The start of the trip is missing. Reload the page and try again.',
            'destination_id.required' => 'Destination is not chosen. Pick where this trip goes.',
            'destination_id.different' => 'The trip starts and ends at the same hub. Pick a different destination.',
            'driver_id.required' => 'Driver is not chosen. Pick an available driver.',
            'vehicle_id.required' => 'Vehicle is not chosen. Pick an available vehicle.',
            'departure.required' => 'Departure is empty. Set when the trip leaves.',
            'departure.date' => 'Departure isn’t a valid date and time. Pick it from the calendar.',
            'expected_arrival_at.required' => 'Expected arrival is empty. Set when the trip should arrive.',
            'expected_arrival_at.date' => 'Expected arrival isn’t a valid date and time. Pick it from the calendar.',
            'expected_arrival_at.after' => 'Expected arrival is before the departure. Pick a time after the departure.',
            'orders.required' => 'No parcels are selected. Select at least one parcel to load.',
            'orders.min' => 'No parcels are selected. Select at least one parcel to load.',
            'orders.*.exists' => 'A selected parcel no longer exists. Reload the page and select the parcels again.',
            'orders.*.distinct' => 'A parcel is selected twice. Reload the page and select the parcels again.',
        ]);
        $allowedFacilityIds = $hub->children()->where('kind', 'station_hub')->where('is_active', true)->pluck('id')->push($hub->id)->all();
        $originId = (int) $data['origin_id'];
        $destinationId = (int) $data['destination_id'];
        abort_if(\Illuminate\Support\Carbon::parse($data['departure'])->isPast(), 422, 'Departure is in the past. Pick a time from now on.');
        abort_unless(in_array($originId, array_map('intval', $allowedFacilityIds), true), 404);
        $destination = LogisticsFacility::query()->where('logistics_company_id', $hub->logistics_company_id)
            ->where('is_active', true)->whereKey($destinationId)->firstOrFail();
        $validDestination = $data['type'] === 'station_transfer'
            ? ($destination->id === $hub->id || ($destination->kind === 'station_hub' && (int) $destination->parent_id === (int) $hub->id))
            : ($originId === (int) $hub->id && $destination->kind === 'province_hub' && $destination->id !== $hub->id);
        abort_unless($validDestination, 422, 'This destination isn’t allowed for this trip type. Pick a Station Hub for a station transfer, or another Province Hub for an inter-province trip.');

        $trip = DB::transaction(function () use ($hub, $data, $originId, $destinationId) {
            $driver = $hub->transportDrivers()->whereKey($data['driver_id'])->lockForUpdate()->first()
                ?? abort(422, 'This driver isn’t part of this hub. Reload the page and pick another driver.');
            abort_unless($driver->status === 'available' && $driver->license_expires_at?->toDateString() >= substr($data['departure'], 0, 10), 422, 'This driver is no longer available, or their licence expires before the departure. Reload the page and pick another driver.');
            $driverUser = User::query()->lockForUpdate()->findOrFail($driver->user_id);
            abort_unless($driverUser->status === 'approved' && $driverUser->account_status === 'active', 422, 'This driver’s account is not active. Pick another driver.');
            $vehicle = $hub->company->vehicles()->where('province_hub_id', $hub->id)->whereKey($data['vehicle_id'])->lockForUpdate()->first()
                ?? abort(422, 'This vehicle isn’t part of this hub. Reload the page and pick another vehicle.');
            abort_unless($vehicle->status === 'available', 422, 'This vehicle is no longer available. Reload the page and pick another vehicle.');

            $orders = collect($data['orders'])->map(fn ($id) => $this->eligibleTripOrders($hub, $originId, $destinationId)->lockForUpdate()->find($id)
                ?? abort(409, 'A selected parcel is no longer ready for this route. Reload the page and select the parcels again.'));
            $trip = LogisticsTransportTrip::create([
                'logistics_company_id' => $hub->logistics_company_id, 'trip_type' => $data['type'],
                'origin_facility_id' => $originId, 'destination_facility_id' => $destinationId,
                'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'status' => 'scheduled',
                'scheduled_departure_at' => $data['departure'],
                'expected_arrival_at' => $data['expected_arrival_at'], 'created_by' => auth()->id(),
            ]);
            $trip->orders()->attach($orders->pluck('id')->all());
            $driver->update(['status' => 'on_trip']);
            $vehicle->update(['status' => 'on_trip']);

            return $trip->load(['origin', 'destination', 'driver.user', 'vehicle', 'orders']);
        }, 3);

        $count = $trip->orders->count();
        $message = 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' is scheduled with '.$count.($count === 1 ? ' parcel' : ' parcels')
            .'. Scan each parcel at departure, then record the departure.';
        // The next page is the Trips list, which shows this in its top banner.
        session()->flash('success', $message);

        return response()->json(['trip' => $this->tripCard($trip), 'message' => $message], 201);
    }

    public function cancelTrip(Request $request, LogisticsTransportTrip $trip): JsonResponse
    {
        $hub = $this->facility($request);
        $facilityScopeIds = $hub->children()->pluck('id')->map(fn ($id) => (int) $id)->push((int) $hub->id)->all();
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && in_array((int) $trip->origin_facility_id, $facilityScopeIds, true), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [
            'reason.required' => 'Reason is empty. Write a short reason so the stations know why the trip was cancelled.',
            'reason.min' => 'Reason is too short. Write at least 5 characters so the stations know why the trip was cancelled.',
            'reason.max' => 'Reason is too long. Keep it to 500 characters or fewer.',
        ]);

        DB::transaction(function () use ($trip, $data) {
            $lockedTrip = LogisticsTransportTrip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedTrip->status === 'scheduled', 409, 'This trip is no longer scheduled, so it can’t be cancelled. Reload the page to see its current status.');
            abort_if(\App\Models\Profiles\OrderFacilityStop::query()->where('facility_id', $lockedTrip->origin_facility_id)
                ->whereNotNull('departed_at')->whereIn('order_id', $lockedTrip->orders()->select('orders.id'))->exists(),
                409, 'A parcel was already scanned for departure, so this trip can’t be cancelled. Finish the trip, or resolve the scan first.');
            $lockedTrip->update(['status' => 'cancelled', 'cancel_reason' => $data['reason']]);
            $lockedTrip->driver()->where('status', 'on_trip')->update(['status' => 'available']);
            $lockedTrip->vehicle()->where('status', 'on_trip')->update(['status' => 'available']);
        }, 3);

        return response()->json(['trip' => $this->tripCard($trip->fresh(['origin', 'destination', 'driver.user', 'vehicle', 'orders'])) + ['can_cancel' => true], 'message' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' is cancelled. The driver, vehicle and parcels are released.']);
    }

    public function departTrip(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        $hub = $this->facility($request);
        $scopeIds = $hub->children()->where('kind', 'station_hub')->pluck('id')->push($hub->id);
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && $scopeIds->contains($trip->origin_facility_id), 404);
        $trip = $workflow->depart($trip, $request->user());

        return response()->json(['trip' => $this->tripCard($trip) + ['can_cancel' => false, 'can_depart' => true,
            'can_arrive' => $scopeIds->contains($trip->destination_facility_id)], 'message' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' is on its way to '.($trip->destination?->name ?? 'its destination').'.']);
    }

    public function arriveTrip(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        $hub = $this->facility($request);
        $scopeIds = $hub->children()->where('kind', 'station_hub')->pluck('id')->push($hub->id);
        abort_unless((int) $trip->logistics_company_id === (int) $hub->logistics_company_id
            && $scopeIds->contains($trip->destination_facility_id), 404);
        $trip = $workflow->arrive($trip);

        return response()->json(['trip' => $this->tripCard($trip) + ['can_cancel' => false, 'can_depart' => false,
            'can_arrive' => true], 'message' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' arrived at '.($trip->destination?->name ?? 'its destination').'. The driver and vehicle are available again.']);
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

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => $vehicle->vehicle_code.' is added. It is available for trips.'], 201);
    }

    public function updateVehicle(Request $request, LogisticsCompanyVehicle $vehicle): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $vehicle->province_hub_id === (int) $hub->id && (int) $vehicle->logistics_company_id === (int) $hub->logistics_company_id, 404);
        abort_if($vehicle->status === 'on_trip', 409, $vehicle->vehicle_code.' is on a trip, so it can’t be edited. Edit it after the trip ends.');
        $data = $this->validateVehicle($request, $hub, $vehicle);
        $vehicle->update([
            'plate_number' => $this->normalizePlate($data['plate']), 'vehicle_type' => $data['type'],
            'capacity_kg' => (int) round((float) $data['capacity'] * 1000),
        ]);

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => $vehicle->vehicle_code.' is updated. Changes apply to future trips.']);
    }

    public function updateVehicleStatus(Request $request, LogisticsCompanyVehicle $vehicle): JsonResponse
    {
        $hub = $this->facility($request);
        abort_unless((int) $vehicle->province_hub_id === (int) $hub->id && (int) $vehicle->logistics_company_id === (int) $hub->logistics_company_id, 404);
        abort_if($vehicle->status === 'on_trip', 409, $vehicle->vehicle_code.' is on a trip, so its status can’t change. Change it after the trip ends.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'maintenance_note' => ['required_if:status,maintenance', 'nullable', 'string', 'min:5', 'max:500'],
        ], [
            'status.required' => 'Status is missing. Reload the page and try again.',
            'status.in' => 'That status isn’t available for a vehicle. Reload the page and try again.',
            'maintenance_note.required_if' => 'Maintenance reason is empty. Say what the vehicle is in maintenance for.',
            'maintenance_note.min' => 'Maintenance reason is too short. Write at least 5 characters so the team knows what the vehicle needs.',
            'maintenance_note.max' => 'Maintenance reason is too long. Keep it to 500 characters or fewer.',
        ]);
        $wasInMaintenance = $vehicle->status === 'maintenance';
        $vehicle->update([
            'status' => $data['status'],
            'maintenance_note' => $data['status'] === 'maintenance' ? $data['maintenance_note'] : null,
            'maintenance_at' => $data['status'] === 'maintenance' ? ($vehicle->maintenance_at ?? now()) : null,
        ]);

        $code = $vehicle->vehicle_code;
        $message = match ($data['status']) {
            'maintenance' => $code.' is in maintenance. It won’t be assigned to trips until it is back in service.',
            'inactive' => $code.' is deactivated. Activate it to assign it to trips again.',
            default => $wasInMaintenance ? $code.' is back in service and available for trips.' : $code.' is active and available for trips.',
        };

        return response()->json(['vehicle' => $this->vehicleCard($vehicle->fresh()), 'message' => $message]);
    }

    public function reviewRider(Request $request, \App\Models\Profiles\CourierDetail $courierDetail): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $courierDetail->station_hub_id === (int) $station->id, 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:500']], [
            'decision.required' => 'Choose Approve rider or Reject application.',
            'decision.in' => 'Choose Approve rider or Reject application.',
            'reason.required_if' => 'Reason is empty. Write at least 5 characters so the applicant knows what to fix.',
            'reason.min' => 'Reason is too short. Write at least 5 characters so the applicant knows what to fix.',
            'reason.max' => 'Reason is longer than 500 characters. Shorten it.',
        ]);

        DB::transaction(function () use ($courierDetail, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
            abort_unless($user->role === 'courier' && $user->status === 'pending', 409, 'This application was already reviewed. Reload the page to see its current status.');
            $user->update($data['decision'] === 'approve'
                ? ['status' => 'approved', 'account_status' => 'active']
                : ['status' => 'disapproved', 'rejection_reason' => $data['reason'], 'rejection_notes' => null]);
        }, 3);

        $name = $courierDetail->user?->name ?? 'The rider';

        return response()->json(['status' => $data['decision'] === 'approve' ? 'active' : 'rejected', 'message' => $data['decision'] === 'approve'
            ? $name.' is approved. They can now be assigned tasks.'
            : $name.'’s application is rejected. They see your reason in the Rider app.']);
    }

    public function updateRiderStatus(Request $request, \App\Models\Profiles\CourierDetail $courierDetail): JsonResponse
    {
        $station = $this->facility($request);
        abort_unless((int) $courierDetail->station_hub_id === (int) $station->id, 404);
        $data = $request->validate(['active' => ['required', 'boolean']], [
            'active.required' => 'Choose Activate rider or Deactivate rider.',
            'active.boolean' => 'Choose Activate rider or Deactivate rider.',
        ]);
        if (! $data['active']) {
            abort_if($courierDetail->tasks()->whereIn('status', ['assigned', 'in_progress'])->exists(), 409, 'This rider still has open parcels. Reassign them on Task assignment, then deactivate.');
        }
        $user = User::query()->lockForUpdate()->findOrFail($courierDetail->user_id);
        abort_unless($user->role === 'courier' && $user->status === 'approved', 409, 'Only approved riders can be activated or deactivated. Approve the application first.');
        $user->update(['account_status' => $data['active'] ? 'active' : 'deactivated']);
        $name = $user->name ?: 'The rider';

        return response()->json(['status' => $data['active'] ? 'active' : 'inactive', 'message' => $data['active']
            ? $name.' is active again. They can be assigned tasks.'
            : $name.' is deactivated. They won’t get new parcels.']);
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
        ], [
            'code.required' => 'Vehicle code is empty. Enter a code, like TRK-010.',
            'code.max' => 'Vehicle code is too long. Use 40 characters or fewer.',
            'code.regex' => 'Vehicle code has symbols that aren’t allowed. Use only letters, numbers and dashes.',
            'code.unique' => 'This vehicle code is already used by another vehicle. Enter a different code.',
            'plate.required' => 'Plate number is empty. Enter the plate number, like ABC 1234.',
            'plate.regex' => 'Plate number isn’t in the right format. Use three letters and three or four digits, like ABC 1234.',
            'plate.unique' => 'This plate number is already registered to another vehicle. Check the plate number.',
            'type.required' => 'Vehicle type is not chosen. Pick one from the list.',
            'type.in' => 'That vehicle type isn’t available. Pick one from the list.',
            'capacity.required' => 'Capacity is empty. Enter the capacity in tons, from 0.5 to 40.',
            'capacity.numeric' => 'Capacity must be a number. Enter the capacity in tons, like 8.',
            'capacity.min' => 'Capacity is too low. Enter at least 0.5 tons.',
            'capacity.max' => 'Capacity is too high. Enter 40 tons or less.',
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
            'vehicle' => $driver->vehicle_type ?: 'Not specified',
            'plate' => $driver->plate_number ?: '—',
            'license' => $driver->license_number, 'category' => $driver->license_category,
            'expiry' => $driver->license_expires_at?->toDateString(),
            // The Driver app registers with the licence alone, so the stored
            // valid ID is usually that same file. Keep a separate link only
            // when a distinct identity document was uploaded.
            'has_valid_id' => $driver->valid_id_path && $driver->valid_id_path !== $driver->drivers_license_path,
            'status' => $driver->status, 'trip' => $current ? 'TRP-'.str_pad((string) $current->id, 5, '0', STR_PAD_LEFT) : null,
            'rejected' => $driver->user?->status === 'disapproved',
            'done' => $driver->trips->where('status', 'completed')->count(),
            'applied' => $driver->created_at?->toDateString(),
            // Who reviews the application. Only a station-only driver is
            // approved by the Station Hub; everyone else by the Province Hub.
            'apply_to' => $driver->apply_to ?: TransportDriverDetail::APPLY_PROVINCE,
            'apply_label' => $driver->applyLabel(),
            'reviewed_by_kind' => $driver->worksStationToStation() ? 'station_hub' : 'province_hub',
            'station' => $driver->stationHub?->name,
            'station_id' => $driver->stationHub?->id,
            'station_area' => $driver->stationHub ? trim(($driver->stationHub->municipality ?? '').' '.($driver->stationHub->province ?? '')) : null,
            'reviewed_by' => $driver->approvedBy?->name,
            'reviewed_at' => $driver->approved_at?->toDateString(),
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

    /** The form accepts "0917 123 4567" and "+63 917 123 4567"; the database keeps 09XXXXXXXXX. */
    private function normalizeHubPhone(Request $request): void
    {
        $phone = preg_replace('/[\s-]/', '', (string) $request->input('phone'));
        if (str_starts_with($phone, '+639')) {
            $phone = '0'.substr($phone, 3);
        }
        $request->merge(['phone' => $phone]);
    }

    /** Validation messages for the Province Hub form: what is wrong, then how to fix it. */
    private function hubMessages(): array
    {
        return [
            'province.required' => 'Choose a province for this hub.',
            'name.required' => 'Enter a hub name.',
            'name.max' => 'The hub name is too long. Use 80 characters or fewer.',
            'address.required' => 'Enter the hub address.',
            'address.min' => 'Add the unit, street, barangay and city so drivers can find the hub.',
            'address.max' => 'The address is too long. Use 200 characters or fewer.',
            'manager.required' => 'Enter the manager’s full name.',
            'manager.max' => 'The manager name is too long. Use 80 characters or fewer.',
            'email.required' => 'Enter the manager’s email.',
            'email.email' => 'Enter an email like name@company.com.',
            'email.max' => 'The email is too long. Use a shorter address.',
            'email.unique' => 'This email is already in use. Enter a different email.',
            'phone.required' => 'Enter the manager’s mobile number.',
            'phone.regex' => 'Enter a mobile number like 0917 123 4567.',
        ];
    }

    private function ensureNoOpenWork(LogisticsFacility $facility): void
    {
        $orderUsesFacility = \App\Models\Ecommerce\Order::query()->whereNotIn('status', ['cancelled', 'returned', 'completed', 'delivered'])
            ->where(fn ($q) => $q->where('origin_station_id', $facility->id)->orWhere('destination_station_id', $facility->id)
                ->orWhere('origin_province_hub_id', $facility->id)->orWhere('destination_province_hub_id', $facility->id)
                ->orWhereHas('facilityStops', fn ($stops) => $stops->where('facility_id', $facility->id)))->exists();
        $tripUsesFacility = LogisticsTransportTrip::query()->whereIn('status', ['scheduled', 'in_transit'])
            ->where(fn ($q) => $q->where('origin_facility_id', $facility->id)->orWhere('destination_facility_id', $facility->id))->exists();
        abort_if($orderUsesFacility || $tripUsesFacility, 409, 'Open parcels or trips still use this facility. Finish or move them, then deactivate it.');
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
