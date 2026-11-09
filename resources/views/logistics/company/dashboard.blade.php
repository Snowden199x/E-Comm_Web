{{--
    Company Dashboard (spec section 8, Company Admin). Read-only overview.
    Fed by PortalController::companyDashboard, and by the preview route in routes/logistics_preview.php
    (hard-coded data; it does not pass $activeTripCount, so the view falls back to counting hub trips).
    Look: the seller pastel lilac system. Styles: resources/css/logistics/dashboard-theme.css (loaded last, tokens copied
    from seller/delivered-orders.css). Behavior: resources/js/logistics/dashboard.js (row clicks, button spinner).
    Status colors: new = lilac, waiting = butter, in progress = sky, done = mint, failed = rose.
    Loading: shell skeleton (boot and page-to-page) plus a spinner on buttons that open another page.
    Empty: no hubs (whole page), nothing needs attention, no activity. This page has no search or filters.
--}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $hubList = collect($hubs);
    $stations = $hubList->flatMap(fn ($h) => $h['stations'] ?? []);
    $riders = $stations->sum('riders');
    $parcelsNow = $hubList->sum('parcels');
    $activeTrips = $activeTripCount ?? $hubList->sum(fn ($h) => count($h['trips'] ?? []));
    $maxParcels = max(1, $hubList->max('parcels') ?? 1);
    $exceptions = $delayedParcels + $failedParcels;

    // Needs attention: each item names the page where the work is done. Worst first.
    $hubUrl = fn ($slug) => route('lgp.company.hubs.show', $slug);
    $nameList = fn ($names) => $names->count() > 3
        ? $names->take(3)->implode(', ').' and '.($names->count() - 3).' more'
        : $names->implode(', ');
    $attention = [];

    if ($failedParcels) {
        $attention[] = ['rank' => 0, 'tone' => 'danger', 'icon' => 'x',
            'title' => $failedParcels.' failed '.Str::plural('delivery', $failedParcels),
            'text' => 'The Station Hub must reassign or return '.($failedParcels === 1 ? 'it' : 'them').'.',
            'url' => route('lgp.company.tracking'), 'cta' => 'Track parcels'];
    }
    if ($delayedParcels) {
        $attention[] = ['rank' => 1, 'tone' => 'warning', 'icon' => 'clock',
            'title' => $delayedParcels.' delayed '.Str::plural('parcel', $delayedParcels),
            'text' => $delayedRule,
            'url' => route('lgp.company.tracking'), 'cta' => 'Track parcels'];
    }

    $setup = $hubList->flatMap(fn ($h, $slug) => collect($h['stations'] ?? [])->where('status', 'Setup')
        ->map(fn ($s) => ['name' => $s['name'], 'slug' => $slug]))->values();
    if ($setup->count()) {
        $attention[] = ['rank' => 1, 'tone' => 'warning', 'icon' => 'alert',
            'title' => $setup->count() === 1 ? $setup[0]['name'].' is in setup' : $setup->count().' Station Hubs are in setup',
            'text' => $setup->count() === 1
                ? 'Its manager hasn\'t signed in.'
                : $nameList($setup->pluck('name')).'. Their managers haven\'t signed in.',
            'url' => $setup->count() === 1 ? $hubUrl($setup[0]['slug']) : route('lgp.company.network'),
            'cta' => $setup->count() === 1 ? 'Open hub' : 'View network'];
    }

    $inactive = $hubList->filter(fn ($h) => ($h['status'] ?? '') === 'Inactive');
    if ($inactive->count()) {
        $attention[] = ['rank' => 2, 'tone' => 'plum', 'icon' => 'info',
            'title' => $inactive->count() === 1 ? $inactive->first()['name'].' is inactive' : $inactive->count().' Province Hubs are inactive',
            'text' => $inactive->count() === 1 ? 'Activate it in Province Hubs to use it again.' : $nameList($inactive->pluck('name')).'.',
            'url' => $inactive->count() === 1 ? $hubUrl($inactive->keys()->first()) : route('lgp.company.hubs'),
            'cta' => $inactive->count() === 1 ? 'Open hub' : 'View Province Hubs'];
    }
    $attention = collect($attention)->sortBy('rank')->values();
    $attentionShown = 3;

    // [label, value, icon, url, tone]. Tone follows the shared status colors.
    $tiles = [
        ['Parcels in network', number_format($parcelsNow), 'package', route('lgp.company.tracking'), 'lilac'],
        ['In transit', number_format($parcelsInTransit), 'truck', route('lgp.company.tracking'), 'sky'],
        ['Delivered today', number_format($deliveredToday), 'check-circle', route('lgp.company.reports'), 'mint'],
        ['Delayed or failed', number_format($exceptions), 'alert', route('lgp.company.tracking'), $exceptions > 0 ? 'rose' : 'lilac'],
        ['Active trips', number_format($activeTrips), 'calendar', route('lgp.company.reports'), 'sky'],
    ];

    $networkRows = [
        ['Province Hubs', $hubList->count(), 'map-pin'],
        ['Station Hubs', $stations->count(), 'building'],
        ['Riders', $riders, 'bike'],
        ['Transport drivers', $drivers, 'users'],
        ['Vehicles', $vehicles, 'truck'],
    ];

    $statusTone = ['Active' => 'mint', 'Setup' => 'butter', 'Inactive' => 'lilac'];
    $attentionTone = ['danger' => 'rose', 'warning' => 'butter', 'plum' => 'lilac'];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/dashboard-theme.css', 'resources/js/logistics/dashboard.js'])
@endpush

<x-logistics.shell title="Company Dashboard" role="company">
    <div class="lgd" data-lgd>

        <x-logistics.flash />

        <header class="lgd-head">
            <div>
                <h1>Dashboard</h1>
                <p>{{ now()->format('l, F j') }}</p>
            </div>

            @if ($hubList->isNotEmpty())
                <div class="lgd-head__actions">
                    <a href="{{ route('lgp.company.tracking') }}" class="lgd-btn lgd-btn--primary">
                        <x-logistics.icon name="search" :size="16" /> Track parcels
                    </a>
                    <a href="{{ route('lgp.company.hubs') }}" class="lgd-btn lgd-btn--soft">
                        <x-logistics.icon name="map-pin" :size="16" /> View Province Hubs
                    </a>
                </div>
            @endif
        </header>

        @if ($hubList->isEmpty())
            {{-- First run: nothing else on this page means anything until a Province Hub exists. --}}
            <section class="lgd-card">
                <div class="lgd-empty lgd-empty--page">
                    <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="map-pin" :size="26" /></span>
                    <strong>Add your first Province Hub</strong>
                    <p>Each Province Hub manages its own Station Hubs, drivers, and vehicles.</p>
                    <a href="{{ route('lgp.company.hubs') }}" class="lgd-btn lgd-btn--primary">
                        <x-logistics.icon name="plus" :size="16" /> Add Province Hub
                    </a>
                </div>
            </section>
        @else
            {{-- Parcels and trips --}}
            <section class="lgd-tiles" aria-label="Parcels and trips">
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
                    <div class="lgd-empty lgd-empty--compact" role="status">
                        <span class="lgd-empty__icon lgd-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                        <strong>Nothing needs attention</strong>
                        <p>No failed or delayed parcels, and every hub is active.</p>
                    </div>
                @else
                    <ul class="lgd-list">
                        @foreach ($attention as $i => $item)
                            <li class="lgd-item lgd-row" data-href="{{ $item['url'] }}"
                                @if ($i >= $attentionShown) x-show="all" x-cloak @endif>
                                <span class="lgd-item__icon lgd-tone-{{ $attentionTone[$item['tone']] }}"><x-logistics.icon :name="$item['icon']" :size="18" /></span>
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

            <div class="lgd-grid">

                {{-- Province Hubs --}}
                <section class="lgd-card lgd-table-card" aria-labelledby="hubs-title">
                    <div class="lgd-card__head lgd-card__head--bar">
                        <h2 id="hubs-title">Province Hubs</h2>
                        <a href="{{ route('lgp.company.hubs') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">View all</a>
                    </div>

                    <div class="lgd-table-wrap">
                        <table class="lgd-table">
                            <thead>
                                <tr>
                                    <th scope="col">Province Hub</th>
                                    <th scope="col" class="is-num">Stations</th>
                                    <th scope="col" class="is-num">Riders</th>
                                    <th scope="col">Parcels now</th>
                                    <th scope="col" class="is-num">Trips</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="lgd-sr">Action</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hubs as $slug => $hub)
                                    @php($hubStations = $hub['stations'] ?? [])
                                    <tr class="lgd-row" data-href="{{ $hubUrl($slug) }}">
                                        <td>
                                            <a href="{{ $hubUrl($slug) }}" class="lgd-name">{{ $hub['name'] }}</a>
                                            <small>{{ $hub['province'] }} · {{ $hub['manager'] }}</small>
                                        </td>
                                        <td class="is-num" data-label="Stations">{{ count($hubStations) }}</td>
                                        <td class="is-num" data-label="Riders">{{ collect($hubStations)->sum('riders') }}</td>
                                        <td class="lgd-parcels" data-label="Parcels now">
                                            <strong>{{ number_format($hub['parcels']) }}</strong>
                                            <span class="lgd-bar" role="img" aria-label="{{ number_format($hub['parcels']) }} parcels now, compared with the busiest hub"><i style="--w: {{ round($hub['parcels'] / $maxParcels * 100) }}%"></i></span>
                                        </td>
                                        <td class="is-num" data-label="Trips">{{ count($hub['trips'] ?? []) }}</td>
                                        <td data-label="Status">
                                            <span class="lgd-pill lgd-pill--{{ $statusTone[$hub['status']] ?? 'lilac' }}">{{ $hub['status'] }}</span>
                                        </td>
                                        <td class="lgd-actions">
                                            <a href="{{ $hubUrl($slug) }}" aria-label="View details for {{ $hub['name'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                                View details <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="lgd-side">
                    {{-- Network --}}
                    <section class="lgd-card" aria-labelledby="network-title">
                        <div class="lgd-card__head lgd-card__head--bar">
                            <h2 id="network-title">Network</h2>
                            <a href="{{ route('lgp.company.network') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">View network</a>
                        </div>
                        <dl class="lgd-facts">
                            @foreach ($networkRows as [$label, $value, $icon])
                                <div>
                                    <dt><span class="lgd-mini lgd-tone-lilac"><x-logistics.icon :name="$icon" :size="16" /></span> {{ $label }}</dt>
                                    <dd>{{ number_format($value) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    {{-- Recent activity --}}
                    <section class="lgd-card" aria-labelledby="activity-title">
                        <div class="lgd-card__head">
                            <h2 id="activity-title">Recent activity</h2>
                        </div>

                        @if (count($activity))
                            <ol class="lgd-feed">
                                @foreach ($activity as $a)
                                    <li>
                                        <span class="lgd-mini lgd-tone-lilac"><x-logistics.icon :name="! empty($a['track']) ? 'package' : 'map-pin'" :size="16" /></span>
                                        <div>
                                            <p>{{ $a['text'] }}</p>
                                            <div class="lgd-feed__meta">
                                                <time datetime="{{ Carbon::parse($a['at'])->toIso8601String() }}">{{ Carbon::parse($a['at'])->format('M j, g:i A') }}</time>
                                                @if (! empty($a['track']))
                                                    <a href="{{ route('lgp.company.tracking', ['q' => $a['track']]) }}">Track {{ $a['track'] }}</a>
                                                @endif
                                                @if (! empty($a['hub']) && isset($hubs[$a['hub']]))
                                                    <a href="{{ $hubUrl($a['hub']) }}">Open {{ $hubs[$a['hub']]['name'] }}</a>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="lgd-empty lgd-empty--compact" role="status">
                                <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="clock" :size="26" /></span>
                                <strong>No activity yet</strong>
                                <p>Trips, delivery problems, and new hubs appear here.</p>
                                <a href="{{ route('lgp.company.tracking') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">Track parcels</a>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        @endif
    </div>
</x-logistics.shell>
