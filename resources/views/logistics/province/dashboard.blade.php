{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php and routes/logistics_preview_data.php. Spec section 8 (Province Hub > Dashboard).
     Read-only overview. Every card links to the page where the work is done. --}}
@php
    use Carbon\Carbon;

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

    $stationTone = ['Active' => 'green', 'Setup' => 'amber', 'Inactive' => 'gray'];
    $statusLabel = ['scheduled' => 'Scheduled', 'in_transit' => 'In transit'];
    $statusTone = ['scheduled' => 'blue', 'in_transit' => 'amber'];
    $typeLabel = ['station_transfer' => 'Station transfer', 'inter_province' => 'Inter-province'];
    $maxWaiting = max(1, $stations->max('parcels') ?? 1);

    // Needs your attention: each item links to the page where it is fixed.
    $attention = [];
    foreach ($delayed as $d) {
        $attention[] = ['red', 'clock', 'Transfer '.$d['id'].' is delayed', $d['text'], route('lgp.province.trips'), 'Open trips'];
    }
    if ($waitingForTrip) {
        $attention[] = ['amber', 'truck', $waitingForTrip.' parcels are waiting for a transfer trip', 'Create a trip and assign an available driver and vehicle.', route('lgp.province.trips.create'), 'Create trip'];
    }
    if ($pending->count()) {
        $attention[] = ['violet', 'users', $pending->count().' driver '.($pending->count() === 1 ? 'application' : 'applications').' to review',
            $expiredApplications ? $expiredApplications.' of them '.($expiredApplications === 1 ? 'has' : 'have').' an expired license.' : 'Approve or reject them so they can take trips.',
            route('lgp.province.drivers'), 'Review'];
    }
    foreach ($stationsSetup as $s) {
        $attention[] = ['amber', 'alert', $s['name'].' is still in setup', 'Its manager has not signed in yet, so it cannot receive parcels.', route('lgp.province.stations.show', \Illuminate\Support\Str::slug($s['municipality'])), 'View station'];
    }
    if ($vehicleCount('maintenance')) {
        $attention[] = ['blue', 'info', $vehicleCount('maintenance').' '.($vehicleCount('maintenance') === 1 ? 'vehicle is' : 'vehicles are').' in maintenance', 'It cannot be assigned to a trip until it is set back to available.', route('lgp.province.vehicles'), 'Open vehicles'];
    }

    // Parcels at this hub, in the order they move through it.
    $flow = [
        ['Incoming transfers', $incomingParcels, 'inbox', 'blue', route('lgp.province.parcels'), count($incoming) ? count($incoming).' '.(count($incoming) === 1 ? 'trip is' : 'trips are').' bringing parcels here from stations or other hubs.' : 'No trips are on their way here.'],
        ['Awaiting sorting', $awaitingSorting, 'layers', 'violet', route('lgp.province.parcels'), 'Received and scanned. Each one gets its next destination.'],
        ['Waiting for a trip', $waitingForTrip, 'package', 'amber', route('lgp.province.trips.create'), 'Sorted and ready to load. Create a trip to move them.'],
        ['Outgoing transfers', $outgoingParcels, 'truck', 'green', route('lgp.province.trips'), count($outgoing) ? count($outgoing).' '.(count($outgoing) === 1 ? 'trip is' : 'trips are').' loaded or scheduled to leave this hub.' : 'No trips are scheduled to leave.'],
    ];

    $headline = [
        ['Parcels at this hub', number_format($hub['parcels']), 'package', 'blue', route('lgp.province.parcels')],
        ['Station Hubs', $stations->count(), 'building', 'blue', route('lgp.province.stations')],
        ['Active trips', $trips->count(), 'calendar', 'amber', route('lgp.province.trips')],
        ['Delayed transfers', $delayedCount, 'clock', $delayedCount ? 'red' : 'green', route('lgp.province.trips')],
    ];

    // Drivers and vehicles are shown as availability: ready, busy, unavailable.
    $fleet = [
        ['Transport drivers', $approvedDrivers.' approved', route('lgp.province.drivers'), 'Manage drivers', [
            ['Available', $driverCount('available'), 'green'],
            ['On a trip', $driverCount('on_trip'), 'amber'],
            ['Off duty', $driverCount('off_duty'), 'gray'],
        ]],
        ['Vehicles', $activeVehicles.' in service', route('lgp.province.vehicles'), 'Manage vehicles', [
            ['Available', $vehicleCount('available'), 'green'],
            ['On a trip', $vehicleCount('on_trip'), 'amber'],
            ['In maintenance', $vehicleCount('maintenance'), 'gray'],
        ]],
    ];
