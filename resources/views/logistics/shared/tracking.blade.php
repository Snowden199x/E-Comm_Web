{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview_tracking.php. Spec section 8A (Parcel Tracking). Read-only. --}}
<x-logistics.shell title="Parcel Tracking" :role="$role">
    <div class="lg-page"
         x-data="{
            parcels: @js($parcels),
            routeLabels: @js($routeLabels),
            q: '',
            filter: 'all',
            sel: null,
            filters: {
                all: p => true,
                delayed: p => p.flag === 'delayed',
                failed: p => p.flag === 'failed',
                moving: p => !p.flag && p.events[p.events.length - 1].status !== 'Delivered',
                delivered: p => p.events[p.events.length - 1].status === 'Delivered'
            },
            init() {
                // Dashboard links arrive as ?q=VND-10473.
                const q = new URLSearchParams(location.search).get('q');
                if (q) { this.q = q; const hit = this.parcels.find(p => p.code.toLowerCase() === q.toLowerCase()); if (hit) this.sel = hit.code; }
            },
            count(k) { return this.parcels.filter(this.filters[k]).length; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.parcels.filter(p => this.filters[this.filter](p) && (!s || (p.code + ' ' + p.order).toLowerCase().includes(s)));
            },
            get cur() { return this.parcels.find(p => p.code === this.sel) || null; },
            last(p) { return p.events[p.events.length - 1]; },
            tone(p) { return p.flag === 'failed' ? 'lg-pill--red' : p.flag === 'delayed' ? 'lg-pill--amber' : this.last(p).status === 'Delivered' ? 'lg-pill--green' : 'lg-pill--blue'; },
            label(p) { return p.flag === 'failed' ? 'Failed' : p.flag === 'delayed' ? 'Delayed' : this.last(p).status === 'Delivered' ? 'Delivered' : 'On its way'; },
            fmt(iso) { return new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); },
            pick(p) { this.sel = p.code; }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Parcel Tracking</h1>
                <p>{{ $intro ?? 'Find a parcel in your whole network and follow its journey, trip by trip.' }}</p>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Parcel filters">
            @foreach (['all' => 'All', 'delayed' => 'Delayed', 'failed' => 'Failed', 'moving' => 'On its way', 'delivered' => 'Delivered'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="filter === '{{ $k }}'" @click="filter = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Tracking number or order ID" aria-label="Search parcels">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <div class="lg-note">
            <x-logistics.icon name="info" :size="18" />
            <span>{{ $delayedRule }}@isset($scopeNote) {{ $scopeNote }}@endisset</span>
        </div>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,360px),1fr));align-items:start">
            <section class="lg-card" aria-label="Parcels">
                <ul class="lg-tree__children" style="margin:0;padding:0;border:0" x-show="shown.length">
                    <template x-for="p in shown" :key="p.code">
                        <li class="lg-tree__leaf" style="padding:12px 20px">
                            <button type="button" class="lg-btn lg-btn--ghost" style="width:100%;justify-content:space-between;height:auto;text-align:left;padding:4px 0"
                                    :aria-current="sel === p.code ? 'true' : null" @click="pick(p)">
                                <span>
                                    <span class="lg-cell-title" x-text="p.code"></span>
                                    <span class="lg-cell-sub" style="display:block" x-text="p.from + (p.from === p.to ? '' : ' to ' + p.to)"></span>
                                    <span class="lg-cell-sub" style="display:block" x-text="last(p).status + ' · ' + fmt(last(p).at)"></span>
                                </span>
                                <span class="lg-pill" :class="tone(p)" x-text="label(p)"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <x-logistics.empty x-show="!shown.length" x-cloak icon="search" title="No parcel found" :text="'Check the tracking number or order ID, or pick another filter.'.(isset($scopeNote) ? ' Parcels outside your hub are never shown.' : '')">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; filter = 'all'">Clear search</button>
                </x-logistics.empty>
            </section>

            <section class="lg-card" aria-label="Parcel journey" aria-live="polite">
                <x-logistics.empty x-show="!cur" icon="package" title="Pick a parcel" text="Choose a parcel on the left to see where it has been and who has handled it." />
                <template x-if="cur">
                    <div>
                        <div class="lg-card__head">
                            <div>
                                <h2 class="lg-section-title" x-text="cur.code"></h2>
                                <p class="lg-section-sub" x-text="'Order ' + cur.order + ' · ' + routeLabels[cur.route]"></p>
                            </div>
                            <span class="lg-pill" :class="tone(cur)" x-text="label(cur)"></span>
                        </div>
                        <div class="lg-card__body" style="display:grid;gap:16px;padding-top:0">
                            <div class="lg-route__hubs" aria-label="Route">
                                <template x-for="(s, i) in cur.stops" :key="s">
                                    <span style="display:inline-flex;align-items:center;gap:6px">
                                        <span class="lg-stop" x-text="s"></span>
                                        <svg x-show="i < cur.stops.length - 1" class="lg-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                    </span>
                                </template>
                            </div>
                            <div class="lg-note" :class="cur.flag === 'failed' ? 'lg-note--warn' : ''" x-show="cur.flag">
                                <x-logistics.icon name="alert" :size="18" />
                                <span x-text="cur.reason"></span>
                            </div>
                        </div>
                        <ol class="lg-journey">
                            <template x-for="(e, i) in cur.events" :key="i">
                                <li :class="i === cur.events.length - 1 ? 'is-current' : ''">
                                    <div class="lg-journey__what" x-text="e.status"></div>
                                    <div class="lg-journey__meta">
                                        <span x-text="e.where"></span>
                                        <span x-text="fmt(e.at)"></span>
                                        <span class="lg-pill lg-pill--violet" x-show="e.trip" x-text="e.trip + ' · ' + e.type"></span>
                                    </div>
                                    <div class="lg-journey__meta" x-show="e.trip && e.note" x-text="'Driver and vehicle: ' + e.note"></div>
                                    <div class="lg-journey__meta" x-show="!e.trip && e.note" x-text="e.note"></div>
                                </li>
                            </template>
                        </ol>
                    </div>
                </template>
            </section>
        </div>
    </div>
</x-logistics.shell>
