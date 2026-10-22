{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php and routes/logistics_preview_data.php. Spec section 8 (Province Hub > Parcel Operations).
     The next destination is worked out from each order's route, so staff never choose it here (spec section 7). Receiving and sorting only move a parcel along. --}}
<x-logistics.shell title="Parcel Operations" role="province">
    <div class="lg-page"
         x-data="{
            parcels: @js($parcels),
            tripsUrl: @js($tripsUrl),
            tab: 'incoming',
            q: '',
            dest: '',
            limit: 25,
            picked: [],
            tabs: {
                incoming: p => p.stage === 'incoming',
                sorting: p => p.stage === 'sorting',
                outgoing: p => p.stage === 'waiting' || p.stage === 'loaded',
                history: p => p.stage === 'history'
            },
            stageLabels: { incoming: 'On its way', sorting: 'Awaiting sorting', waiting: 'Waiting for a trip', loaded: 'Loaded on a trip', history: 'Done' },
            stageTones: { incoming: 'lg-pill--blue', sorting: 'lg-pill--violet', waiting: 'lg-pill--amber', loaded: 'lg-pill--green', history: 'lg-pill--gray' },
            when: { incoming: 'Left station', sorting: 'Received', outgoing: 'Ready since or departs', history: 'Completed' },
            helps: {
                incoming: 'Parcels on trips that are on their way to this hub. In the live version a parcel is received by scanning its label.',
                sorting: 'Received parcels. Each one already has its next destination from its order route. Mark them sorted so they can go on a trip.',
                outgoing: 'Sorted parcels. Those waiting for a trip are ready to load. Create a trip to move them.',
                history: 'Parcels this hub has already received or handed over.'
            },
            tabParcels() { return this.parcels.filter(this.tabs[this.tab]); },
            get destinations() { return [...new Set(this.tabParcels().map(p => p.to))].sort(); },
            get matched() {
                const s = this.q.trim().toLowerCase();
                return this.tabParcels().filter(p => (!this.dest || p.to === this.dest)
                    && (!s || (p.code + ' ' + p.area + ' ' + p.from + ' ' + p.to + ' ' + (p.trip || '')).toLowerCase().includes(s)));
            },
            get shown() { return this.matched.slice(0, this.limit); },
            count(k) { return this.parcels.filter(this.tabs[k]).length; },
            waitingCount() { return this.parcels.filter(p => p.stage === 'waiting').length; },
            setTab(k) { this.tab = k; this.dest = ''; this.q = ''; this.limit = 25; this.picked = []; },
            fmt(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''; },
            get canAct() { return this.tab === 'incoming' || this.tab === 'sorting'; },
            get actionLabel() { return this.tab === 'incoming' ? 'Receive' : 'Mark sorted'; },
            isPicked(p) { return this.picked.includes(p.code); },
            toggle(p) { this.isPicked(p) ? this.picked = this.picked.filter(c => c !== p.code) : this.picked.push(p.code); },
            get allPicked() { return this.matched.length > 0 && this.matched.every(p => this.isPicked(p)); },
            toggleAll() { this.picked = this.allPicked ? [] : this.matched.map(p => p.code); },
            advance(codes) {
                const now = new Date(); const pad = n => String(n).padStart(2, '0');
                const stamp = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
                const next = { incoming: 'sorting', sorting: 'waiting' };
                let n = 0;
                this.parcels.forEach(p => {
                    if (codes.includes(p.code) && this.tabs[this.tab](p) && next[p.stage]) { p.stage = next[p.stage]; p.at = stamp; n++; }
                });
                this.picked = [];
                if (!n) return;
                const msg = this.tab === 'incoming'
                    ? n + (n === 1 ? ' parcel' : ' parcels') + ' received. ' + (n === 1 ? 'It is' : 'They are') + ' now in Sorting.'
                    : n + (n === 1 ? ' parcel' : ' parcels') + ' sorted. ' + (n === 1 ? 'It is' : 'They are') + ' ready for a transfer trip.';
                this.$dispatch('lg-toast', { type: 'success', message: msg });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Parcel Operations</h1>
                <p>Parcels at {{ $hub['name'] }}, by stage. Each one comes from a Station Hub or another Province Hub and goes to its next destination.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.province.trips.create') }}" class="lg-btn"><x-logistics.icon name="plus" :size="18" /> Create trip</a>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Parcel stage">
            @foreach (['incoming' => 'Incoming', 'sorting' => 'Sorting', 'outgoing' => 'Outgoing', 'history' => 'History'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="setTab('{{ $k }}')">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <p class="lg-section-sub" x-text="helps[tab]"></p>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Search tracking number, area, source or trip" aria-label="Search parcels">
            </div>
            <select class="lg-select" x-model="dest" @change="limit = 25" aria-label="Filter by next destination">
                <option value="">All destinations</option>
                <template x-for="d in destinations" :key="d"><option :value="d" x-text="d"></option></template>
            </select>
            <button type="button" class="lg-btn lg-btn--sm" x-show="canAct" x-cloak :disabled="!picked.length" @click="advance(picked)" x-text="actionLabel + (picked.length ? ' (' + picked.length + ')' : ' selected')"></button>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="matched.length + (matched.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <section class="lg-card" aria-label="Parcels">
            <div class="lg-table-wrap" x-show="matched.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th x-show="canAct" style="width:44px"><input type="checkbox" :checked="allPicked" @change="toggleAll()" aria-label="Select all parcels in this list"></th>
                            <th>Parcel</th><th>Came from</th><th>Next destination</th><th>Stage</th>
                            <th x-text="when[tab]"></th>
                            <th class="is-actions" x-show="canAct">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr>
                                <td x-show="canAct" data-label="Select"><input type="checkbox" :checked="isPicked(p)" @change="toggle(p)" :aria-label="'Select ' + p.code"></td>
                                <td data-label="Parcel"><div class="lg-cell-title" x-text="p.code"></div><div class="lg-cell-sub" x-text="'For ' + p.area"></div></td>
                                <td data-label="Came from" x-text="p.from"></td>
                                <td data-label="Next destination" x-text="p.to"></td>
                                <td data-label="Stage">
                                    <span class="lg-pill" :class="stageTones[p.stage]" x-text="stageLabels[p.stage]"></span>
                                    <div class="lg-cell-sub" x-show="p.trip" x-text="'Trip ' + p.trip + (p.result ? ' · ' + p.result : '')"></div>
                                </td>
                                <td data-label="Time" x-text="fmt(p.at)"></td>
                                <td data-label="Action" class="is-actions" x-show="canAct">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="advance([p.code])" :aria-label="actionLabel + ' ' + p.code" x-text="actionLabel"></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="lg-toolbar" style="justify-content:center;padding:12px" x-show="matched.length > limit">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="limit += 25" x-text="'Show 25 more (' + (matched.length - limit) + ' left)'"></button>
                </div>
            </div>

            <x-logistics.empty x-show="!matched.length && !q.trim() && !dest && tab === 'incoming'" x-cloak icon="inbox" title="Nothing on its way" text="Parcels from your stations and other Province Hubs show up here while their trip is running." />
            <x-logistics.empty x-show="!matched.length && !q.trim() && !dest && tab === 'sorting'" x-cloak icon="check-circle" title="Nothing to sort" text="Received parcels wait here until they are marked sorted." />
            <x-logistics.empty x-show="!matched.length && !q.trim() && !dest && tab === 'outgoing'" x-cloak icon="truck" title="No outgoing parcels" text="Sorted parcels appear here, waiting for a transfer trip.">
                <a href="{{ route('lgp.province.trips.create') }}" class="lg-btn lg-btn--sm">Create trip</a>
            </x-logistics.empty>
            <x-logistics.empty x-show="!matched.length && !q.trim() && !dest && tab === 'history'" x-cloak icon="clock" title="No history yet" text="Parcels this hub has received or handed over are listed here." />
            <x-logistics.empty x-show="!matched.length && (q.trim() || dest)" x-cloak icon="search" title="No parcels match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; dest = ''">Clear filters</button>
            </x-logistics.empty>
        </section>

        <p class="lg-section-sub" x-show="tab === 'outgoing' && waitingCount()" x-cloak>
            <span x-text="waitingCount()"></span> parcels are waiting for a trip. Trips already created are on the <a :href="tripsUrl">Transport Trips</a> page.
        </p>
    </div>
</x-logistics.shell>
