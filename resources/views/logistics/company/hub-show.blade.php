{{--
    Company > Province Hub details (spec section 8). Read-only.
    Fed by PortalController::companyHubShow (hubCard data), and by the preview route (hard-coded data from routes/logistics_preview_data.php).
    Look: the seller pastel lilac system. Styles: resources/css/logistics/hub-show-theme.css (pushed last, tokens copied from
    seller/delivered-orders.css). No page script. Editing, activating and deleting happen on the Province Hubs page.
    Status colors: Active = mint, Setup = butter, Inactive = lilac; trips: Scheduled = butter, In transit = sky, Completed = mint, Cancelled = rose.
    Loading: shell skeleton on open. Empty: no performance data, no Station Hubs, no active trips. This page has no search or filters.
--}}
@php
    $active = $hub['status'] === 'Active';
    $stationCount = count($hub['stations']);
    $riders = collect($hub['stations'])->sum('riders');
    // Preview data keys the fleet as drivers/vehicles. The live hubCard sends one flat list: 3 driver counts, then 3 vehicle counts.
    $fleet = $hub['fleet'] ?? [];
    $driverFleet = $fleet['drivers'] ?? array_pad(array_slice($fleet, 0, 3), 3, 0);
    $vehicleFleet = $fleet['vehicles'] ?? array_pad(array_slice($fleet, 3, 3), 3, 0);
    $driverCount = $hub['drivers'] ?? array_sum($driverFleet);
    $vehicleCount = $hub['vehicles'] ?? array_sum($vehicleFleet);
    [$dAvail, $dTrip, $dOff] = $driverFleet;
    [$vAvail, $vTrip, $vMaint] = $vehicleFleet;
    $pendingEmail = $hub['pending_email'] ?? null;
    $perf = $hub['perf'] ?? ['on_time' => 0, 'failed' => 0, 'hours' => 0];

    $tripTone = ['Scheduled' => 'butter', 'In transit' => 'sky', 'Completed' => 'mint', 'Cancelled' => 'rose'];
    $stationTone = ['Active' => 'mint', 'Setup' => 'butter', 'Inactive' => 'lilac'];

    // [label, value, icon, tone]. Tone follows the shared status colors.
    $tiles = [
        ['Station Hubs', number_format($stationCount), 'building', 'lilac'],
        ['Riders', number_format($riders), 'bike', 'lilac'],
        ['Parcels at this hub', number_format($hub['parcels']), 'package', 'butter'],
        ['Active trips', number_format(count($hub['trips'])), 'truck', 'sky'],
    ];
@endphp

@push('lg-head')
    @vite('resources/css/logistics/hub-show-theme.css')
@endpush

