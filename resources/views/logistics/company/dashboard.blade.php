{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php and routes/logistics_preview_data.php. Spec section 8 (Company Admin > Dashboard). Read-only overview. --}}
@php
    use Carbon\Carbon;

    $hubList = collect($hubs);
    $stations = $hubList->flatMap(fn ($h) => $h['stations']);
    $riders = $stations->sum('riders');
    $parcelsNow = $hubList->sum('parcels');
    $activeTrips = $hubList->sum(fn ($h) => count($h['trips']));
    $maxParcels = max(1, $hubList->max('parcels') ?? 1);
    $tone = ['Active' => 'green', 'Setup' => 'amber', 'Inactive' => 'gray'];
    $exceptions = $delayedParcels + $failedParcels;

    // Needs your attention: each item links to the page where the work is done.
    $attention = [];
    if ($failedParcels) {
        $attention[] = ['red', 'x', $failedParcels.' failed '.($failedParcels === 1 ? 'delivery' : 'deliveries'), 'The Station Hub must reassign the parcel or return it to the seller.', route('lgp.company.tracking'), 'Open tracking'];
    }
    if ($delayedParcels) {
        $attention[] = ['amber', 'clock', $delayedParcels.' delayed '.($delayedParcels === 1 ? 'parcel' : 'parcels'), $delayedRule, route('lgp.company.tracking'), 'Open tracking'];
    }
    foreach ($hubs as $slug => $hub) {
        foreach ($hub['stations'] as $s) {
            if ($s['status'] === 'Setup') {
                $attention[] = ['amber', 'alert', $s['name'].' is still in setup', 'Its manager has not signed in yet.', route('lgp.company.hubs.show', $slug), 'View hub'];
            }
        }
        if ($hub['status'] === 'Inactive') {
            $attention[] = ['violet', 'info', $hub['name'].' is inactive', 'It has no Station Hubs, drivers or vehicles.', route('lgp.company.hubs.show', $slug), 'View hub'];
        }
    }

    $network = [
        ['Province Hubs', $hubList->count(), 'map-pin', 'blue', route('lgp.company.hubs')],
        ['Station Hubs', $stations->count(), 'building', 'blue', route('lgp.company.network')],
        ['Riders', $riders, 'bike', 'green', route('lgp.company.reports')],
        ['Transport drivers', $drivers, 'users', 'green', route('lgp.company.reports')],
        ['Company vehicles', $vehicles, 'truck', 'amber', route('lgp.company.reports')],
    ];
    $parcelTiles = [
        ['Parcels in the network', number_format($parcelsNow), 'package', 'blue', route('lgp.company.tracking')],
        ['Parcels in transit', number_format($parcelsInTransit), 'truck', 'amber', route('lgp.company.tracking')],
        ['Delivered today', number_format($deliveredToday), 'check-circle', 'green', route('lgp.company.reports')],
        ['Delayed or failed', $exceptions, 'alert', $exceptions ? 'red' : 'green', route('lgp.company.tracking')],
        ['Active transport trips', $activeTrips, 'calendar', 'blue', route('lgp.company.reports')],
    ];
@endphp
<x-logistics.shell title="Company Dashboard" role="company">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Company Dashboard</h1>
                <p>Your whole delivery network: {{ $hubList->count() }} Province Hubs, {{ $stations->count() }} Station Hubs and every parcel moving through them.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.company.tracking') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="search" :size="18" /> Track a parcel</a>
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn"><x-logistics.icon name="map-pin" :size="18" /> Province Hubs</a>
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

        <section aria-labelledby="netStats">
            <h2 class="lg-section-title" id="netStats" style="margin-bottom:12px">Your network</h2>
            <div class="lg-grid lg-grid--5">
                @foreach ($network as $i => [$label, $value, $icon, $t, $url])
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

        <section aria-labelledby="parcelStats">
            <h2 class="lg-section-title" id="parcelStats" style="margin-bottom:12px">Parcels and trips</h2>
            <div class="lg-grid lg-grid--5">
                @foreach ($parcelTiles as $i => [$label, $value, $icon, $t, $url])
                    <a href="{{ $url }}" class="lg-stat lg-rise" style="--i: {{ $i + 5 }}">
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

        <section class="lg-card" aria-labelledby="hubSummary">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="hubSummary">Province Hub summary</h2>
                    <p class="lg-section-sub">Open a hub to see its stations, drivers, vehicles and trips.</p>
                </div>
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--ghost lg-btn--sm">See all Province Hubs</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Province Hub</th><th class="is-num">Station Hubs</th><th class="is-num">Riders</th><th>Parcels now</th><th class="is-num">Active trips</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($hubs as $slug => $hub)
                        <tr>
                            <td data-label="Province Hub">
                                <a href="{{ route('lgp.company.hubs.show', $slug) }}" class="lg-cell-title">{{ $hub['name'] }}</a>
                                <div class="lg-cell-sub">{{ $hub['province'] }} · Manager {{ $hub['manager'] }}</div>
                            </td>
                            <td data-label="Station Hubs" class="is-num">{{ count($hub['stations']) }}</td>
                            <td data-label="Riders" class="is-num">{{ collect($hub['stations'])->sum('riders') }}</td>
                            <td data-label="Parcels now" style="min-width:150px">
                                <div class="lg-meter-row">
                                    <div class="lg-meter-row__top"><span class="sr-only">Parcels now</span><strong>{{ number_format($hub['parcels']) }}</strong></div>
                                    <div class="lg-meter" role="presentation"><span style="--w: {{ round($hub['parcels'] / $maxParcels * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td data-label="Active trips" class="is-num">{{ count($hub['trips']) }}</td>
                            <td data-label="Status"><span class="lg-pill lg-pill--{{ $tone[$hub['status']] ?? 'gray' }}">{{ $hub['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-logistics.empty icon="map-pin" title="No Province Hubs yet" text="Add your first Province Hub. Its manager then adds the Station Hubs.">
                                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--sm">Add a Province Hub</a>
                            </x-logistics.empty>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lg-card" aria-labelledby="actTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="actTitle">Recent activity</h2></div>
            @if (count($activity))
                <ol class="lg-journey">
                    @foreach ($activity as $a)
                        <li>
                            <div class="lg-journey__what">{{ $a['text'] }}</div>
                            <div class="lg-journey__meta">
                                <span>{{ Carbon::parse($a['at'])->format('M j, g:i A') }}</span>
                                @isset($a['track'])<a href="{{ route('lgp.company.tracking', ['q' => $a['track']]) }}">Track {{ $a['track'] }}</a>@endisset
                                @isset($a['hub'])<a href="{{ route('lgp.company.hubs.show', $a['hub']) }}">View {{ $hubs[$a['hub']]['name'] }}</a>@endisset
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <x-logistics.empty icon="clock" title="No activity yet" text="Trips, delivery problems and new hubs show up here as they happen." />
            @endif
        </section>
    </div>
</x-logistics.shell>
