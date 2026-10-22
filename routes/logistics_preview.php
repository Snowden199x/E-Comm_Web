<?php

/*
 * PREVIEW ONLY - hardcoded data so the team can click through the new screens.
 * Delete this whole file (and its require line in routes/web.php) once the real
 * controllers exist. Open: /logistics-preview/company
 *
 * Every page in the spec has a named route here so config/logistics_nav.php can
 * list it. Pages that are not built yet render logistics.preview.coming-next.
 * To build one: swap its view() for the real view. Keep the route name.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::prefix('logistics-preview')->name('lgp.')->group(function () {

    // ---- Company portal ---------------------------------------------------
    // One hardcoded company network shared by Dashboard, Province Hubs, Hub details and Network Overview (routes/logistics_preview_data.php).
    $net = require __DIR__.'/logistics_preview_data.php';

    // Dashboard (spec section 8, Company Admin). Totals come from the shared network data so every screen agrees.
    // Backend later: counts scoped to the signed-in company; recent activity from the order and trip event logs.
    Route::get('/company', fn () => view('logistics.company.dashboard', [
        'hubs' => $net['hubs'],
        'drivers' => collect($net['hubs'])->sum('drivers'),
        'vehicles' => collect($net['hubs'])->sum('vehicles'),
        'parcelsInTransit' => collect($net['hubs'])->flatMap(fn ($h) => $h['trips'])->where(6, 'In transit')->sum(5),
        'deliveredToday' => 312,
        'delayedParcels' => 3,
        'failedParcels' => 2,
        'delayedRule' => 'A parcel is delayed when it has not moved on for 24 hours at a facility, or when its trip is more than 2 hours past the expected arrival.',
        'activity' => [
            ['at' => '2026-10-21T10:15', 'text' => 'Delivery failed for VND-10473 at Lucban Station: the address could not be found.', 'track' => 'VND-10473'],
            ['at' => '2026-10-21T08:45', 'text' => 'Trip HT-2042 from Quezon Hub to Laguna Hub is running late with 210 parcels.', 'hub' => 'quezon'],
            ['at' => '2026-10-21T08:20', 'text' => 'HT-2041 left Santa Cruz Station for Laguna Hub with 85 parcels.', 'hub' => 'laguna'],
            ['at' => '2026-10-20T15:40', 'text' => 'Delivery failed for VND-10451 at Santa Cruz Station: the buyer was not home.', 'track' => 'VND-10451'],
            ['at' => '2026-10-20T10:35', 'text' => 'HT-2044 arrived at Laguna Hub from Pagsanjan Station with 38 parcels.', 'hub' => 'laguna'],
            ['at' => '2026-10-20T09:00', 'text' => 'Los Banos Station was added to Laguna Hub. Its manager has not signed in yet.', 'hub' => 'laguna'],
        ],
    ]))->name('company');


    Route::get('/company/hubs', fn () => view('logistics.company.hubs', [
        'provinces' => $net['provinces'],
        'hubs' => collect($net['hubs'])->map(fn ($h, $slug) => ['slug' => $slug, 'stations' => count($h['stations']), 'trips' => count($h['trips'])]
            + collect($h)->except(['stations', 'trips', 'fleet', 'perf'])->all())->values()->all(),
    ]))->name('company.hubs');

    Route::get('/company/hubs/{hub}', function (string $hub) use ($net) {
        abort_unless(isset($net['hubs'][$hub]), 404);

        return view('logistics.company.hub-show', ['slug' => $hub, 'hub' => $net['hubs'][$hub]]);
    })->where('hub', '[a-z0-9-]+')->name('company.hubs.show');

    Route::get('/company/managers', fn () => view('logistics.company.managers', [
        'hubNames' => ['Laguna Hub', 'Santa Cruz Station', 'Pagsanjan Station', 'Los Banos Station', 'Quezon Hub', 'Lucena Station'],
        'managers' => [
            ['name' => 'Ana Reyes',      'email' => 'ana@jnt-demo.ph',    'hub' => 'Laguna Hub',         'role' => 'Hub manager',        'last_login' => 'Today, 8:12 AM',  'status' => 'Active'],
            ['name' => 'Mark Dela Cruz', 'email' => 'mark@jnt-demo.ph',   'hub' => 'Santa Cruz Station', 'role' => 'Hub manager',        'last_login' => 'Today, 7:40 AM',  'status' => 'Active'],
            ['name' => 'Rica Flores',    'email' => 'rica@jnt-demo.ph',   'hub' => 'Santa Cruz Station', 'role' => 'Hub staff (sorter)', 'last_login' => 'Yesterday',       'status' => 'Active'],
            ['name' => 'Liza Santos',    'email' => 'liza@jnt-demo.ph',   'hub' => 'Pagsanjan Station',  'role' => 'Hub manager',        'last_login' => 'Oct 5',           'status' => 'Active'],
            ['name' => 'Carlo Ramos',    'email' => 'carlo@jnt-demo.ph',  'hub' => 'Los Banos Station',  'role' => 'Hub manager',        'last_login' => 'Never',           'status' => 'Invited'],
        ],
    ]))->name('company.managers');

    // ---- Station Hub dashboard (spec section 8, Station Hub > Dashboard) -------
    // The preview Station Hub is JNT Santa Cruz Station under Laguna Hub, so counts and riders come from the shared network data
    // and agree with Province Hub > Station Hubs (96 parcels on hand, 9 riders).
    // Backend later: counts scoped to the signed-in Station Hub. Workload comes from open rider_tasks (pickup and delivery) per rider.

    // PREVIEW: [open pickup tasks, open delivery tasks] per Santa Cruz rider. Shared by the Station Dashboard and Pickup Requests so both agree.
    // Delivery tasks add up to the 35 parcels out for delivery.
    $stationTasks = [
        'Jun Bautista' => [5, 9], 'Paolo Garcia' => [4, 8], 'Nina Castillo' => [3, 6],
        'Rico Dimaano' => [4, 4], 'Tomas Villar' => [6, 5], 'Gino Alonzo' => [3, 3],
    ];

    Route::get('/hub/station', function () use ($net, $stationTasks) {
        $detail = $net['station_details']['santa-cruz'];
        $station = collect($net['hubs']['laguna']['stations'])->first(fn ($s) => $s['municipality'] === 'Santa Cruz');

        $riders = collect($detail['riders_list'])->map(function ($r) use ($stationTasks) {
            [$pickup, $delivery] = $stationTasks[$r['name']] ?? [0, 0];

            return $r + ['pickup' => $pickup, 'delivery' => $delivery];
        })->all();

        return view('logistics.station.dashboard', [
            'station' => $station,
            'stage' => $detail['stage'],
            'riders' => $riders,
            // PREVIEW: pending pickup requests (not yet assigned to a rider), today's counts and the one failed delivery.
            'pendingPickups' => 3,
            'receivedToday' => 48,
            'deliveredToday' => 37,
            'failed' => [['code' => 'VND-10451', 'area' => 'Brgy. Patimbao']],
            // The scheduled Province Hub transfer on its way here (HT-2047). Only the Province Hub creates trips.
            'incoming' => ['id' => 'HT-2047', 'from' => 'Laguna Hub', 'parcels' => 52, 'scheduled' => '2026-10-21T14:00'],
        ]);
    })->name('hub.station');

    // Province Hub dashboard (spec section 8, Province Hub > Dashboard). The preview hub is Laguna Hub, so every list comes from the shared network data.
    // Backend later: counts scoped to the signed-in Province Hub. "Delayed" uses the same rule as the company dashboard and the reports.
    Route::get('/hub/province', function () use ($net) {
        $trips = collect($net['laguna_trips']);
        $active = $trips->whereIn('status', ['scheduled', 'in_transit']);

        return view('logistics.province.dashboard', [
            'hub' => $net['hubs']['laguna'],
            'drivers' => collect($net['laguna_drivers']),
            'vehicles' => collect($net['laguna_vehicles']),
            'activeTrips' => $active->sortBy([['status', 'asc'], ['scheduled', 'asc']])->values()->all(),
            // Incoming = active trips that end at this hub. Outgoing = active trips that start here.
            'incoming' => $active->where('destination', 'Laguna Hub')->values()->all(),
            'outgoing' => $active->where('origin', 'Laguna Hub')->values()->all(),
            // PREVIEW: parcels at the hub by stage. Backend later: orders at this facility grouped by status.
            'awaitingSorting' => 64,
            'waitingForTrip' => collect($net['ready_parcels'])->filter(fn ($rows, $k) => str_starts_with($k, 'to:'))->sum(fn ($rows) => count($rows)),
            'delayed' => [
                ['id' => 'HT-2041', 'text' => 'Santa Cruz Station to Laguna Hub left at 8:20 AM and is more than 2 hours past its expected arrival with 85 parcels.'],
            ],
            'delayedRule' => 'A transfer is delayed when its trip is more than 2 hours past the expected arrival.',
        ]);
    })->name('hub.province');

    Route::get('/hub/linehaul', fn () => view('logistics.hub.linehaul', [
        'truckRiders' => ['Ramon Aquino (Truck)', 'Eddie Lim (Van)'],
        'outbound' => [
            ['to' => 'Quezon Hub', 'via' => 'Laguna to Quezon', 'count' => 34],
            ['to' => 'Batangas Hub', 'via' => 'Laguna to Batangas', 'count' => 24],
        ],
        'inbound' => [
            ['from' => 'Quezon Hub',   'count' => 42, 'rider' => 'Ben Torres',  'eta' => 'Arriving in 25 min', 'tone' => 'blue'],
            ['from' => 'Cavite Hub',   'count' => 32, 'rider' => 'Dan Navarro', 'eta' => 'Arrived',            'tone' => 'green'],
        ],
    ]))->name('hub.linehaul');

    // ======================================================================
    // Pages from the Logistics Hub Restructure spec (section 8 and 8A).
    // Real view = built. coming-next = planned, shows what the page will hold.
    // ======================================================================
    $soon = fn (string $role, string $title, string $icon, string $spec, string $text, array $shows)
        => view('logistics.preview.coming-next', compact('role', 'title', 'icon', 'spec', 'text', 'shows'));


    // ---- Company --------------------------------------------------------
    Route::get('/company/network', fn () => view('logistics.company.network', ['hubs' => $net['hubs']]))->name('company.network');
    $trackingData = require __DIR__.'/logistics_preview_tracking.php';
    Route::get('/company/tracking', fn () => view('logistics.shared.tracking', [
        'role' => 'company',
        'parcels' => $trackingData['parcels'],
        'routeLabels' => $trackingData['route_labels'],
        'delayedRule' => $trackingData['delayed_rule'],
    ]))->name('company.tracking');

    // ---- Province Hub ---------------------------------------------------
    // The preview Province Hub is Laguna Hub (see the shell identity), so its stations come from the shared network data.
    Route::get('/province/stations', fn () => view('logistics.province.stations', [
        'hub' => $net['hubs']['laguna'],
        'showUrl' => route('lgp.province.stations.show', '__station__'),
        'municipalities' => $net['laguna_municipalities'],
        'stations' => collect($net['hubs']['laguna']['stations'])
            ->map(fn ($s) => $s + ['slug' => Str::slug($s['municipality']), 'open' => $s['parcels']])->all(),
    ]))->name('province.stations');

    Route::get('/province/stations/{station}', function (string $station) use ($net) {
        $hub = $net['hubs']['laguna'];
        $row = collect($hub['stations'])->first(fn ($s) => Str::slug($s['municipality']) === $station);
        abort_unless($row && isset($net['station_details'][$station]), 404);

        return view('logistics.province.station-show', ['hub' => $hub, 'station' => $row + $net['station_details'][$station]]);
    })->where('station', '[a-z0-9-]+')->name('province.stations.show');
    Route::get('/province/drivers', fn () => view('logistics.province.drivers', [
        'hub' => $net['hubs']['laguna'],
        'drivers' => collect($net['laguna_drivers'])->map(fn ($d, $i) => $d + ['id' => $i + 1])->all(),
        'today' => now('Asia/Manila')->toDateString(),
    ]))->name('province.drivers');
    Route::get('/province/vehicles', fn () => view('logistics.province.vehicles', [
        'hub' => $net['hubs']['laguna'],
        'vehicles' => collect($net['laguna_vehicles'])->map(fn ($v, $i) => $v + ['id' => $i + 1])->all(),
        'types' => $net['vehicle_types'],
        'today' => now('Asia/Manila')->toDateString(),
    ]))->name('province.vehicles');
    // Parcel Operations (spec section 8): parcels at this hub by stage. Backend later: scoped to the signed-in Province Hub.
    Route::get('/province/parcels', fn () => view('logistics.province.parcels', [
        'hub' => $net['hubs']['laguna'],
        'parcels' => $net['laguna_parcels'],
        'tripsUrl' => route('lgp.province.trips'),
    ]))->name('province.parcels');
    Route::get('/province/trips', fn () => view('logistics.province.trips', [
        'hub' => $net['hubs']['laguna'],
        'trips' => $net['laguna_trips'],
        'createUrl' => route('lgp.province.trips.create'),
        'now' => now('Asia/Manila')->format('Y-m-d\TH:i'),
    ]))->name('province.trips');

    Route::get('/province/trips/create', function () use ($net) {
        $hub = $net['hubs']['laguna'];
        $maxId = collect($net['laguna_trips'])->map(fn ($t) => (int) substr($t['id'], 3))->max();

        return view('logistics.province.trip-create', [
            'hub' => $hub,
            // Only Station Hubs of this Province Hub that are active, and other Province Hubs that are active.
            'stations' => collect($hub['stations'])->where('status', 'Active')
                ->map(fn ($s) => ['name' => $s['name'], 'slug' => Str::slug($s['municipality'])])->values()->all(),
            'otherHubs' => collect($net['hubs'])->except('laguna')->where('status', 'Active')
                ->map(fn ($h, $slug) => ['name' => $h['name'], 'slug' => $slug])->values()->all(),
            // Only available drivers and vehicles can be assigned to a new trip.
            'drivers' => collect($net['laguna_drivers'])->where('status', 'available')
                ->map(fn ($d) => ['name' => $d['name'], 'expiry' => $d['expiry'], 'category' => $d['category']])->values()->all(),
            'vehicles' => collect($net['laguna_vehicles'])->where('status', 'available')
                ->map(fn ($v) => ['code' => $v['code'], 'type' => $v['type'], 'capacity' => $v['capacity']])->values()->all(),
            'parcels' => $net['ready_parcels'],
            'nextId' => 'HT-' . ($maxId + 1),
            'now' => now('Asia/Manila')->format('Y-m-d\TH:i'),
            'defaultDeparture' => now('Asia/Manila')->addHour()->startOfHour()->format('Y-m-d\TH:i'),
            'listUrl' => route('lgp.province.trips'),
        ]);
    })->name('province.trips.create');

    // Parcel Tracking (spec section 8A), scoped to this Province Hub: only parcels whose route passes through Laguna Hub or one of its stations.
    // Backend later: authorize on the server and return nothing for a parcel outside the signed-in hub's scope (spec 3.1).
    Route::get('/province/tracking', function () use ($net, $trackingData) {
        $hub = $net['hubs']['laguna'];
        $inScope = array_merge([$hub['name']], array_column($hub['stations'], 'name'));

        return view('logistics.shared.tracking', [
            'role' => 'province',
            'parcels' => collect($trackingData['parcels'])->filter(fn ($p) => array_intersect($p['stops'], $inScope))->values()->all(),
            'routeLabels' => $trackingData['route_labels'],
            'delayedRule' => $trackingData['delayed_rule'],
            'intro' => 'Find a parcel that passes through '.$hub['name'].' or one of its Station Hubs and follow its journey, trip by trip.',
            'scopeNote' => 'You only see parcels whose route goes through '.$hub['name'].' or its Station Hubs. Other parcels are not found here.',
        ]);
    })->name('province.tracking');
    // Reports (spec section 8A, Province Hub row): the Laguna slice of the company reports. Backend later: the same aggregates filtered to the signed-in hub.
    // $reportData is defined further down in this file, so it is read here with its own require.
    Route::get('/province/reports', function () {
        $data = require __DIR__.'/logistics_preview_reports.php';

        return view('logistics.shared.reports', [
            'role' => 'province',
            'reports' => $data['province_laguna'],
            'baseDays' => $data['days'],
            'intro' => 'See how Laguna Hub, its Station Hubs, drivers and vehicles are doing.',
        ]);
    })->name('province.reports');
    // Account still reuses the existing view for now.
    Route::get('/province/account', fn () => view('logistics.shared.account', ['role' => 'province']))->name('province.account');

    // ---- Station Hub ----------------------------------------------------
    // Pickup Requests (spec section 8): seller pickups for this station. The Station Hub assigns a pickup task to a rider (spec 6A).
    // Open pickup tasks per rider = assigned + picked up, and match the Station Dashboard (25 open, 3 still need a rider).
    // Backend later: orders in ready_for_pickup / rider_assigned / picked_up for the signed-in Station Hub, joined to rider_tasks (type = pickup).
    $stationPickups = require __DIR__.'/logistics_preview_station.php';
    Route::get('/station/pickups', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);

        return view('logistics.station.pickups', ['requests' => $data['requests'], 'riders' => $data['riders']]);
    })->name('station.pickups');

    // Incoming Parcels (spec section 8): parcels arriving at this station. The Station Hub only RECEIVES and scans them (spec 6B, 7).
    // Two sources: seller parcels a rider has picked up (the 7 "Picked up" rows on Pickup Requests) and the Province Hub transfer HT-2047
    // (the same 52 parcels shown loaded on Province Hub > Parcel Operations). Received today (48) agrees with the Station Dashboard.
    // Backend later: orders whose next stop is this Station Hub (rider pickups in picked_up, transport_trip_orders for trips ending here),
    // and a receive scan per parcel that records the arrival (FacilityScanWorkflow). A trip must be in_transit before its parcels can be received.
    Route::get('/station/incoming', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);
        $now = $data['now'];
        $riders = collect($data['riders'])->keyBy('id');
        $trip = collect($net['laguna_trips'])->firstWhere('id', 'HT-2047');

        $fromRiders = collect($data['requests'])->where('status', 'picked_up')->map(fn ($r) => [
            'code' => $r['code'], 'kind' => 'rider', 'stage' => 'with_rider', 'seller' => $r['seller'], 'street' => $r['street'],
            'barangay' => $r['barangay'], 'rider' => $riders[$r['rider_id']]['name'], 'vehicle' => $riders[$r['rider_id']]['vehicle'],
            'time' => $r['time'], 'ago' => $r['ago'],
        ])->values();

        $fromHub = collect($net['laguna_parcels'])->where('trip', 'HT-2047')->map(fn ($p) => [
            'code' => $p['code'], 'kind' => 'transfer', 'stage' => 'scheduled', 'area' => $p['area'], 'from' => $p['from'], 'trip' => $p['trip'],
        ])->values();

        return view('logistics.station.incoming', [
            'parcels' => $fromRiders->concat($fromHub)->concat($data['received'])->values()->all(),
            'trip' => $trip,
            'from' => 'Laguna Hub',
        ]);
    })->name('station.incoming');
    // Task Assignment (spec sections 6A and 8): every task that still needs a rider, pickup and delivery together. Only the Station Hub assigns tasks.
    // PREVIEW: pickups are the 3 unassigned requests; delivery tasks are the 31 parcels ready for delivery (same list as Delivery Queue).
    Route::get('/station/tasks', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);

        $pickups = collect($data['requests'])->where('status', 'unassigned')->map(fn ($r) => [
            'id' => $r['code'], 'type' => 'pickup', 'code' => $r['code'], 'party' => $r['seller'], 'place' => $r['street'].', Brgy. '.$r['barangay'],
            'barangay' => $r['barangay'], 'since' => 'Requested '.$r['time'], 'ago' => $r['ago'],
        ]);
        $deliveries = collect($data['ready'])->map(fn ($p) => [
            'id' => $p['code'], 'type' => 'delivery', 'code' => $p['code'], 'party' => $p['seller'], 'place' => 'Brgy. '.$p['barangay'],
            'barangay' => $p['barangay'], 'since' => $p['since'], 'ago' => $p['ago'],
        ]);

        return view('logistics.station.tasks', [
            'tasks' => $pickups->merge($deliveries)->values()->all(),
            'riders' => $data['riders'],
            'cap' => 16,
        ]);
    })->name('station.tasks');
    // Delivery Queue (spec section 8): parcels ready for delivery grouped by barangay, plus the parcels with riders and failed deliveries the
    // dashboard links to. Failed deliveries are a Station Hub decision: retry (back to the queue) or return to the seller.
    Route::get('/station/delivery-queue', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);

        return view('logistics.station.delivery-queue', [
            'ready' => $data['ready'],
            'withRiders' => $data['withRiders'],
            'failed' => $data['failed'],
            'riders' => $data['riders'],
            'cap' => 16,
        ]);
    })->name('station.delivery-queue');
    // Sorting (spec section 8, 7): parcels this station has received. Each one's next step is worked out from its order route, so staff do not
    // pick a destination hub here. Local parcels go to the Delivery Queue; the rest wait for a station transfer that the Province Hub schedules.
    // Same 48 received parcels as Incoming Parcels (18 awaiting sorting, 30 sorted). Backend later: orders at this facility in received status.
    Route::get('/station/sorting', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);

        return view('logistics.station.sorting', ['parcels' => $data['received'], 'hub' => 'Laguna Hub']);
    })->name('station.sorting');
    // Outgoing Transfers (spec section 8): parcels waiting for a station transfer to the Province Hub, and the trips that start at this station.
    // Read only: only the Province Hub creates a trip and picks its driver and vehicle. Backend later: orders here whose next route stop is the Province Hub,
    // and transport_trips with this station as origin.
    Route::get('/station/outgoing', function () use ($net, $stationTasks, $stationPickups) {
        $data = $stationPickups($net, $stationTasks);

        return view('logistics.station.outgoing', ['waiting' => $data['transferWaiting'], 'trips' => $data['outTrips'], 'hub' => 'Laguna Hub']);
    })->name('station.outgoing');
    // Parcel Tracking (spec section 8A), scoped to this station: only parcels whose route passes through Santa Cruz Station. The journey of a parcel in scope
    // still shows every leg and trip, including the ones before it reached or after it left this station.
    // Backend later: authorize on the server and return nothing for a parcel outside the signed-in station's scope (spec 3.1).
    Route::get('/station/tracking', function () use ($trackingData) {
        $station = 'Santa Cruz Station';

        return view('logistics.shared.tracking', [
            'role' => 'station',
            'parcels' => collect($trackingData['parcels'])->filter(fn ($p) => in_array($station, $p['stops'], true))->values()->all(),
            'routeLabels' => $trackingData['route_labels'],
            'delayedRule' => $trackingData['delayed_rule'],
            'intro' => 'Find a parcel that passes through '.$station.' and follow its journey, trip by trip.',
            'scopeNote' => 'You only see parcels whose route goes through '.$station.'. Other parcels are not found here.',
        ]);
    })->name('station.tracking');
    Route::get('/station/riders', fn () => view('logistics.hub.riders', ['role' => 'station']))->name('station.riders');
    Route::get('/station/reports', fn () => view('logistics.hub.reports', ['role' => 'station']))->name('station.reports');
    Route::get('/station/account', fn () => view('logistics.shared.account', ['role' => 'station']))->name('station.account');

    // ======================================================================
    // LEGACY routes. Not in the sidebar any more, kept so the old preview
    // views that still link to them keep working. Delete each one together
    // with the view that uses it.
    //   hub.linehaul, hub.page (incoming, sorting, assignments, monitoring), company.managers
    // ======================================================================
    // ---- Remaining company pages ------------------------------------------
    $reportData = require __DIR__.'/logistics_preview_reports.php';
    Route::get('/company/reports', fn () => view('logistics.shared.reports', [
        'role' => 'company',
        'reports' => $reportData['reports'],
        'baseDays' => $reportData['days'],
    ]))->name('company.reports');
    // Messages: email-style conversations between the Company Admin and one hub each.
    $messageData = require __DIR__.'/logistics_preview_messages.php';
    Route::get('/company/messages', function () use ($messageData, $net) {
        $refUrl = fn (array $r) => match ($r[0]) {
            'parcel' => route('lgp.company.tracking', ['q' => $r[1]]),
            'hub' => route('lgp.company.hubs.show', collect($net['hubs'])->search(fn ($h) => $h['name'] === $r[1]) ?: 'laguna'),
            default => null,
        };
        $threads = collect($messageData['threads'])->map(fn ($t) => $t + ['refUrl' => $refUrl($t['ref'])])->all();
        // Everyone the company can write to: each active or setting-up Province Hub and its stations.
        $recipients = collect($net['hubs'])->where('status', '!=', 'Inactive')->flatMap(fn ($h) => array_merge(
            [['name' => $h['name'], 'kind' => 'Province Hub', 'contact' => $h['manager']]],
            collect($h['stations'])->map(fn ($s) => ['name' => $s['name'], 'kind' => 'Station Hub, '.$h['name'], 'contact' => $s['manager']])->all()
        ))->values()->all();

        return view('logistics.company.messages', ['threads' => $threads, 'recipients' => $recipients, 'topics' => $messageData['topics']]);
    })->name('company.messages');

    // Messages for the Province Hub and Station Hub: the same conversations as Company > Messages, read from the hub's side. A thread is always between the
    // Company Admin and one facility, so each hub sees only its own. Unread here means the Company Admin wrote last and the thread is still open.
    // Backend later: the same message_threads filtered to the signed-in facility, with the signed-in manager as the sender.
    $hubMessages = function (string $role, string $facility, string $me, string $trackingRoute) use ($messageData) {
        $companyName = collect($messageData['threads'])->flatMap(fn ($t) => $t['messages'])->firstWhere('from', 'company')['name'];
        $threads = collect($messageData['threads'])->where('hub', $facility)->map(function ($t) use ($trackingRoute) {
            $last = end($t['messages']);

            return array_merge($t, [
                'unread' => $last['from'] === 'company' && $t['status'] === 'open',
                'refUrl' => $t['ref'][0] === 'parcel' ? route($trackingRoute, ['q' => $t['ref'][1]]) : null,
            ]);
        })->values()->all();

        return ['role' => $role, 'threads' => $threads, 'topics' => $messageData['topics'], 'me' => $me, 'facility' => $facility, 'companyName' => $companyName];
    };
    Route::get('/province/messages', fn () => view('logistics.shared.messages', $hubMessages('province', 'Laguna Hub', 'Ana Reyes', 'lgp.province.tracking')))->name('province.messages');
    Route::get('/station/messages', fn () => view('logistics.shared.messages', $hubMessages('station', 'Santa Cruz Station', 'Mark Dela Cruz', 'lgp.station.tracking')))->name('station.messages');

    // Company Admin account: its own page because it has a head office and business documents (spec section 8).
    Route::get('/company/account', fn () => view('logistics.company.account', [
        'approved' => '2026-08-04',
        'company' => ['name' => 'JNT Express Philippines', 'email' => 'contact@jnt-demo.ph', 'phone' => '0917 123 4567', 'permit' => 'BP-2026-004418'],
        'office' => ['street' => 'Unit 2, JNT Corporate Center, Brgy. Don Jose', 'barangay' => 'Don Jose', 'city' => 'Santa Rosa', 'province' => 'Laguna', 'postal' => '4026'],
        'documents' => [
            ['id' => 1, 'name' => 'Business permit', 'file' => 'business-permit-2026.pdf', 'size' => '1.2 MB', 'uploaded' => '2026-08-02', 'status' => 'Verified'],
            ['id' => 2, 'name' => 'Valid ID of the owner', 'file' => 'owner-valid-id.jpg', 'size' => '640 KB', 'uploaded' => '2026-08-02', 'status' => 'Verified'],
        ],
        'manager' => ['name' => 'Maria Lourdes Tan', 'email' => 'maria.tan@jnt-demo.ph', 'phone' => '0917 555 0101', 'role' => 'Company administrator', 'last_login' => '2026-10-21T07:52'],
    ]))->name('company.account');

    // ---- Remaining hub pages (same views for station and province hub) ----
    // /logistics-preview/hub/{station|province}/{riders|incoming|sorting|assignments|monitoring|reports|account}
    Route::get('/hub/{role}/{page}', function (string $role, string $page) {
        $view = $page === 'account' ? 'logistics.shared.account' : 'logistics.hub.'.$page;
        abort_unless(view()->exists($view), 404);
        abort_if($page === 'assignments' && $role !== 'station', 404);
        return view($view, ['role' => $role]);
    })->where(['role' => 'station|province', 'page' => 'riders|incoming|sorting|assignments|monitoring|reports|account'])
      ->name('hub.page');
});