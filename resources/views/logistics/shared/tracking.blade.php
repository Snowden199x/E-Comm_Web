{{--
    Parcel Tracking, shared by the Company, Province Hub and Station Hub portals (spec section 8A). Read-only.
    Fed by PortalController::trackingView, and by the preview routes (hard-coded data from routes/logistics_preview_tracking.php).
    Look: the seller pastel lilac system. Styles: resources/css/logistics/tracking-theme.css (pushed last, tokens copied
    from seller/delivered-orders.css). No page script: the list, filters and journey panel run in the Alpine component below.
    Status colors: delayed = butter, on its way = sky, delivered = mint, failed or cancelled = rose.
    Filters and search run in the browser as you type, so there is no Search button. One click on a row opens its journey.
    Loading: the shell skeleton (first load and page-to-page). Empty: no parcels at all, and no parcels match the filters.
    Errors: a search over 100 characters is sent back with a message by the controller and shown in the top banner.
--}}
@php
    $home = $role === 'company' ? route('lgp.company') : route('lgp.hub.'.$role);
    $statusChips = ['all' => 'All', 'moving' => 'On its way', 'delayed' => 'Delayed', 'failed' => 'Failed', 'delivered' => 'Delivered'];
@endphp

@push('lg-head')
    @vite('resources/css/logistics/tracking-theme.css')
@endpush

