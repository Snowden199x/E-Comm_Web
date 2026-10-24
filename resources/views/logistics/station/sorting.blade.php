{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec section 8 (Station Hub > Sorting) and section 7.
     Received parcels and where they go next. The next step is worked out from each order's route, so staff never choose a destination here:
     a parcel for a barangay of this station goes to the Delivery Queue, any other waits for a station transfer to the Province Hub.
     Marking a parcel sorted only moves it along. Interactions change page state only. --}}
<x-logistics.shell title="Sorting" role="station">
    <div class="lg-page"
         x-data="{
            parcels: @js($parcels),
            hub: @js($hub),
            tab: 'awaiting_sorting',
            q: '',
            step: '',
            limit: 25,
            picked: [],
            helps: {
                awaiting_sorting: 'Received and scanned parcels. Each one already has its next step from its order route. Mark them sorted to move them along.',
                sorted: 'Parcels sorted today, and where each one went.'
            },
            get tabList() { return this.parcels.filter(p => p.stage === this.tab).sort((a, b) => b.rcv - a.rcv); },
            get matched() {
                const s = this.q.trim().toLowerCase();
                return this.tabList.filter(p => (!this.step || p.next === this.step)
                    && (!s || [p.code, p.seller, p.rider, p.to].some(v => v.toLowerCase().includes(s))));
            },
            get shown() { return this.matched.slice(0, this.limit); },
            count(st) { return this.parcels.filter(p => p.stage === st).length; },
            waiting(next) { return this.parcels.filter(p => p.stage === 'awaiting_sorting' && p.next === next).length; },
            get canAct() { return this.tab === 'awaiting_sorting'; },
            isPicked(p) { return this.picked.includes(p.code); },
            toggle(p) { this.isPicked(p) ? this.picked = this.picked.filter(c => c !== p.code) : this.picked.push(p.code); },
            get allPicked() { return this.matched.length > 0 && this.matched.every(p => this.isPicked(p)); },
            toggleAll() { this.picked = this.allPicked ? [] : this.matched.map(p => p.code); },
            setTab(k) { this.tab = k; this.q = ''; this.step = ''; this.limit = 25; this.picked = []; },
            sort(codes) {
                let d = 0, t = 0;
                const time = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
                this.parcels.forEach(p => {
                    if (codes.includes(p.code) && p.stage === 'awaiting_sorting') { p.stage = 'sorted'; p.sortedAt = time; p.next === 'delivery' ? d++ : t++; }
                });
                this.picked = [];
                const n = d + t;
                if (!n) return;
                const parts = [];
                if (d) parts.push(d + ' to the Delivery Queue');
                if (t) parts.push(t + ' waiting for a transfer to ' + this.hub);
                this.$dispatch('lg-toast', { type: 'success', message: n + (n === 1 ? ' parcel' : ' parcels') + ' sorted: ' + parts.join(' and ') + '.' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Sorting</h1>
                <p>Received parcels and where they go next. The next step comes from the order, so you do not choose a destination here.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.incoming') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="inbox" :size="18" /> Incoming parcels</a>
            </div>
        </div>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
            <a href="{{ route('lgp.station.delivery-queue') }}" class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--amber"><x-logistics.icon name="map-pin" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Going to the delivery queue</p>
                    <p class="lg-stat__value" x-text="waiting('delivery')">0</p>
                </div>
                <x-logistics.icon name="arrow-right" :size="18" class="lg-stat__go" />
            </a>
            <a href="{{ route('lgp.station.outgoing') }}" class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--blue"><x-logistics.icon name="truck" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Going to {{ $hub }}</p>
                    <p class="lg-stat__value" x-text="waiting('transfer')">0</p>
                </div>
                <x-logistics.icon name="arrow-right" :size="18" class="lg-stat__go" />
            </a>
        </div>

        <div class="lg-note">
            <x-logistics.icon name="info" :size="18" />
            <span>Parcels for another town wait in <a href="{{ route('lgp.station.outgoing') }}">Outgoing Transfers</a> until the Province Hub schedules a trip. This station never picks the trip, driver or vehicle.</span>
        </div>

        <nav class="lg-tabs" aria-label="Sorting stage">
            @foreach (['awaiting_sorting' => 'Awaiting sorting', 'sorted' => 'Sorted today'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="setTab('{{ $k }}')">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <p class="lg-section-sub" x-text="helps[tab]"></p>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Tracking number, seller, rider or area" aria-label="Search parcels">
            </div>
            <label class="sr-only" for="srtStep">Next step</label>
            <select id="srtStep" class="lg-select" x-model="step" @change="limit = 25">
                <option value="">All next steps</option>
                <option value="delivery">Delivery queue</option>
                <option value="transfer">Transfer to {{ $hub }}</option>
            </select>
            <button type="button" class="lg-btn lg-btn--sm" x-show="canAct" x-cloak :disabled="!picked.length" @click="sort(picked)"
                    x-text="'Mark sorted' + (picked.length ? ' (' + picked.length + ')' : '')"></button>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="matched.length + (matched.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <section class="lg-card" aria-label="Parcels">
            <div class="lg-table-wrap" x-show="matched.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th x-show="canAct" style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll()" aria-label="Select all parcels in this list"></th>
                            <th>Parcel</th><th>Brought by</th><th>Received</th><th>Next step</th><th>Status</th>
                            <th class="is-actions" x-show="canAct">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr :class="{ 'is-selected': isPicked(p) }">
                                <td x-show="canAct" class="is-check"><input type="checkbox" class="lg-check" :checked="isPicked(p)" @change="toggle(p)" :aria-label="'Select ' + p.code"></td>
                                <td data-label="Parcel"><div class="lg-cell-title" x-text="p.code"></div><div class="lg-cell-sub" x-text="p.seller"></div></td>
                                <td data-label="Brought by" x-text="p.rider"></td>
                                <td data-label="Received" x-text="p.time"></td>
                                <td data-label="Next step">
                                    <span class="lg-pill" :class="p.next === 'delivery' ? 'lg-pill--teal' : 'lg-pill--blue'" x-text="p.next === 'delivery' ? 'Delivery queue' : 'Transfer to ' + hub"></span>
                                    <div class="lg-cell-sub" x-text="p.next === 'delivery' ? 'Brgy. ' + p.to : 'For ' + p.to"></div>
                                </td>
                                <td data-label="Status">
                                    <span class="lg-pill" :class="p.stage === 'sorted' ? 'lg-pill--green' : 'lg-pill--violet'"
                                          x-text="p.stage === 'sorted' ? (p.next === 'delivery' ? 'Ready for delivery' : 'Waiting for transfer') : 'Awaiting sorting'"></span>
                                </td>
                                <td data-label="Action" class="is-actions" x-show="canAct">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="sort([p.code])" :aria-label="'Mark ' + p.code + ' sorted'">Mark sorted</button>
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

            <x-logistics.empty x-show="!matched.length && !q.trim() && !step && tab === 'awaiting_sorting'" x-cloak icon="check-circle" title="Everything is sorted" text="Parcels you scan in under Incoming Parcels will appear here.">
                <a href="{{ route('lgp.station.incoming') }}" class="lg-btn lg-btn--outline lg-btn--sm">Go to Incoming Parcels</a>
            </x-logistics.empty>
            <x-logistics.empty x-show="!matched.length && !q.trim() && !step && tab === 'sorted'" x-cloak icon="layers" title="Nothing sorted yet today" text="Parcels you mark sorted are listed here." />
            <x-logistics.empty x-show="!matched.length && (q.trim() || step)" x-cloak icon="search" title="No parcels match" text="Check the spelling, change the next step, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; step = ''">Clear filters</button>
            </x-logistics.empty>
        </section>
    </div>
</x-logistics.shell>
