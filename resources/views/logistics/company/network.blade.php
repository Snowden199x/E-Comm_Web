{{--
    Company > Network Overview (spec section 8A). Read-only: Province Hubs and the Station Hubs under each.
    Fed by PortalController::companyNetwork (hubCard data), and by the preview route (hard-coded data from routes/logistics_preview_data.php).
    Look: the seller pastel lilac system. Styles: resources/css/logistics/network-theme.css (pushed last, tokens copied from
    seller/delivered-orders.css). No page script: search and status chips run in the Alpine scope below.
    Status colors: Active = mint, Setup = butter, Inactive = lilac.
    Search runs as you type; chips show counts. Loading: shell skeleton on open.
    Empty: no Province Hubs at all, and no hubs match the filters.
--}}
@php
    $tone = ['Active' => 'mint', 'Setup' => 'butter', 'Inactive' => 'lilac'];
    $stationTotal = collect($hubs)->sum(fn ($h) => count($h['stations']));
    // What the search reads, per hub: its own text, and the text of each station.
    $index = collect($hubs)->map(fn ($h) => [
        'status' => $h['status'],
        'text' => strtolower($h['name'].' '.$h['province']),
        'stations' => collect($h['stations'])->map(fn ($s) => strtolower($s['name'].' '.$s['municipality']))->values()->all(),
    ])->all();
    $counts = [
        'all' => count($hubs),
        'active' => collect($hubs)->where('status', 'Active')->count(),
        'inactive' => collect($hubs)->where('status', 'Inactive')->count(),
    ];
@endphp

@push('lg-head')
    @vite('resources/css/logistics/network-theme.css')
@endpush

<x-logistics.shell title="Network Overview" role="company">
    <div class="lgn"
         x-data="{
            index: @js($index),
            q: '',
            tab: 'all',
            get term() { return this.q.trim().toLowerCase(); },
            get filtered() { return this.term !== '' || this.tab !== 'all'; },
            hubShown(slug) {
                const h = this.index[slug];
                if (this.tab !== 'all' && h.status.toLowerCase() !== this.tab) return false;
                return !this.term || h.text.includes(this.term) || h.stations.some(t => t.includes(this.term));
            },
            stationShown(slug, i) {
                const h = this.index[slug];
                return !this.term || h.text.includes(this.term) || h.stations[i].includes(this.term);
            },
            get shownCount() { return Object.keys(this.index).filter(slug => this.hubShown(slug)).length; },
            clear() { this.q = ''; this.tab = 'all'; }
         }">

        <x-logistics.flash />

        <header class="lgn-head">
            <div>
                <h1>Network overview</h1>
                <p>{{ count($hubs) }} Province {{ Str::plural('Hub', count($hubs)) }} and {{ $stationTotal }} Station {{ Str::plural('Hub', $stationTotal) }}. To add or change a hub, use Province Hubs.</p>
            </div>
            <a href="{{ route('lgp.company.hubs') }}" class="lgn-btn lgn-btn--primary"><x-logistics.icon name="map-pin" :size="16" /> Manage Province Hubs</a>
        </header>

        @if (count($hubs))
            <div class="lgn-chips" role="group" aria-label="Filter hubs by status">
                @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $text)
                    <button type="button" class="lgn-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                        {{ $text }} <span class="lgn-chip__count">{{ $counts[$key] }}</span>
                    </button>
                @endforeach
                <button type="button" class="lgn-chip lgn-chip--clear" x-show="filtered" x-cloak @click="clear()">
                    <x-logistics.icon name="x" :size="14" /> Clear filters
                </button>
            </div>

            <div class="lgn-toolbar">
                <label class="lgn-search">
                    <span class="lgn-sr">Search the network</span>
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search hub, station or municipality">
                </label>
                <span class="lgn-meta" aria-live="polite" x-text="shownCount + (shownCount === 1 ? ' Province Hub' : ' Province Hubs')"></span>
            </div>
        @endif

        <div class="lgn-list">
            @foreach ($hubs as $slug => $hub)
                <section class="lgn-card" x-show="hubShown('{{ $slug }}')" aria-labelledby="hub{{ $slug }}">
                    <header class="lgn-card__head">
                        <span class="lgn-tile lgn-tone-lilac"><x-logistics.icon name="map-pin" :size="20" /></span>
                        <div class="lgn-card__title">
                            <h2 id="hub{{ $slug }}"><a href="{{ route('lgp.company.hubs.show', $slug) }}">{{ $hub['name'] }}</a></h2>
                            <p>{{ $hub['province'] }} · {{ count($hub['stations']) }} {{ count($hub['stations']) === 1 ? 'station' : 'stations' }}</p>
                        </div>
                        <span class="lgn-pill lgn-pill--{{ $tone[$hub['status']] ?? 'lilac' }}">{{ $hub['status'] }}</span>
                        <a href="{{ route('lgp.company.hubs.show', $slug) }}" class="lgn-btn lgn-btn--soft lgn-btn--small" aria-label="View details for {{ $hub['name'] }}">
                            View details <x-logistics.icon name="arrow-right" :size="14" class="lgn-nudge" />
                        </a>
                    </header>

                    @if (count($hub['stations']))
                        <ul class="lgn-stations">
                            @foreach ($hub['stations'] as $i => $s)
                                <li class="lgn-station" x-show="stationShown('{{ $slug }}', {{ $i }})">
                                    <span class="lgn-tile lgn-tile--small lgn-tone-sky"><x-logistics.icon name="building" :size="16" /></span>
                                    <div>
                                        <strong>{{ $s['name'] }}</strong>
                                        <small>{{ $s['municipality'] }}</small>
                                    </div>
                                    <span class="lgn-pill lgn-pill--{{ $tone[$s['status']] ?? 'lilac' }}">{{ $s['status'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="lgn-muted">No Station Hubs yet. The hub manager adds one station per municipality from the Province Hub portal.</p>
                    @endif
                </section>
            @endforeach

            {{-- No Province Hubs at all --}}
            @if (! count($hubs))
                <section class="lgn-card lgn-empty" role="status">
                    <span class="lgn-empty__icon lgn-tone-lilac"><x-logistics.icon name="map-pin" :size="26" /></span>
                    <strong>No Province Hubs yet</strong>
                    <p>Create a Province Hub to start building your network.</p>
                    <a href="{{ route('lgp.company.hubs') }}" class="lgn-btn lgn-btn--primary">Create Province Hub</a>
                </section>
            @else
                {{-- Hubs exist, none match --}}
                <section class="lgn-card lgn-empty" x-show="shownCount === 0" x-cloak role="status">
                    <span class="lgn-empty__icon lgn-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No hubs match your filters</strong>
                    <p>Check the spelling, or search by hub, station or municipality.</p>
                    <button type="button" class="lgn-btn lgn-btn--primary" @click="clear()">Clear filters</button>
                </section>
            @endif
        </div>
    </div>
</x-logistics.shell>
