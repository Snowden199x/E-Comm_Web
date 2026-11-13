{{--
    Province Hub Dashboard. Read-only overview, fed by PortalController::provinceDashboard (nothing about the data changed).
    Look: the seller pastel lilac system, the same .lgd layer as the Company dashboard. Styles: resources/css/logistics/dashboard-theme.css
    plus province-dashboard-theme.css (loaded last). Behavior: resources/js/logistics/dashboard.js (one click on a row opens its page,
    spinner on buttons that open another page).
    Status colors: new = lilac, waiting = butter, in progress = sky, done = mint, delayed or failed = rose.
    Loading: shell skeleton (boot and page-to-page) plus a button spinner. Errors: top banner (<x-logistics.flash />) for messages sent
    back from other pages. Empty: nothing needs attention, no active trips, no Station Hubs, no drivers, no vehicles. No search or filters.
--}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $stations = collect($hub['stations']);
    $stationsSetup = $stations->where('status', 'Setup');

    // Fleet: only approved drivers count. Applications wait for review on the Drivers page.
    $pending = $drivers->where('status', 'pending');
    $expiredApplications = $pending->filter(fn ($d) => $d['expiry'] < now('Asia/Manila')->toDateString())->count();
    $driverCount = fn (string $s) => $drivers->where('status', $s)->count();
    $vehicleCount = fn (string $s) => $vehicles->where('status', $s)->count();
    $approvedDrivers = $drivers->whereIn('status', ['available', 'on_trip', 'off_duty'])->count();
    $activeVehicles = $vehicles->whereIn('status', ['available', 'on_trip', 'maintenance'])->count();

    // Trips by type, for the active ones only (scheduled or in transit).
    $trips = collect($activeTrips);
    $stationTrips = $trips->where('type', 'station_transfer')->count();
    $provinceTrips = $trips->where('type', 'inter_province')->count();
    $incomingParcels = collect($incoming)->sum('parcels');
    $outgoingParcels = collect($outgoing)->sum('parcels');
    $delayedCount = count($delayed);
    $delayedIds = collect($delayed)->pluck('id');

    $statusTone = ['Active' => 'mint', 'Setup' => 'butter', 'Inactive' => 'lilac'];
    $tripLabel = ['scheduled' => 'Scheduled', 'in_transit' => 'In transit'];
    $tripTone = ['scheduled' => 'butter', 'in_transit' => 'sky'];
    $typeLabel = ['station_transfer' => 'Station transfer', 'inter_province' => 'Inter-province'];
    $maxWaiting = max(1, $stations->max('parcels') ?? 1);

    // Needs attention: each item names the page where it is fixed. Worst first.
    $attention = [];
    foreach ($delayed as $d) {
        $attention[] = ['rank' => 0, 'tone' => 'rose', 'icon' => 'clock', 'title' => 'Transfer '.$d['id'].' is delayed',
            'text' => $d['text'], 'url' => route('lgp.province.trips'), 'cta' => 'Open trips'];
    }
    if ($waitingForTrip) {
        $attention[] = ['rank' => 1, 'tone' => 'butter', 'icon' => 'truck',
            'title' => $waitingForTrip.' '.Str::plural('parcel', $waitingForTrip).' '.($waitingForTrip === 1 ? 'is' : 'are').' waiting for a trip',
            'text' => 'Create a trip and assign an available driver and vehicle.',
            'url' => route('lgp.province.trips.create'), 'cta' => 'Create trip'];
    }
    if ($pending->count()) {
        $attention[] = ['rank' => 1, 'tone' => 'lilac', 'icon' => 'users',
            'title' => $pending->count().' driver '.Str::plural('application', $pending->count()).' to review',
            'text' => $expiredApplications
                ? $expiredApplications.' of them '.($expiredApplications === 1 ? 'has' : 'have').' an expired license.'
                : 'Approve or reject them so they can take trips.',
            'url' => route('lgp.province.drivers'), 'cta' => 'Review drivers'];
    }
    foreach ($stationsSetup as $s) {
        $attention[] = ['rank' => 1, 'tone' => 'butter', 'icon' => 'alert', 'title' => $s['name'].' is in setup',
            'text' => 'Its manager hasn’t signed in yet, so it can’t receive parcels.',
            'url' => route('lgp.province.stations.show', $s['id']), 'cta' => 'View station'];
    }
    if ($vehicleCount('maintenance')) {
        $attention[] = ['rank' => 2, 'tone' => 'sky', 'icon' => 'info',
            'title' => $vehicleCount('maintenance').' '.($vehicleCount('maintenance') === 1 ? 'vehicle is' : 'vehicles are').' in maintenance',
            'text' => 'Set '.($vehicleCount('maintenance') === 1 ? 'it' : 'them').' back to available before assigning '.($vehicleCount('maintenance') === 1 ? 'it' : 'them').' to a trip.',
            'url' => route('lgp.province.vehicles'), 'cta' => 'Open vehicles'];
    }
    $attention = collect($attention)->sortBy('rank')->values();
    $attentionShown = 3;

    // [label, value, icon, url, tone]
    $tiles = [
        ['Parcels at this hub', number_format($hub['parcels']), 'package', route('lgp.province.parcels'), 'lilac'],
        ['Station Hubs', number_format($stations->count()), 'building', route('lgp.province.stations'), 'lilac'],
        ['Active trips', number_format($trips->count()), 'calendar', route('lgp.province.trips'), 'sky'],
        ['Delayed transfers', number_format($delayedCount), 'clock', route('lgp.province.trips'), $delayedCount ? 'rose' : 'lilac'],
    ];

    // Parcels, in the order they move through this hub: [label, count, icon, tone, url, hint]
    $flow = [
        ['Incoming transfers', $incomingParcels, 'inbox', 'sky', route('lgp.province.parcels'),
            count($incoming) ? count($incoming).' '.(count($incoming) === 1 ? 'trip is' : 'trips are').' bringing parcels here.' : 'No trips are on their way here.'],
        ['Awaiting sorting', $awaitingSorting, 'layers', 'lilac', route('lgp.province.parcels'), 'Scan and sort each one to give it a next stop.'],
        ['Waiting for a trip', $waitingForTrip, 'package', 'butter', route('lgp.province.trips.create'), 'Sorted and ready to load. Create a trip to move them.'],
        ['Outgoing transfers', $outgoingParcels, 'truck', 'sky', route('lgp.province.trips'),
            count($outgoing) ? count($outgoing).' '.(count($outgoing) === 1 ? 'trip is' : 'trips are').' loaded or scheduled to leave.' : 'No trips are scheduled to leave.'],
    ];

    // Availability: [title, subtitle, url, button, empty title, empty text, empty button, rows [label, count, tone]]
    $fleet = [
        ['Transport drivers', $approvedDrivers.' approved', route('lgp.province.drivers'), 'Manage drivers',
            'No drivers yet', 'Drivers apply in the Driver app. Approve them here so they can take trips.', 'Open drivers', [
                ['Available', $driverCount('available'), 'mint'],
                ['On a trip', $driverCount('on_trip'), 'sky'],
                ['Off duty', $driverCount('off_duty'), 'lilac'],
            ]],
        ['Vehicles', $activeVehicles.' in service', route('lgp.province.vehicles'), 'Manage vehicles',
            'No vehicles yet', 'Add a vehicle so trips can be assigned to it.', 'Add vehicle', [
                ['Available', $vehicleCount('available'), 'mint'],
                ['On a trip', $vehicleCount('on_trip'), 'sky'],
                ['In maintenance', $vehicleCount('maintenance'), 'butter'],
            ]],
    ];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/dashboard-theme.css', 'resources/css/logistics/province-dashboard-theme.css', 'resources/js/logistics/dashboard.js'])
@endpush

<x-logistics.shell title="Province Hub Dashboard" role="province">
    <div class="lgd" data-lgd>

        <x-logistics.flash />

        <header class="lgd-head">
            <div>
                <h1>Dashboard</h1>
                <p>{{ $hub['name'] }} · {{ now()->format('l, F j') }}</p>
            </div>
            <div class="lgd-head__actions">
                <a href="{{ route('lgp.province.trips.create') }}" class="lgd-btn lgd-btn--primary">
                    <x-logistics.icon name="plus" :size="16" /> Create trip
                </a>
                <a href="{{ route('lgp.province.parcels') }}" class="lgd-btn lgd-btn--soft">
                    <x-logistics.icon name="inbox" :size="16" /> View parcels
                </a>
            </div>
        </header>

        {{-- Your hub --}}
        <section class="lgd-tiles lgd-tiles--4" aria-label="Your hub">
            @foreach ($tiles as [$label, $value, $icon, $url, $tone])
                <a href="{{ $url }}" class="lgd-tile @if ($tone === 'rose') is-problem @endif" aria-label="{{ $label }}: {{ $value }}. View details">
                    <span class="lgd-tile__top">
                        <span class="lgd-tile__icon lgd-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                        <span class="lgd-tile__go"><x-logistics.icon name="arrow-right" :size="16" /></span>
                    </span>
                    <span class="lgd-tile__label">{{ $label }}</span>
                    <strong class="lgd-tile__value">{{ $value }}</strong>
                </a>
            @endforeach
        </section>

        {{-- Needs attention --}}
        <section class="lgd-card" aria-labelledby="attn-title" x-data="{ all: false }">
            <div class="lgd-card__head">
                <h2 id="attn-title">Needs attention</h2>
                @if ($attention->isNotEmpty())
                    <span class="lgd-count">{{ $attention->count() }}</span>
                @endif
            </div>

            @if ($attention->isEmpty())
                <div class="lgd-empty" role="status">
                    <span class="lgd-empty__icon lgd-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                    <strong>Nothing needs attention</strong>
                    <p>No delayed trips, waiting parcels or applications to review.</p>
                </div>
            @else
                <ul class="lgd-list">
                    @foreach ($attention as $i => $item)
                        <li class="lgd-item lgd-row" data-href="{{ $item['url'] }}" @if ($i >= $attentionShown) x-show="all" x-cloak @endif>
                            <span class="lgd-item__icon lgd-tone-{{ $item['tone'] }}"><x-logistics.icon :name="$item['icon']" :size="18" /></span>
                            <div class="lgd-item__body">
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['text'] }}</p>
                            </div>
                            <a href="{{ $item['url'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                {{ $item['cta'] }} <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($attention->count() > $attentionShown)
                    <div class="lgd-more">
                        <button type="button" @click="all = !all" :aria-expanded="all" class="lgd-btn lgd-btn--soft lgd-btn--small"
                                x-text="all ? 'Show fewer' : 'Show {{ $attention->count() - $attentionShown }} more'">Show {{ $attention->count() - $attentionShown }} more</button>
                    </div>
                @endif
            @endif
        </section>

        {{-- Parcels moving through this hub --}}
        <section class="lgd-card" aria-labelledby="flow-title">
            <div class="lgd-card__head">
                <div class="lgd-card__title">
                    <h2 id="flow-title">Parcels moving through this hub</h2>
                    <p class="lgd-card__sub">In the order they pass through.</p>
                </div>
            </div>
            <ol class="lgd-tiles lgd-tiles--4" style="list-style:none;margin:0;padding:0">
                @foreach ($flow as [$label, $value, $icon, $tone, $url, $hint])
                    <li style="display:grid">
                        <a href="{{ $url }}" class="lgd-tile" aria-label="{{ $label }}: {{ $value }}. View details">
                            <span class="lgd-tile__top">
                                <span class="lgd-tile__icon lgd-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                                <span class="lgd-tile__go"><x-logistics.icon name="arrow-right" :size="16" /></span>
                            </span>
                            <span class="lgd-tile__label">{{ $label }}</span>
                            <strong class="lgd-tile__value">{{ number_format($value) }}</strong>
                            <span class="lgd-tile__hint">{{ $hint }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Drivers and vehicles --}}
        <div class="lgd-pair">
            @foreach ($fleet as [$title, $sub, $url, $cta, $emptyTitle, $emptyText, $emptyCta, $rows])
                @php $total = max(1, collect($rows)->sum(fn ($row) => $row[1])); $hasAny = collect($rows)->sum(fn ($row) => $row[1]) > 0; @endphp
                <section class="lgd-card" aria-label="{{ $title }}">
                    <div class="lgd-card__head lgd-card__head--bar">
                        <div class="lgd-card__title">
                            <h2>{{ $title }}</h2>
                            <p class="lgd-card__sub">{{ $sub }}</p>
                        </div>
                        <a href="{{ $url }}" class="lgd-btn lgd-btn--soft lgd-btn--small">{{ $cta }}</a>
                    </div>
                    @if ($hasAny)
                        <div class="lgd-meters">
                            @foreach ($rows as [$label, $count, $tone])
                                <div>
                                    <div class="lgd-meter__top"><span><i class="lgd-meter__dot lgd-meter__dot--{{ $tone }}"></i>{{ $label }}</span><strong>{{ $count }}</strong></div>
                                    <span class="lgd-bar lgd-bar--{{ $tone }}" role="img" aria-label="{{ $count }} of {{ $total }} {{ Str::lower($title) }} are {{ Str::lower($label) }}"><i style="--w: {{ round($count / $total * 100) }}%"></i></span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="lgd-empty" role="status">
                            <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon :name="$title === 'Vehicles' ? 'truck' : 'users'" :size="26" /></span>
                            <strong>{{ $emptyTitle }}</strong>
                            <p>{{ $emptyText }}</p>
                            <a href="{{ $url }}" class="lgd-btn lgd-btn--soft lgd-btn--small">{{ $emptyCta }}</a>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Active transport trips --}}
        <section class="lgd-card lgd-table-card" aria-labelledby="trips-title">
            <div class="lgd-card__head lgd-card__head--bar">
                <div class="lgd-card__title">
                    <h2 id="trips-title">Active transport trips</h2>
                    <p class="lgd-card__sub">{{ $stationTrips }} station {{ Str::plural('transfer', $stationTrips) }} and {{ $provinceTrips }} inter-province {{ Str::plural('trip', $provinceTrips) }}. {{ $delayedRule }}</p>
                </div>
                <a href="{{ route('lgp.province.trips') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">View all trips</a>
            </div>
            <div class="lgd-table-wrap">
                <table class="lgd-table lgd-table--wide">
                    <thead>
                        <tr>
                            <th scope="col">Trip</th>
                            <th scope="col">Route</th>
                            <th scope="col">Driver and vehicle</th>
                            <th scope="col" class="is-num">Parcels</th>
                            <th scope="col">Departure</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgd-sr">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $t)
                            <tr class="lgd-row" data-href="{{ route('lgp.province.trips') }}">
                                <td><span class="lgd-name">{{ $t['id'] }}</span><small>{{ $typeLabel[$t['type']] ?? Str::headline($t['type']) }}</small></td>
                                <td data-label="Route">{{ $t['origin'] }} to {{ $t['destination'] }}</td>
                                <td data-label="Driver and vehicle">{{ $t['driver'] }}<small>{{ $t['vehicle'] }}</small></td>
                                <td class="is-num" data-label="Parcels">{{ $t['parcels'] }}</td>
                                <td data-label="Departure">{{ Carbon::parse($t['departed'] ?? $t['scheduled'])->format('M j, g:i A') }}</td>
                                <td data-label="Status">
                                    <span class="lgd-pill lgd-pill--{{ $tripTone[$t['status']] ?? 'lilac' }}">{{ $tripLabel[$t['status']] ?? Str::headline($t['status']) }}</span>
                                    @if ($delayedIds->contains($t['id']))<span class="lgd-pill lgd-pill--rose">Delayed</span>@endif
                                </td>
                                <td class="lgd-actions">
                                    <a href="{{ route('lgp.province.trips') }}" aria-label="Manage trip {{ $t['id'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                        Manage trip <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="lgd-empty" role="status">
                                        <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="truck" :size="26" /></span>
                                        <strong>No active trips</strong>
                                        <p>Create a trip to move parcels between your stations or to another Province Hub.</p>
                                        <a href="{{ route('lgp.province.trips.create') }}" class="lgd-btn lgd-btn--primary lgd-btn--small"><x-logistics.icon name="plus" :size="14" /> Create trip</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Station Hubs --}}
        <section class="lgd-card lgd-table-card" aria-labelledby="stations-title">
            <div class="lgd-card__head lgd-card__head--bar">
                <div class="lgd-card__title">
                    <h2 id="stations-title">Station Hubs</h2>
                    <p class="lgd-card__sub">Parcels each station has on hand, and its riders.</p>
                </div>
                <a href="{{ route('lgp.province.stations') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">View all stations</a>
            </div>
            <div class="lgd-table-wrap">
                <table class="lgd-table lgd-table--wide">
                    <thead>
                        <tr>
                            <th scope="col">Station</th>
                            <th scope="col">Parcels on hand</th>
                            <th scope="col" class="is-num">Riders</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgd-sr">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stations as $s)
                            @php $stationUrl = route('lgp.province.stations.show', $s['id']); @endphp
                            <tr class="lgd-row" data-href="{{ $stationUrl }}">
                                <td><a href="{{ $stationUrl }}" class="lgd-name">{{ $s['name'] }}</a><small>Manager: {{ $s['manager'] }}</small></td>
                                <td class="lgd-parcels" data-label="Parcels on hand">
                                    <strong>{{ number_format($s['parcels']) }}</strong>
                                    <span class="lgd-bar" role="img" aria-label="{{ number_format($s['parcels']) }} parcels on hand, compared with the busiest station"><i style="--w: {{ round($s['parcels'] / $maxWaiting * 100) }}%"></i></span>
                                </td>
                                <td class="is-num" data-label="Riders">{{ $s['riders'] }}</td>
                                <td data-label="Status"><span class="lgd-pill lgd-pill--{{ $statusTone[$s['status']] ?? 'lilac' }}">{{ $s['status'] }}</span></td>
                                <td class="lgd-actions">
                                    <a href="{{ $stationUrl }}" aria-label="View details for {{ $s['name'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                        View details <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="lgd-empty" role="status">
                                        <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="building" :size="26" /></span>
                                        <strong>No Station Hubs yet</strong>
                                        <p>Add a Station Hub for each municipality you serve.</p>
                                        <a href="{{ route('lgp.province.stations') }}" class="lgd-btn lgd-btn--primary lgd-btn--small"><x-logistics.icon name="plus" :size="14" /> Add Station Hub</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