<x-logistics.shell title="Parcel Tracking" :role="$role">
    <div class="lgt"
         x-data="{
            parcels: @js($parcels),
            routeLabels: @js($routeLabels),
            q: '',
            filter: 'all',
            sel: null,
            opener: null,
            copied: null,
            init() {
                // Dashboard links arrive as ?q=VND-10473: fill the search and open that parcel.
                const q = new URLSearchParams(location.search).get('q');
                if (q) {
                    this.q = q;
                    const hit = this.parcels.find(p => p.code.toLowerCase() === q.toLowerCase());
                    if (hit) this.sel = hit.code;
                }
                this.$watch('sel', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                    if (open) this.$nextTick(() => this.$refs.close?.focus());
                });
            },
            last(p) { return p.events[p.events.length - 1] || { status: 'No updates', where: '', at: null }; },
            isDone(p) { return ['Delivered', 'Completed'].includes(this.last(p).status); },
            isStopped(p) { return ['Cancelled', 'Returned'].includes(this.last(p).status); },
            matches(k, p) {
                if (k === 'delayed') return p.flag === 'delayed';
                if (k === 'failed') return p.flag === 'failed';
                if (k === 'moving') return !p.flag && !this.isDone(p) && !this.isStopped(p);
                if (k === 'delivered') return this.isDone(p);
                return true;
            },
            count(k) { return this.parcels.filter(p => this.matches(k, p)).length; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.parcels.filter(p => this.matches(this.filter, p) && (!s || (p.code + ' ' + p.order).toLowerCase().includes(s)));
            },
            get hasFilters() { return this.q.trim() !== '' || this.filter !== 'all'; },
            get cur() { return this.parcels.find(p => p.code === this.sel) || null; },
            tone(p) { return p.flag === 'failed' || this.isStopped(p) ? 'rose' : p.flag === 'delayed' ? 'butter' : this.isDone(p) ? 'mint' : 'sky'; },
            label(p) { return p.flag === 'failed' ? 'Failed' : p.flag === 'delayed' ? 'Delayed' : (this.isDone(p) || this.isStopped(p)) ? this.last(p).status : 'On its way'; },
            fmt(iso) {
                const d = new Date(iso);
                return isNaN(d) ? 'No date' : d.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            },
            pick(p, el) { this.opener = el || null; this.sel = p.code; },
            close() { this.sel = null; this.$nextTick(() => this.opener?.focus?.()); },
            clear() { this.q = ''; this.filter = 'all'; },
            async copy(text) {
                try {
                    await navigator.clipboard.writeText(text);
                    this.copied = text;
                    setTimeout(() => { if (this.copied === text) this.copied = null; }, 1500);
                    window.logisticsBanner?.({ type: 'success', message: 'Tracking number copied.' });
                } catch (e) {
                    window.logisticsBanner?.({ type: 'error', title: 'Could not copy the tracking number', message: 'Select the number and press Ctrl+C to copy it.' });
                }
            }
         }"
         @keydown.escape.window="if (sel) close()">

        <x-logistics.flash />

        <header class="lgt-head">
            <h1>Parcel tracking</h1>
            <p>{{ $intro ?? 'Find a parcel in your network and follow its journey.' }}</p>
        </header>

        {{-- Status chips with counts --}}
        <div class="lgt-chips" role="group" aria-label="Filter by status">
            @foreach ($statusChips as $key => $label)
                <button type="button" class="lgt-chip" :class="{ 'is-active': filter === '{{ $key }}' }" :aria-pressed="filter === '{{ $key }}'" @click="filter = '{{ $key }}'">
                    {{ $label }} <span class="lgt-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgt-chip lgt-chip--clear" x-show="hasFilters" x-cloak @click="clear()">
                <x-logistics.icon name="x" :size="14" /> Clear filters
            </button>
        </div>

        {{-- Search runs as you type; Enter never reloads the page --}}
        <div class="lgt-toolbar">
            <label class="lgt-search">
                <span class="lgt-sr">Search parcels</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.enter.prevent maxlength="100" autocomplete="off" placeholder="Search by tracking number or order ID">
            </label>
            <span class="lgt-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <div class="lgt-note">
            <x-logistics.icon name="info" :size="18" />
            <p>{{ $delayedRule }}@isset($scopeNote) {{ $scopeNote }}@endisset</p>
        </div>

        <section class="lgt-card lgt-table-card" aria-label="Parcels">
            <div class="lgt-table-wrap" x-show="shown.length">
                <table class="lgt-table">
                    <thead>
                        <tr>
                            <th scope="col">Tracking number</th>
                            <th scope="col">Route</th>
                            <th scope="col">Latest update</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgt-sr">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr class="lgt-row" :class="{ 'is-selected': sel === p.code }" @click="pick(p, $el.querySelector('.lgt-view'))">
                                <td data-label="Tracking number">
                                    <span class="lgt-code">
                                        <strong x-text="p.code"></strong>
                                        <button type="button" class="lgt-copy" :class="{ 'is-done': copied === p.code }" @click.stop="copy(p.code)" :aria-label="'Copy tracking number ' + p.code">
                                            <svg class="lgt-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg>
                                        </button>
                                    </span>
                                    <small x-text="'Order ' + p.order"></small>
                                </td>
                                <td data-label="Route">
                                    <span x-text="p.from + (p.from === p.to ? '' : ' to ' + p.to)"></span>
                                    <small x-text="routeLabels[p.route] || ''"></small>
                                </td>
                                <td data-label="Latest update">
                                    <span x-text="last(p).status"></span>
                                    <small x-text="fmt(last(p).at)"></small>
                                </td>
                                <td data-label="Status"><span class="lgt-pill" :class="'lgt-pill--' + tone(p)" x-text="label(p)"></span></td>
                                <td class="lgt-actions">
                                    <button type="button" class="lgt-btn lgt-btn--soft lgt-btn--small lgt-view" @click.stop="pick(p, $el)" :aria-label="'View journey for ' + p.code">
                                        View journey <x-logistics.icon name="arrow-right" :size="14" class="lgt-nudge" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Nothing to track at all --}}
            <div class="lgt-empty" x-show="!parcels.length" x-cloak role="status">
                <span class="lgt-empty__icon lgt-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                <strong>No parcels in your network</strong>
                <p>Parcels show here once a seller hands an order to one of your facilities.</p>
                <a href="{{ $home }}" class="lgt-btn lgt-btn--primary">Go to dashboard</a>
            </div>

            {{-- Parcels exist, none match --}}
            <div class="lgt-empty" x-show="parcels.length && !shown.length" x-cloak role="status">
                <span class="lgt-empty__icon lgt-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                <strong>No parcels match your filters</strong>
                <p>Check the tracking number or order ID, or pick another status.@isset($scopeNote) Parcels outside your facilities are never shown.@endisset</p>
                <button type="button" class="lgt-btn lgt-btn--primary" @click="clear()">Clear filters</button>
            </div>
        </section>

        {{-- Journey panel: slides in from the right, bottom sheet on phones. Carries its own copy of the tokens (see CSS). --}}
        <div class="lgt-layer" x-show="cur" x-cloak>
            <div class="lgt-backdrop" @click="close()"></div>
            <aside class="lgt-drawer" role="dialog" aria-modal="true" aria-labelledby="lgtTitle">
                <template x-if="cur">
                    <div class="lgt-drawer__inner">
                        <header class="lgt-drawer__head">
                            <div>
                                <h2 id="lgtTitle" x-text="cur.code"></h2>
                                <p x-text="'Order ' + cur.order + (routeLabels[cur.route] ? ' · ' + routeLabels[cur.route] : '')"></p>
                            </div>
                            <span class="lgt-pill" :class="'lgt-pill--' + tone(cur)" x-text="label(cur)"></span>
                            <button type="button" class="lgt-close" x-ref="close" @click="close()" aria-label="Close journey">
                                <svg class="lgt-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </header>

                        <div class="lgt-drawer__body">
                            <div class="lgt-route" aria-label="Route">
                                <template x-for="(s, i) in cur.stops" :key="i">
                                    <span class="lgt-route__stop">
                                        <span class="lgt-stop" x-text="s"></span>
                                        <svg x-show="i < cur.stops.length - 1" class="lgt-ico lgt-ico--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                    </span>
                                </template>
                            </div>

                            <div class="lgt-callout" :class="cur.flag === 'failed' ? 'lgt-callout--rose' : 'lgt-callout--butter'" x-show="cur.flag" role="alert">
                                <x-logistics.icon name="alert" :size="18" />
                                <p x-text="cur.reason"></p>
                            </div>

                            <h3>Journey</h3>
                            <ol class="lgt-journey">
                                <template x-for="(e, i) in cur.events" :key="i">
                                    <li :class="{ 'is-current': i === cur.events.length - 1 }">
                                        <strong x-text="e.status"></strong>
                                        <small x-text="(e.where ? e.where + ' · ' : '') + fmt(e.at)"></small>
                                        <span class="lgt-trip" x-show="e.trip" x-text="e.trip + ' · ' + e.type"></span>
                                        <p x-show="e.trip && e.note" x-text="'Driver and vehicle: ' + e.note"></p>
                                        <p x-show="!e.trip && e.note" x-text="e.note"></p>
                                    </li>
                                </template>
                            </ol>
                            <p class="lgt-muted" x-show="!cur.events.length">No updates for this parcel. Updates appear after its first scan.</p>
                        </div>

                        <footer class="lgt-drawer__foot">
                            <button type="button" class="lgt-btn lgt-btn--soft" @click="copy(cur.code)">
                                <x-logistics.icon name="file" :size="16" /> Copy tracking number
                            </button>
                        </footer>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</x-logistics.shell>