@endphp
<x-logistics.shell title="Province Hub Dashboard" role="province">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Province Hub Dashboard</h1>
                <p>{{ $hub['name'] }}: {{ $stations->count() }} Station Hubs, {{ number_format($hub['parcels']) }} parcels on hand and {{ $trips->count() }} active transport trips.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.province.parcels') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="inbox" :size="18" /> Parcel operations</a>
                <a href="{{ route('lgp.province.trips.create') }}" class="lg-btn"><x-logistics.icon name="plus" :size="18" /> Create trip</a>
            </div>
        </div>

        @if (count($attention))
            <section aria-labelledby="attnTitle">
                <h2 class="lg-section-title" id="attnTitle" style="margin-bottom:12px">Needs your attention</h2>
                <ul class="lg-attn">
                    @foreach ($attention as $i => [$t, $icon, $title, $text, $url, $cta])
                        <li class="lg-attn__item lg-rise" style="--i: {{ $i }}">
                            <span class="lg-attn__icon lg-attn__icon--{{ $t }}"><x-logistics.icon :name="$icon" :size="20" /></span>
                            <span class="lg-attn__text">{{ $title }}<small>{{ $text }}</small></span>
                            <a href="{{ $url }}" class="lg-btn lg-btn--outline lg-btn--sm">{{ $cta }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section aria-labelledby="hubStats">
            <h2 class="lg-section-title" id="hubStats" style="margin-bottom:12px">Your hub</h2>
            <div class="lg-grid lg-grid--4">
                @foreach ($headline as $i => [$label, $value, $icon, $t, $url])
                    <a href="{{ $url }}" class="lg-stat lg-rise" style="--i: {{ $i }}">
                        <span class="lg-stat__icon lg-stat__icon--{{ $t }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body">
                            <p class="lg-stat__label">{{ $label }}</p>
                            <p class="lg-stat__value">{{ $value }}</p>
                        </div>
                        <x-logistics.icon name="arrow-right" :size="18" class="lg-stat__go" />
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="flowTitle">
            <h2 class="lg-section-title" id="flowTitle" style="margin-bottom:12px">Parcels moving through this hub</h2>
            <ol class="lg-flow">
                @foreach ($flow as $i => [$label, $value, $icon, $t, $url, $text])
                    <li>
                        <a href="{{ $url }}" class="lg-flow__step lg-rise" style="--i: {{ $i }}">
                            <span class="lg-flow__top">
                                <span class="lg-flow__chip"><x-logistics.icon :name="$icon" :size="22" /></span>
                                <span class="lg-flow__count">{{ number_format($value) }}</span>
                            </span>
                            <h3>{{ $label }}</h3>
                            <p>{{ $text }}</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            @foreach ($fleet as [$title, $sub, $url, $cta, $rows])
                @php $total = max(1, collect($rows)->sum(1)); @endphp
                <section class="lg-card" aria-label="{{ $title }}">
                    <div class="lg-card__head">
                        <div>
                            <h2 class="lg-section-title">{{ $title }}</h2>
                            <p class="lg-section-sub">{{ $sub }}</p>
                        </div>
                        <a href="{{ $url }}" class="lg-btn lg-btn--ghost lg-btn--sm">{{ $cta }}</a>
                    </div>
                    <div style="display:grid;gap:14px">
                        @foreach ($rows as [$label, $count, $t])
                            <div class="lg-meter-row">
                                <div class="lg-meter-row__top"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                                <div class="lg-meter {{ ['green' => 'lg-meter--ok', 'amber' => 'lg-meter--warn', 'gray' => ''][$t] }}" role="presentation"><span style="--w: {{ round($count / $total * 100) }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <section class="lg-card" aria-labelledby="tripsTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="tripsTitle">Active transport trips</h2>
                    <p class="lg-section-sub">{{ $stationTrips }} station {{ $stationTrips === 1 ? 'transfer' : 'transfers' }} and {{ $provinceTrips }} inter-province {{ $provinceTrips === 1 ? 'trip' : 'trips' }}. {{ $delayedRule }}</p>
                </div>
                <a href="{{ route('lgp.province.trips') }}" class="lg-btn lg-btn--ghost lg-btn--sm">All trips</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Trip</th><th>Route</th><th>Driver and vehicle</th><th class="is-num">Parcels</th><th>Departure</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($trips as $t)
                        <tr>
                            <td data-label="Trip"><span class="lg-cell-title">{{ $t['id'] }}</span><div class="lg-cell-sub">{{ $typeLabel[$t['type']] }}</div></td>
                            <td data-label="Route">{{ $t['origin'] }} to {{ $t['destination'] }}</td>
                            <td data-label="Driver and vehicle">{{ $t['driver'] }}<div class="lg-cell-sub">{{ $t['vehicle'] }}</div></td>
                            <td data-label="Parcels" class="is-num">{{ $t['parcels'] }}</td>
                            <td data-label="Departure">{{ Carbon::parse($t['departed'] ?? $t['scheduled'])->format('M j, g:i A') }}</td>
                            <td data-label="Status">
                                <span class="lg-pill lg-pill--{{ $statusTone[$t['status']] }}">{{ $statusLabel[$t['status']] }}</span>
                                @if (collect($delayed)->contains('id', $t['id']))<span class="lg-pill lg-pill--red">Delayed</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-logistics.empty icon="truck" title="No active trips" text="Create a trip to move parcels between your stations or to another Province Hub.">
                                <a href="{{ route('lgp.province.trips.create') }}" class="lg-btn lg-btn--sm">Create trip</a>
                            </x-logistics.empty>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lg-card" aria-labelledby="stTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="stTitle">Station Hubs</h2>
                    <p class="lg-section-sub">Parcels each station has on hand, and the riders who deliver them.</p>
                </div>
                <a href="{{ route('lgp.province.stations') }}" class="lg-btn lg-btn--ghost lg-btn--sm">All stations</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Station</th><th>Parcels on hand</th><th class="is-num">Riders</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($stations as $s)
                        <tr>
                            <td data-label="Station">
                                <a href="{{ route('lgp.province.stations.show', \Illuminate\Support\Str::slug($s['municipality'])) }}" class="lg-cell-title">{{ $s['name'] }}</a>
                                <div class="lg-cell-sub">Manager {{ $s['manager'] }}</div>
                            </td>
                            <td data-label="Parcels on hand" style="min-width:180px">
                                <div class="lg-meter-row">
                                    <div class="lg-meter-row__top"><span class="sr-only">Parcels on hand</span><strong>{{ $s['parcels'] }} parcels</strong></div>
                                    <div class="lg-meter" role="presentation"><span style="--w: {{ round($s['parcels'] / $maxWaiting * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td data-label="Riders" class="is-num">{{ $s['riders'] }}</td>
                            <td data-label="Status"><span class="lg-pill lg-pill--{{ $stationTone[$s['status']] ?? 'gray' }}">{{ $s['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <x-logistics.empty icon="building" title="No Station Hubs yet" text="Add a Station Hub for each municipality you serve. It appears here once it is set up.">
                                <a href="{{ route('lgp.province.stations') }}" class="lg-btn lg-btn--sm">Add a Station Hub</a>
                            </x-logistics.empty>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
