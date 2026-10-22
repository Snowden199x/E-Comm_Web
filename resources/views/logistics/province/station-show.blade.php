{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview_data.php. Spec section 8 (Province Hub > Station Hubs). Read-only: the Province Hub views a station, the Station Hub runs it. --}}
@php
    $statusTone = ['Active' => 'green', 'Setup' => 'amber', 'Inactive' => 'gray'];
    $riderTone = ['Available' => 'green', 'On route' => 'blue', 'Off duty' => 'gray'];
    $tripTone = ['Scheduled' => 'blue', 'In transit' => 'amber', 'Completed' => 'green', 'Cancelled' => 'gray'];
    $riders = $station['riders_list'];
    $onRoute = collect($riders)->where('status', 'On route')->count();
    $available = collect($riders)->where('status', 'Available')->count();
    $activeTrips = collect($station['trips'])->filter(fn ($t) => in_array($t[6], ['Scheduled', 'In transit']))->count();
@endphp
<x-logistics.shell :title="$station['name']" role="province">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <a href="{{ route('lgp.province.stations') }}" class="lg-facility-back">← Station Hubs</a>
                <h1>{{ $station['name'] }}</h1>
                <p>{{ $station['municipality'] }}, {{ $hub['province'] }} · <span class="lg-pill lg-pill--{{ $statusTone[$station['status']] ?? 'gray' }}">{{ $station['status'] }}</span></p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.province.stations') }}" class="lg-btn lg-btn--outline">Back to list</a>
            </div>
        </div>

        @if ($station['status'] === 'Setup')
            <div class="lg-note">
                <x-logistics.icon name="info" :size="18" />
                <span>This station is still being set up. {{ $station['manager'] }} has been invited but has not signed in yet, so it cannot receive parcels.</span>
            </div>
        @endif

        <div class="lg-grid lg-grid--4">
            @foreach ([
                ['Riders', $station['riders'], 'users', 'green'],
                ['Parcels at station', number_format($station['parcels']), 'package', 'amber'],
                ['Riders on route', $onRoute, 'bike', 'blue'],
                ['Active transfers', $activeTrips, 'truck', 'green'],
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
            <section class="lg-card" aria-labelledby="stInfoTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="stInfoTitle">Station information</h2></div>
                <dl class="lg-facts">
                    <div class="is-wide"><dt>Street address</dt><dd>{{ $station['address'] }}</dd></div>
                    <div><dt>Municipality</dt><dd>{{ $station['municipality'] }}</dd></div>
                    <div><dt>Province Hub</dt><dd>{{ $hub['name'] }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $station['status'] }}</dd></div>
                    <div><dt>Active since</dt><dd>{{ $station['since'] }}</dd></div>
                </dl>
            </section>

            <section class="lg-card" aria-labelledby="stMgrTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="stMgrTitle">Station manager</h2></div>
                <dl class="lg-facts">
                    <div class="is-wide"><dt>Name</dt><dd>{{ $station['manager'] }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $station['phone'] }}</dd></div>
                    <div class="is-wide"><dt>Email</dt><dd>{{ $station['email'] }}</dd></div>
                </dl>
            </section>

            <section class="lg-card" aria-labelledby="stStageTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="stStageTitle">Parcels by stage</h2>
                        <p class="lg-section-sub">Where the {{ number_format($station['parcels']) }} parcels at this station are now.</p>
                    </div>
                </div>
                <dl class="lg-facts">
                    @foreach ($station['stage'] as $label => $count)
                        <div><dt>{{ $label }}</dt><dd>{{ $count }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="lg-card" aria-labelledby="stPerfTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="stPerfTitle">Performance</h2>
                        <p class="lg-section-sub">Last 30 days.</p>
                    </div>
                </div>
                @if ($station['perf']['on_time'])
                    <dl class="lg-facts">
                        <div><dt>Delivered on time</dt><dd>{{ $station['perf']['on_time'] }}%</dd></div>
                        <div><dt>Failed deliveries</dt><dd>{{ $station['perf']['failed'] }}%</dd></div>
                        <div class="is-wide"><dt>Average station-to-door time</dt><dd>{{ $station['perf']['hours'] }} hours</dd></div>
                    </dl>
                @else
                    <x-logistics.empty icon="bar-chart" title="No activity yet" text="Performance shows up after this station handles its first parcels." />
                @endif
            </section>
        </div>

        <section class="lg-card" aria-labelledby="stRidersTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="stRidersTitle">Riders</h2>
                    <p class="lg-section-sub">{{ $available }} available, {{ $onRoute }} on route. The station approves riders and assigns their tasks.</p>
                </div>
            </div>
            @if (count($riders))
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Rider</th><th>Vehicle</th><th>Service barangays</th><th class="is-num">Open tasks</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($riders as $r)
                            <tr>
                                <td data-label="Rider"><div class="lg-cell-title">{{ $r['name'] }}</div></td>
                                <td data-label="Vehicle">{{ $r['vehicle'] }}</td>
                                <td data-label="Service barangays">{{ $r['barangays'] }}</td>
                                <td data-label="Open tasks" class="is-num">{{ $r['tasks'] }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $riderTone[$r['status']] ?? 'gray' }}">{{ $r['status'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-logistics.empty icon="users" title="No riders yet" text="Riders in {{ $station['municipality'] }} register in the rider app, then the station manager approves them." />
            @endif
        </section>

        <section class="lg-card" aria-labelledby="stTripTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="stTripTitle">Transfers to and from this station</h2>
                    <p class="lg-section-sub">Trips run by your transport drivers. The station only receives and scans.</p>
                </div>
            </div>
            @if (count($station['trips']))
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Trip</th><th>Type</th><th>Route</th><th>Driver</th><th>Vehicle</th><th class="is-num">Parcels</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($station['trips'] as [$id, $type, $route, $driver, $vehicle, $count, $status])
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
                <x-logistics.empty icon="truck" title="No transfers yet" text="Trips that pick up from or deliver to this station appear here." />
            @endif
        </section>
    </div>
</x-logistics.shell>