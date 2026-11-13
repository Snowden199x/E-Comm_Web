{{--
    Province Hub > Station Hub details (spec section 8). Read-only: the Province Hub views a station, the Station Hub runs it.
    Fed by PortalController::stationShow, and by the preview route (hard-coded data from routes/logistics_preview_data.php).
    Look: the seller pastel lilac system, the same .lgs layer as Company > Province Hub details.
    Styles: hub-show-theme.css (shared) + station-show-theme.css (notes, parcel stage meters), pushed last.
    No page script. Activating and deactivating happen on the Station Hubs page.
    Status colors: Active = mint, Setup = butter, Inactive = lilac. Riders: Available = mint, On route = sky, Off duty = lilac.
    Trips: Scheduled = butter, In transit = sky, Completed = mint, Cancelled or delayed = rose.
    Parcel stages: Awaiting sorting = lilac, Ready for delivery = mint, Waiting for transfer = butter, Out for delivery = sky.
    Loading: shell skeleton on open. Empty: no parcels, no performance data, no riders, no transfers. No search or filters.
--}}
@php
    $status = $station['status'];
    $riders = collect($station['riders_list'] ?? []);
    $trips = collect($station['trips'] ?? []);
    $stage = $station['stage'] ?? [];
    $perf = $station['perf'] ?? ['on_time' => 0, 'failed' => 0, 'hours' => 0];
    $parcels = (int) $station['parcels'];
    $stageTotal = array_sum($stage);
    $openTasks = $riders->sum('tasks');
    $activeTrips = $trips->filter(fn ($t) => in_array($t[6], ['Scheduled', 'In transit']))->count();
    $unset = fn ($value) => filled($value) ? $value : 'Not set';

    $statusTone = ['Active' => 'mint', 'Setup' => 'butter', 'Inactive' => 'lilac'];
    $riderTone = ['Available' => 'mint', 'On route' => 'sky', 'Off duty' => 'lilac'];
    $tripTone = ['Scheduled' => 'butter', 'In transit' => 'sky', 'Completed' => 'mint', 'Cancelled' => 'rose', 'Delayed' => 'rose'];
    $stageTone = ['Awaiting sorting' => 'lilac', 'Ready for delivery' => 'mint', 'Waiting for transfer' => 'butter', 'Out for delivery' => 'sky'];
    $typeLabel = ['station_transfer' => 'Station transfer', 'inter_province' => 'Inter-province'];

    // [label, value, icon, tone]. Tone follows the shared status colors.
    $tiles = [
        ['Riders', number_format($station['riders']), 'users', 'lilac'],
        ['Parcels at this station', number_format($parcels), 'package', 'butter'],
        ['Open rider tasks', number_format($openTasks), 'bike', 'sky'],
        ['Active transfers', number_format($activeTrips), 'truck', 'sky'],
    ];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/hub-show-theme.css', 'resources/css/logistics/station-show-theme.css'])
@endpush

