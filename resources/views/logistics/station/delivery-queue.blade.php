{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php and routes/logistics_preview_station.php. Spec section 8 (Station Hub > Delivery Queue).
     Ready for delivery is grouped by barangay with counts; tick parcels (or a whole barangay), choose one rider, assign. The two other tabs are what the
     dashboard links to: parcels already with riders (read only; riders scan out and report delivery in the mobile app) and failed deliveries, which are a
     Station Hub decision: Retry puts the parcel back in the queue for a new delivery task, Return to seller needs a reason.
     $cap is a demo constant, not a business rule. Interactions change page state only; nothing is saved. --}}
@php
    $requested = request('tab');
    $initialTab = in_array($requested, ['ready', 'out', 'failed'], true) ? $requested : 'ready';
    $tabs = ['ready' => 'Ready for delivery', 'out' => 'Out for delivery', 'failed' => 'Failed'];
@endphp
<x-logistics.shell title="Delivery Queue" role="station">
    <div class="lg-page"
         x-data="{
            cap: {{ (int) $cap }},
            ready: @js($ready),
            out: @js($withRiders),
            failed: @js($failed),
            riders: @js($riders),
            tab: '{{ $initialTab }}',
            q: '',
            outRider: '',
            limit: 25,
            picked: [],
            rider: '',
            open: false,
            target: null,
            reason: '',
            err: false,
            load(r) { return r.pickup + r.delivery; },
            canTake(r) { return r.status !== 'Off duty' && this.load(r) < this.cap; },
            serves(r, barangay) { return r.barangays.split(', ').includes(barangay); },
            search(items, keys) {
                const s = this.q.trim().toLowerCase();
                return items.filter(i => !s || keys.some(k => String(i[k]).toLowerCase().includes(s)));
            },
            get matchedReady() { return this.search(this.ready, ['code', 'seller', 'barangay']); },
            get groups() {
                const by = {};
                this.matchedReady.forEach(p => { (by[p.barangay] = by[p.barangay] || []).push(p); });
                return Object.keys(by).map(b => {
                    const servers = this.riders.filter(r => this.serves(r, b));
                    return { barangay: b, items: by[b], servers, onDuty: servers.some(r => r.status !== 'Off duty') };
                }).sort((a, b) => (b.items.length - a.items.length) || a.barangay.localeCompare(b.barangay));
            },
            get outRiders() { return [...new Set(this.out.map(o => o.rider))].sort(); },
            get matchedOut() { return this.search(this.out, ['code', 'barangay', 'rider']).filter(o => !this.outRider || o.rider === this.outRider); },
            get shownOut() { return this.matchedOut.slice(0, this.limit); },
            get matchedFailed() { return this.search(this.failed, ['code', 'seller', 'barangay', 'rider']); },
            get pickedItems() { return this.ready.filter(p => this.picked.includes(p.code)); },
            get chosen() { return this.riders.find(r => String(r.id) === String(this.rider)); },
            get room() { return this.chosen ? this.cap - this.load(this.chosen) : 0; },
            get tooMany() { return !!this.chosen && this.picked.length > this.room; },
            get outside() {
                if (!this.chosen) return [];
                return [...new Set(this.pickedItems.filter(p => !this.serves(this.chosen, p.barangay)).map(p => p.barangay))].sort();
            },
            get riderOptions() {
                const areas = [...new Set(this.pickedItems.map(p => p.barangay))];
                return this.riders
                    .map(r => ({ ...r, ok: this.canTake(r), fits: areas.length > 0 && areas.every(a => this.serves(r, a)) }))
                    .sort((a, b) => (b.ok - a.ok) || (b.fits - a.fits) || (this.load(a) - this.load(b)));
            },
            isPicked(p) { return this.picked.includes(p.code); },
            toggle(p) { this.isPicked(p) ? this.picked = this.picked.filter(c => c !== p.code) : this.picked.push(p.code); },
            groupAll(g) { return g.items.every(p => this.isPicked(p)); },
            groupSome(g) { return g.items.some(p => this.isPicked(p)); },
            toggleGroup(g) {
                const codes = g.items.map(p => p.code);
                this.picked = this.groupAll(g) ? this.picked.filter(c => !codes.includes(c)) : [...new Set([...this.picked, ...codes])];
            },
            clearPicked() { this.picked = []; this.rider = ''; },
            setTab(k) { this.tab = k; this.q = ''; this.outRider = ''; this.limit = 25; this.clearPicked(); },
            assign() {
                if (!this.chosen || !this.picked.length || this.tooMany) return;
                const r = this.chosen, n = this.picked.length, codes = [...this.picked];
                this.pickedItems.forEach(p => this.out.unshift({ code: p.code, barangay: p.barangay, rider_id: r.id, rider: r.name, since: 'Assigned just now' }));
                r.delivery += n;
                this.ready = this.ready.filter(p => !codes.includes(p.code));
                this.clearPicked();
                this.$dispatch('lg-toast', { type: 'success', message: n + (n === 1 ? ' parcel' : ' parcels') + ' assigned to ' + r.name + '. ' + (n === 1 ? 'It is' : 'They are') + ' now in their task list.' });
            },
            retry(f) {
                this.failed = this.failed.filter(x => x.code !== f.code);
                this.ready.unshift({ code: f.code, seller: f.seller, barangay: f.barangay, since: 'Retry requested just now', ago: 'Retry', retry: true });
                this.$dispatch('lg-toast', { type: 'success', message: f.code + ' is back in Ready for delivery. Assign a rider to try again.' });
                if (!this.failed.length && this.tab === 'failed') this.setTab('ready');
            },
            ask(f) {
                this.target = f; this.reason = ''; this.err = false; this.open = true;
                this.$nextTick(() => this.$refs.reason && this.$refs.reason.focus());
            },
            giveBack() {
                if (this.reason.trim().length < 5) { this.err = true; return; }
                const f = this.target;
                this.failed = this.failed.filter(x => x.code !== f.code);
                this.open = false;
                this.$dispatch('lg-toast', { type: 'info', message: f.code + ' is going back to ' + f.seller + ' with your reason.' });
                if (!this.failed.length && this.tab === 'failed') this.setTab('ready');
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Delivery Queue</h1>
                <p>Parcels sorted for this station's barangays, grouped by barangay. Tick the parcels, choose one rider and assign.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.tasks', ['type' => 'delivery']) }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="bike" :size="18" /> Task assignment</a>
            </div>
        </div>

        <div class="lg-note lg-note--warn" x-show="failed.length" x-cloak role="status">
            <x-logistics.icon name="alert" :size="18" />
            <span><strong x-text="failed.length"></strong> failed <span x-text="failed.length === 1 ? 'delivery needs' : 'deliveries need'"></span> a decision. <button type="button" class="lg-btn lg-btn--ghost lg-btn--sm" @click="setTab('failed')">Review</button></span>
        </div>

        <nav class="lg-tabs" aria-label="Delivery stage">
            @foreach ($tabs as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="setTab('{{ $k }}')">
                    {{ $label }} <span class="lg-tab__count" x-text="{{ $k }}.length"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Tracking number, seller, barangay or rider" aria-label="Search parcels">
            </div>
            <label class="sr-only" for="dqRider">Rider</label>
            <select id="dqRider" class="lg-select" x-show="tab === 'out'" x-cloak x-model="outRider" @change="limit = 25">
                <option value="">All riders</option>
                <template x-for="n in outRiders" :key="n"><option :value="n" x-text="n"></option></template>
            </select>
            <span class="lg-toolbar__meta" aria-live="polite"
                  x-text="tab === 'ready' ? (matchedReady.length + (matchedReady.length === 1 ? ' parcel in ' : ' parcels in ') + groups.length + (groups.length === 1 ? ' barangay' : ' barangays')) : (tab === 'out' ? matchedOut.length + (matchedOut.length === 1 ? ' parcel' : ' parcels') : matchedFailed.length + (matchedFailed.length === 1 ? ' parcel' : ' parcels'))"></span>
        </div>

        {{-- Ready for delivery: one card per barangay --}}
        <div x-show="tab === 'ready'" x-cloak style="display:grid;gap:16px">
            <template x-for="g in groups" :key="g.barangay">
                <section class="lg-card" :aria-label="'Barangay ' + g.barangay">
                    <div class="lg-card__head">
                        <label class="lg-person" style="cursor:pointer">
                            <input type="checkbox" class="lg-check" :checked="groupAll(g)" @change="toggleGroup(g)"
                                   x-effect="$el.indeterminate = groupSome(g) && !groupAll(g)" :aria-label="'Select all parcels for Brgy. ' + g.barangay">
                            <span>
                                <strong x-text="'Brgy. ' + g.barangay"></strong>
                                <small x-text="g.servers.length ? 'Served by ' + g.servers.map(r => r.name + ' (' + r.status.toLowerCase() + ')').join(', ') : 'No rider is set up for this barangay'"></small>
                            </span>
                        </label>
                        <span class="lg-pill lg-pill--amber" x-show="!g.onDuty">No rider on duty</span>
                        <span class="lg-pill lg-pill--teal" x-text="g.items.length + (g.items.length === 1 ? ' parcel' : ' parcels')"></span>
                    </div>
                    <div class="lg-table-wrap">
                        <table class="lg-table lg-table--stack lg-table--mid">
                            <thead><tr><th style="width:44px"><span class="sr-only">Select</span></th><th>Tracking number</th><th>Seller</th><th>Waiting</th></tr></thead>
                            <tbody>
                                <template x-for="p in g.items" :key="p.code">
                                    <tr :class="{ 'is-selected': isPicked(p) }">
                                        <td class="is-check"><input type="checkbox" class="lg-check" :checked="isPicked(p)" @change="toggle(p)" :aria-label="'Select ' + p.code"></td>
                                        <td data-label="Tracking number"><strong x-text="p.code"></strong> <span class="lg-pill lg-pill--amber" x-show="p.retry">Retry</span></td>
                                        <td data-label="Seller" x-text="p.seller"></td>
                                        <td data-label="Waiting"><span x-text="p.since"></span><div class="lg-cell-sub" x-text="p.ago"></div></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <section class="lg-card" x-show="!ready.length" x-cloak>
                <x-logistics.empty icon="check-circle" title="No parcels are ready for delivery" text="Parcels you mark sorted for this station's barangays in Sorting appear here.">
                    <a href="{{ route('lgp.station.sorting') }}" class="lg-btn lg-btn--outline lg-btn--sm">Open Sorting</a>
                </x-logistics.empty>
            </section>
            <section class="lg-card" x-show="ready.length && !groups.length" x-cloak>
                <x-logistics.empty icon="search" title="No parcels match" text="Check the spelling or search for another barangay.">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''">Clear search</button>
                </x-logistics.empty>
            </section>
        </div>

        {{-- Out for delivery: with riders, read only --}}
        <section class="lg-card" x-show="tab === 'out'" x-cloak aria-label="Parcels with riders">
            <div class="lg-note">
                <x-logistics.icon name="info" :size="18" />
                <span>These parcels are with riders. Riders scan them out and report each delivery in the mobile app, so this tab only lets you watch. A failed delivery appears under Failed.</span>
            </div>
            <div class="lg-table-wrap" x-show="matchedOut.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Tracking number</th><th>Delivery area</th><th>Rider</th><th>Assigned</th></tr></thead>
                    <tbody>
                        <template x-for="o in shownOut" :key="o.code">
                            <tr>
                                <td data-label="Tracking number"><strong x-text="o.code"></strong></td>
                                <td data-label="Delivery area" x-text="'Brgy. ' + o.barangay"></td>
                                <td data-label="Rider" x-text="o.rider"></td>
                                <td data-label="Assigned" x-text="o.since"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="lg-toolbar" style="justify-content:center;padding:12px" x-show="matchedOut.length > limit" x-cloak>
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="limit += 25" x-text="'Show 25 more (' + (matchedOut.length - limit) + ' left)'"></button>
                </div>
            </div>
            <x-logistics.empty x-show="!out.length" x-cloak icon="truck" title="No parcels are out for delivery" text="Parcels you assign to a rider show here until they are delivered." />
            <x-logistics.empty x-show="out.length && !matchedOut.length" x-cloak icon="search" title="No parcels match" text="Check the spelling or change the rider.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; outRider = ''">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Failed: Retry or Return to seller --}}
        <section class="lg-card" x-show="tab === 'failed'" x-cloak aria-label="Failed deliveries">
            <div class="lg-table-wrap" x-show="matchedFailed.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Tracking number</th><th>Seller</th><th>Delivery area</th><th>Rider</th><th>What happened</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="f in matchedFailed" :key="f.code">
                            <tr>
                                <td data-label="Tracking number"><strong x-text="f.code"></strong></td>
                                <td data-label="Seller" x-text="f.seller"></td>
                                <td data-label="Delivery area" x-text="'Brgy. ' + f.barangay"></td>
                                <td data-label="Rider" x-text="f.rider"></td>
                                <td data-label="What happened">
                                    <span class="lg-pill lg-pill--red" x-text="f.reason"></span>
                                    <div class="lg-cell-sub" x-text="'Attempt ' + f.attempts + ' at ' + f.at"></div>
                                </td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <a :href="'{{ route('lgp.station.tracking') }}?q=' + encodeURIComponent(f.code)" class="lg-btn lg-btn--ghost lg-btn--sm" :aria-label="'See the journey of ' + f.code">Journey</a>
                                        <button type="button" class="lg-btn lg-btn--sm" @click="retry(f)" :aria-label="'Retry ' + f.code"><x-logistics.icon name="refresh" :size="16" /> Retry</button>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="ask(f)" :aria-label="'Return ' + f.code + ' to the seller'"><x-logistics.icon name="undo" :size="16" /> Return to seller</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!failed.length" x-cloak icon="check-circle" title="No failed deliveries" text="A delivery a rider could not complete shows here until you retry it or return it to the seller." />
            <x-logistics.empty x-show="failed.length && !matchedFailed.length" x-cloak icon="search" title="No parcels match" text="Check the spelling or clear the search.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''">Clear search</button>
            </x-logistics.empty>
        </section>

        {{-- Assign bar (Ready tab). The rider is required first. Riders who serve every selected barangay are listed first, then the lightest workload. --}}
        <div class="lg-bulkbar" x-show="tab === 'ready' && picked.length" x-cloak x-transition.opacity.duration.150ms role="region" aria-label="Assign selected parcels">
            <span class="lg-bulkbar__count" x-text="picked.length + ' selected'"></span>
            <span class="lg-bulkbar__meta" x-show="tooMany" x-cloak x-text="chosen ? chosen.name + ' only has room for ' + room + '. Pick fewer parcels or another rider.' : ''"></span>
            <span class="lg-bulkbar__meta" x-show="!tooMany && outside.length" x-cloak x-text="chosen ? chosen.name + ' does not serve ' + outside.join(', ') + '. You can still assign.' : ''"></span>
            <span class="lg-bulkbar__spacer"></span>
            <label class="sr-only" for="dqAssignRider">Rider</label>
            <select id="dqAssignRider" class="lg-select" x-model="rider">
                <option value="">Choose a rider</option>
                <template x-for="r in riderOptions" :key="r.id">
                    <option :value="r.id" :disabled="!r.ok" x-text="r.name + ' (' + load(r) + '/' + cap + ')' + (r.status === 'Off duty' ? ', off duty' : (load(r) >= cap ? ', full' : (r.fits ? ', serves this area' : '')))"></option>
                </template>
            </select>
            <button type="button" class="lg-btn" :disabled="!chosen || tooMany" @click="assign()" x-text="'Assign ' + picked.length"></button>
            <button type="button" class="lg-btn lg-btn--ghost" @click="clearPicked()">Clear</button>
        </div>

        {{-- Return to seller: a reason of 5+ characters is required --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="dqTitle">
                <form @submit.prevent="giveBack()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="dqTitle">Return to seller</h3>
                            <p x-text="target ? target.code + ' goes back to ' + target.seller + '.' : ''"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="dqReason">Why is it going back? <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="dqReason" class="lg-textarea" x-ref="reason" x-model="reason" maxlength="500" rows="3"
                                      :aria-invalid="err" aria-describedby="dqErr" placeholder="For example: the buyer could not be reached after two attempts."></textarea>
                            <p class="lg-error" id="dqErr" x-show="err" x-cloak role="alert"><x-logistics.icon name="alert" :size="14" /> Enter at least 5 characters so the seller knows why.</p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Return to seller</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