<x-logistics.shell :title="$hub['name']" role="company">
    <div class="lgs">

        <x-logistics.flash />

        <header class="lgs-head">
            <div>
                <a href="{{ route('lgp.company.hubs') }}" class="lgs-back"><x-logistics.icon name="arrow-right" :size="14" class="lgs-back__ico" /> Province Hubs</a>
                <h1>{{ $hub['name'] }} <span class="lgs-pill lgs-pill--{{ $active ? 'mint' : 'lilac' }}">{{ $hub['status'] }}</span></h1>
                <p>{{ $hub['province'] }}</p>
            </div>
            <div class="lgs-head__actions">
                <a href="{{ route('lgp.company.tracking') }}" class="lgs-btn lgs-btn--primary"><x-logistics.icon name="search" :size="16" /> Track parcels</a>
                <a href="{{ route('lgp.company.hubs') }}" class="lgs-btn lgs-btn--soft"><x-logistics.icon name="edit" :size="16" /> Edit in Province Hubs</a>
            </div>
        </header>

        <section class="lgs-tiles" aria-label="Hub totals">
            @foreach ($tiles as [$label, $value, $icon, $tone])
                <div class="lgs-tile">
                    <span class="lgs-tile__icon lgs-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                    <span class="lgs-tile__text">{{ $label }}<strong>{{ $value }}</strong></span>
                </div>
            @endforeach
        </section>

        <div class="lgs-grid">
            <section class="lgs-card" aria-labelledby="infoTitle">
                <div class="lgs-card__head"><h2 id="infoTitle">Hub information</h2></div>
                <dl class="lgs-facts">
                    <div class="is-wide"><dt>Street address</dt><dd>{{ $hub['address'] ?: 'Not set' }}</dd></div>
                    <div><dt>Province</dt><dd>{{ $hub['province'] }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $hub['status'] }}</dd></div>
                    <div><dt>Manager</dt><dd>{{ $hub['manager'] }}</dd></div>
                    <div><dt>Manager phone</dt><dd>{{ $hub['phone'] ?: 'Not set' }}</dd></div>
                    <div class="is-wide">
                        <dt>Manager email</dt>
                        <dd>{{ $hub['email'] ?: 'Not set' }}</dd>
                        @if ($pendingEmail)
                            <dd class="lgs-pending">New email {{ $pendingEmail }} is not confirmed yet.</dd>
                        @endif
                    </div>
                </dl>
            </section>

            <section class="lgs-card" aria-labelledby="fleetTitle">
                <div class="lgs-card__head">
                    <div>
                        <h2 id="fleetTitle">Drivers and vehicles</h2>
                        <p>{{ $driverCount }} transport {{ Str::plural('driver', $driverCount) }} and {{ $vehicleCount }} company {{ Str::plural('vehicle', $vehicleCount) }}.</p>
                    </div>
                </div>
                <dl class="lgs-facts">
                    @foreach ([
                        ['Drivers available', $dAvail, 'mint'], ['Drivers on a trip', $dTrip, 'sky'], ['Drivers off duty', $dOff, 'butter'],
                        ['Vehicles available', $vAvail, 'mint'], ['Vehicles on a trip', $vTrip, 'sky'], ['Vehicles in maintenance', $vMaint, 'butter'],
                    ] as [$label, $value, $dot])
                        <div><dt><i class="lgs-dot lgs-dot--{{ $dot }}"></i>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="lgs-card" aria-labelledby="perfTitle">
                <div class="lgs-card__head">
                    <div>
                        <h2 id="perfTitle">Performance</h2>
                        <p>Last 30 days.</p>
                    </div>
                </div>
                @if ($perf['on_time'])
                    <dl class="lgs-facts">
                        <div><dt>Delivered on time</dt><dd>{{ $perf['on_time'] }}%</dd></div>
                        <div><dt>Failed deliveries</dt><dd>{{ $perf['failed'] }}%</dd></div>
                        <div class="is-wide"><dt>Average hub-to-door time</dt><dd>{{ $perf['hours'] }} hours</dd></div>
                    </dl>
                @else
                    <div class="lgs-empty lgs-empty--compact" role="status">
                        <span class="lgs-empty__icon lgs-tone-lilac"><x-logistics.icon name="bar-chart" :size="26" /></span>
                        <strong>No activity yet</strong>
                        <p>Performance shows after this hub handles its first parcels.</p>
                    </div>
                @endif
            </section>
        </div>

        <section class="lgs-card lgs-table-card" aria-labelledby="stTitle">
            <div class="lgs-card__head lgs-card__head--pad">
                <div>
                    <h2 id="stTitle">Station Hubs</h2>
                    <p>The Province Hub creates and manages these. You can view them here.</p>
                </div>
            </div>
            @if ($stationCount)
                <div class="lgs-table-wrap">
                    <table class="lgs-table">
                        <thead><tr><th scope="col">Station</th><th scope="col">Municipality</th><th scope="col">Manager</th><th scope="col" class="is-num">Riders</th><th scope="col" class="is-num">Parcels</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($hub['stations'] as $s)
                                <tr>
                                    <td><strong>{{ $s['name'] }}</strong></td>
                                    <td data-label="Municipality">{{ $s['municipality'] }}</td>
                                    <td data-label="Manager">{{ $s['manager'] }}</td>
                                    <td data-label="Riders" class="is-num">{{ number_format($s['riders']) }}</td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($s['parcels']) }}</td>
                                    <td data-label="Status"><span class="lgs-pill lgs-pill--{{ $stationTone[$s['status']] ?? 'lilac' }}">{{ $s['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgs-empty" role="status">
                    <span class="lgs-empty__icon lgs-tone-lilac"><x-logistics.icon name="building" :size="26" /></span>
                    <strong>No Station Hubs yet</strong>
                    <p>The hub manager adds one station per municipality from the Province Hub portal.</p>
                    <a href="{{ route('lgp.company.network') }}" class="lgs-btn lgs-btn--soft">View network</a>
                </div>
            @endif
        </section>

        <section class="lgs-card lgs-table-card" aria-labelledby="tripTitle">
            <div class="lgs-card__head lgs-card__head--pad"><h2 id="tripTitle">Active transport trips</h2></div>
            @if (count($hub['trips']))
                <div class="lgs-table-wrap">
                    <table class="lgs-table">
                        <thead><tr><th scope="col">Trip</th><th scope="col">Type</th><th scope="col">Route</th><th scope="col">Driver</th><th scope="col">Vehicle</th><th scope="col" class="is-num">Parcels</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($hub['trips'] as [$id, $type, $route, $driver, $vehicle, $count, $status])
                                <tr>
                                    <td><strong>{{ $id }}</strong></td>
                                    <td data-label="Type">{{ $type }}</td>
                                    <td data-label="Route">{{ $route }}</td>
                                    <td data-label="Driver">{{ $driver }}</td>
                                    <td data-label="Vehicle">{{ $vehicle }}</td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($count) }}</td>
                                    <td data-label="Status"><span class="lgs-pill lgs-pill--{{ $tripTone[$status] ?? 'lilac' }}">{{ $status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgs-empty" role="status">
                    <span class="lgs-empty__icon lgs-tone-sky"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong>No active trips</strong>
                    <p>Scheduled and in-transit trips from this hub appear here.</p>
                </div>
            @endif
        </section>
    </div>
</x-logistics.shell>