<x-logistics.shell :title="$station['name']" role="province">
    <div class="lgs">

        <x-logistics.flash />

        <header class="lgs-head">
            <div>
                <a href="{{ route('lgp.province.stations') }}" class="lgs-back"><x-logistics.icon name="arrow-right" :size="14" class="lgs-back__ico" /> Station Hubs</a>
                <h1>{{ $station['name'] }} <span class="lgs-pill lgs-pill--{{ $statusTone[$status] ?? 'lilac' }}">{{ $status }}</span></h1>
                <p>{{ $station['municipality'] }}, {{ $hub['province'] }}</p>
            </div>
            <div class="lgs-head__actions">
                <a href="{{ route('lgp.province.tracking') }}" class="lgs-btn lgs-btn--primary"><x-logistics.icon name="search" :size="16" /> Track parcels</a>
                <a href="{{ route('lgp.province.stations') }}" class="lgs-btn lgs-btn--soft"><x-logistics.icon name="edit" :size="16" /> Manage in Station Hubs</a>
            </div>
        </header>

        @if ($status === 'Setup')
            <div class="lgs-note lgs-note--butter" role="status">
                <x-logistics.icon name="info" :size="18" />
                <p>This station is in setup. {{ $station['manager'] }} has not signed in yet, so it can’t receive parcels.</p>
            </div>
        @elseif ($status === 'Inactive')
            <div class="lgs-note lgs-note--lilac" role="status">
                <x-logistics.icon name="info" :size="18" />
                <p>This station is deactivated, so it can’t receive parcels. Activate it from Station Hubs when it’s ready.</p>
            </div>
        @endif

        <section class="lgs-tiles" aria-label="Station totals">
            @foreach ($tiles as [$label, $value, $icon, $tone])
                <div class="lgs-tile">
                    <span class="lgs-tile__icon lgs-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                    <span class="lgs-tile__text">{{ $label }}<strong>{{ $value }}</strong></span>
                </div>
            @endforeach
        </section>

        <div class="lgs-grid">
            <section class="lgs-card" aria-labelledby="stInfoTitle">
                <div class="lgs-card__head"><h2 id="stInfoTitle">Station information</h2></div>
                <dl class="lgs-facts">
                    <div class="is-wide"><dt>Street address</dt><dd>{{ $unset($station['address']) }}</dd></div>
                    <div><dt>Municipality</dt><dd>{{ $station['municipality'] }}</dd></div>
                    <div><dt>Province Hub</dt><dd>{{ $hub['name'] }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $status }}</dd></div>
                    <div><dt>Added on</dt><dd>{{ $station['since'] }}</dd></div>
                </dl>
            </section>

            <section class="lgs-card" aria-labelledby="stMgrTitle">
                <div class="lgs-card__head"><h2 id="stMgrTitle">Station manager</h2></div>
                <dl class="lgs-facts">
                    <div class="is-wide"><dt>Name</dt><dd>{{ $station['manager'] }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $unset($station['phone']) }}</dd></div>
                    <div class="is-wide"><dt>Email</dt><dd>{{ $unset($station['email']) }}</dd></div>
                </dl>
            </section>

            <section class="lgs-card" aria-labelledby="stStageTitle">
                <div class="lgs-card__head">
                    <h2 id="stStageTitle">Parcels by stage</h2>
                    <p>Where the parcels at this station are now.</p>
                </div>
                @if ($stageTotal)
                    <ul class="lgs-stages">
                        @foreach ($stage as $label => $count)
                            @php $tone = $stageTone[$label] ?? 'lilac'; @endphp
                            <li class="lgs-stage">
                                <span class="lgs-stage__row"><span><i class="lgs-dot lgs-dot--{{ $tone }}"></i>{{ $label }}</span><strong>{{ number_format($count) }}</strong></span>
                                <span class="lgs-meter" aria-hidden="true"><span class="lgs-meter__fill lgs-meter__fill--{{ $tone }}" style="width: {{ round($count / $stageTotal * 100) }}%"></span></span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="lgs-empty lgs-empty--compact" role="status">
                        <span class="lgs-empty__icon lgs-tone-butter"><x-logistics.icon name="package" :size="26" /></span>
                        <strong>No parcels here</strong>
                        <p>Parcels show by stage once this station receives them.</p>
                    </div>
                @endif
            </section>

            <section class="lgs-card" aria-labelledby="stPerfTitle">
                <div class="lgs-card__head">
                    <h2 id="stPerfTitle">Performance</h2>
                    <p>Last 30 days.</p>
                </div>
                @if ($perf['on_time'])
                    <dl class="lgs-facts">
                        <div><dt>Delivered on time</dt><dd>{{ $perf['on_time'] }}%</dd></div>
                        <div><dt>Failed deliveries</dt><dd>{{ $perf['failed'] }}%</dd></div>
                        <div class="is-wide"><dt>Average station-to-door time</dt><dd>{{ $perf['hours'] }} hours</dd></div>
                    </dl>
                @else
                    <div class="lgs-empty lgs-empty--compact" role="status">
                        <span class="lgs-empty__icon lgs-tone-lilac"><x-logistics.icon name="bar-chart" :size="26" /></span>
                        <strong>No activity yet</strong>
                        <p>Performance shows after this station handles its first parcels.</p>
                    </div>
                @endif
            </section>
        </div>

        <section class="lgs-card lgs-table-card" aria-labelledby="stRidersTitle">
            <div class="lgs-card__head lgs-card__head--pad">
                <h2 id="stRidersTitle">Riders</h2>
                <p>{{ $riders->where('status', 'Available')->count() }} available, {{ $riders->where('status', 'On route')->count() }} on route. The station approves riders and assigns their tasks.</p>
            </div>
            @if ($riders->isNotEmpty())
                <div class="lgs-table-wrap">
                    <table class="lgs-table">
                        <thead><tr><th scope="col">Rider</th><th scope="col">Vehicle</th><th scope="col">Service barangays</th><th scope="col" class="is-num">Open tasks</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($riders as $r)
                                <tr>
                                    <td><strong>{{ $r['name'] }}</strong></td>
                                    <td data-label="Vehicle">{{ $unset($r['vehicle']) }}</td>
                                    <td data-label="Service barangays">{{ $r['barangays'] }}</td>
                                    <td data-label="Open tasks" class="is-num">{{ number_format($r['tasks']) }}</td>
                                    <td data-label="Status"><span class="lgs-pill lgs-pill--{{ $riderTone[$r['status']] ?? 'lilac' }}">{{ $r['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgs-empty" role="status">
                    <span class="lgs-empty__icon lgs-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                    <strong>No riders yet</strong>
                    <p>Riders in {{ $station['municipality'] }} sign up in the rider app. The station manager approves them.</p>
                    <a href="{{ route('lgp.province.messages') }}" class="lgs-btn lgs-btn--soft"><x-logistics.icon name="message" :size="16" /> Message the manager</a>
                </div>
            @endif
        </section>

        <section class="lgs-card lgs-table-card" aria-labelledby="stTripTitle">
            <div class="lgs-card__head lgs-card__head--pad lgs-card__head--row">
                <div>
                    <h2 id="stTripTitle">Transfers to and from this station</h2>
                    <p>Your transport drivers run these trips. The station receives and scans.</p>
                </div>
                @if ($trips->isNotEmpty())
                    <a href="{{ route('lgp.province.trips') }}" class="lgs-btn lgs-btn--soft lgs-btn--small">View all trips</a>
                @endif
            </div>
            @if ($trips->isNotEmpty())
                <div class="lgs-table-wrap">
                    <table class="lgs-table">
                        <thead><tr><th scope="col">Trip</th><th scope="col">Type</th><th scope="col">Route</th><th scope="col">Driver</th><th scope="col">Vehicle</th><th scope="col" class="is-num">Parcels</th><th scope="col">Status</th><th scope="col"><span class="lgs-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($trips as [$id, $type, $route, $driver, $vehicle, $count, $tripStatus])
                                <tr>
                                    <td><strong>{{ $id }}</strong></td>
                                    <td data-label="Type">{{ $typeLabel[$type] ?? $type }}</td>
                                    <td data-label="Route">{{ $route }}</td>
                                    <td data-label="Driver">{{ $driver }}</td>
                                    <td data-label="Vehicle">{{ $vehicle }}</td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($count) }}</td>
                                    <td data-label="Status"><span class="lgs-pill lgs-pill--{{ $tripTone[$tripStatus] ?? 'lilac' }}">{{ $tripStatus }}</span></td>
                                    <td class="lgs-cell-action">
                                        <a href="{{ route('lgp.province.trips') }}" class="lgs-btn lgs-btn--soft lgs-btn--small" aria-label="View trip {{ $id }} in Trips">View trip <x-logistics.icon name="arrow-right" :size="14" /></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgs-empty" role="status">
                    <span class="lgs-empty__icon lgs-tone-sky"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong>No transfers yet</strong>
                    <p>Trips that pick up from or deliver to this station appear here.</p>
                    <a href="{{ route('lgp.province.trips.create') }}" class="lgs-btn lgs-btn--soft"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                </div>
            @endif
        </section>
    </div>
</x-logistics.shell>
