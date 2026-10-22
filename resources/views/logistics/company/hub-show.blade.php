{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview_data.php. Spec section 8 (Company Admin > Province Hub details). --}}
@php
    $active = $hub['status'] === 'Active';
    $stationCount = count($hub['stations']);
    $riders = collect($hub['stations'])->sum('riders');
    [$dAvail, $dTrip, $dOff] = $hub['fleet']['drivers'];
    [$vAvail, $vTrip, $vMaint] = $hub['fleet']['vehicles'];
    $tripTone = ['Scheduled' => 'blue', 'In transit' => 'amber', 'Completed' => 'green', 'Cancelled' => 'gray'];
    $stationTone = ['Active' => 'green', 'Setup' => 'amber', 'Inactive' => 'gray'];
@endphp
<x-logistics.shell :title="$hub['name']" role="company">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <a href="{{ route('lgp.company.hubs') }}" class="lg-facility-back">← Province Hubs</a>
                <h1>{{ $hub['name'] }}</h1>
                <p>{{ $hub['province'] }} · <span class="lg-pill lg-pill--{{ $active ? 'green' : 'gray' }}">{{ $hub['status'] }}</span></p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--outline">Back to list</a>
            </div>
        </div>

        <div class="lg-grid lg-grid--4">
            @foreach ([
                ['Station Hubs', $stationCount, 'building', 'blue'],
                ['Riders', $riders, 'users', 'green'],
                ['Parcels at this hub', number_format($hub['parcels']), 'package', 'amber'],
                ['Active trips', count($hub['trips']), 'truck', 'green'],
            ] as $i => [$label, $value, $icon, $tone])
                <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                    <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                    <div class="lg-stat__body">
                        <p class="lg-stat__label">{{ $label }}</p>
                        <p class="lg-stat__value">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card" aria-labelledby="infoTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="infoTitle">Hub information</h2></div>
                <dl class="lg-facts">
                    <div class="is-wide"><dt>Street address</dt><dd>{{ $hub['address'] }}</dd></div>
                    <div><dt>Province</dt><dd>{{ $hub['province'] }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $hub['status'] }}</dd></div>
                    <div><dt>Manager</dt><dd>{{ $hub['manager'] }}</dd></div>
                    <div><dt>Manager phone</dt><dd>{{ $hub['phone'] }}</dd></div>
                    <div class="is-wide"><dt>Manager email</dt><dd>{{ $hub['email'] }}</dd></div>
                </dl>
            </section>

            <section class="lg-card" aria-labelledby="fleetTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="fleetTitle">Drivers and vehicles</h2>
                        <p class="lg-section-sub">{{ $hub['drivers'] }} transport drivers and {{ $hub['vehicles'] }} company vehicles.</p>
                    </div>
                </div>
                <dl class="lg-facts">
                    <div><dt>Drivers available</dt><dd>{{ $dAvail }}</dd></div>
                    <div><dt>Drivers on a trip</dt><dd>{{ $dTrip }}</dd></div>
                    <div><dt>Drivers off duty</dt><dd>{{ $dOff }}</dd></div>
                    <div><dt>Vehicles available</dt><dd>{{ $vAvail }}</dd></div>
                    <div><dt>Vehicles on a trip</dt><dd>{{ $vTrip }}</dd></div>
                    <div><dt>Vehicles in maintenance</dt><dd>{{ $vMaint }}</dd></div>
                </dl>
            </section>

            <section class="lg-card" aria-labelledby="perfTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="perfTitle">Performance</h2>
                        <p class="lg-section-sub">Last 30 days.</p>
                    </div>
                </div>
                @if ($hub['perf']['on_time'])
                    <dl class="lg-facts">
                        <div><dt>Delivered on time</dt><dd>{{ $hub['perf']['on_time'] }}%</dd></div>
                        <div><dt>Failed deliveries</dt><dd>{{ $hub['perf']['failed'] }}%</dd></div>
                        <div class="is-wide"><dt>Average hub-to-door time</dt><dd>{{ $hub['perf']['hours'] }} hours</dd></div>
                    </dl>
                @else
                    <x-logistics.empty icon="bar-chart" title="No activity yet" text="Performance shows up after this hub handles its first parcels." />
                @endif
            </section>
        </div>

        <section class="lg-card" aria-labelledby="stTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="stTitle">Station Hubs</h2>
                    <p class="lg-section-sub">The Province Hub creates and manages these. You can only view them here.</p>
                </div>
            </div>
            @if ($stationCount)
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Station</th><th>Municipality</th><th>Manager</th><th class="is-num">Riders</th><th class="is-num">Parcels</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($hub['stations'] as $s)
                            <tr>
                                <td data-label="Station"><div class="lg-cell-title">{{ $s['name'] }}</div></td>
                                <td data-label="Municipality">{{ $s['municipality'] }}</td>
                                <td data-label="Manager">{{ $s['manager'] }}</td>
                                <td data-label="Riders" class="is-num">{{ $s['riders'] }}</td>
                                <td data-label="Parcels" class="is-num">{{ number_format($s['parcels']) }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $stationTone[$s['status']] ?? 'gray' }}">{{ $s['status'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-logistics.empty icon="building" title="No Station Hubs yet" text="The hub manager adds one station per municipality from the Province Hub portal." />
            @endif
        </section>

        <section class="lg-card" aria-labelledby="tripTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="tripTitle">Active transport trips</h2></div>
            @if (count($hub['trips']))
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Trip</th><th>Type</th><th>Route</th><th>Driver</th><th>Vehicle</th><th class="is-num">Parcels</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($hub['trips'] as [$id, $type, $route, $driver, $vehicle, $count, $status])
                            <tr>
                                <td data-label="Trip"><div class="lg-cell-title">{{ $id }}</div></td>
                                <td data-label="Type">{{ $type }}</td>
                                <td data-label="Route">{{ $route }}</td>
                                <td data-label="Driver">{{ $driver }}</td>
                                <td data-label="Vehicle">{{ $vehicle }}</td>
                                <td data-label="Parcels" class="is-num">{{ $count }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $tripTone[$status] ?? 'gray' }}">{{ $status }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-logistics.empty icon="truck" title="No active trips" text="Scheduled and in-transit trips from this hub appear here." />
            @endif
        </section>
    </div>
</x-logistics.shell>