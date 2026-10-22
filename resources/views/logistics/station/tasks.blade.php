{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec sections 8 (Station Hub > Task Assignment) and 6A (rider tasks).
     Every task that still needs a rider, pickups and deliveries together. Only the Station Hub creates or changes a task; riders never claim one.
     The page is rider-centric: pick tasks, choose one rider, assign. Delivery Queue (not built yet) is the same work seen by barangay.
     $cap is a demo constant, not a business rule. Interactions change page state only; nothing is saved. --}}
@php
    $typeCounts = collect($tasks)->countBy('type');
    $requested = request('type');
    $initialTab = in_array($requested, ['pickup', 'delivery'], true) ? $requested : (($typeCounts['delivery'] ?? 0) ? 'delivery' : 'pickup');
    $tabs = [
        'delivery' => ['Deliveries', 'No parcels are waiting for a rider', 'Parcels you mark sorted for local delivery in Sorting appear here.', route('lgp.station.sorting'), 'Open Sorting'],
        'pickup' => ['Pickups', 'Every pickup has a rider', 'New seller pickup requests for this station appear here until you assign a rider.', route('lgp.station.pickups'), 'Open Pickup Requests'],
    ];
    $statusTone = ['Available' => 'green', 'On route' => 'blue', 'Off duty' => 'gray'];
@endphp
<x-logistics.shell title="Task Assignment" role="station">
    <div class="lg-page"
         x-data="{
            cap: {{ (int) $cap }},
            tasks: @js($tasks),
            riders: @js($riders),
            tab: '{{ $initialTab }}',
            q: '',
            brgy: '',
            limit: 25,
            picked: [],
            rider: '',
            load(r) { return r.pickup + r.delivery; },
            pct(r) { return Math.min(100, Math.round(this.load(r) / this.cap * 100)); },
            tone(r) { return this.load(r) >= this.cap ? 'danger' : (this.load(r) / this.cap >= 0.8 ? 'warn' : 'ok'); },
            canTake(r) { return r.status !== 'Off duty' && this.load(r) < this.cap; },
            serves(r, barangay) { return r.barangays.split(', ').includes(barangay); },
            count(type) { return this.tasks.filter(t => t.type === type).length; },
            get typeTasks() { return this.tasks.filter(t => t.type === this.tab); },
            get barangays() { return [...new Set(this.typeTasks.map(t => t.barangay))].sort(); },
            get matched() {
                const s = this.q.trim().toLowerCase();
                return this.typeTasks.filter(t => (!this.brgy || t.barangay === this.brgy)
                    && (!s || [t.code, t.party, t.place].some(v => v.toLowerCase().includes(s))));
            },
            get shown() { return this.matched.slice(0, this.limit); },
            get pickedTasks() { return this.tasks.filter(t => this.picked.includes(t.id)); },
            get matchedPicked() { return this.matched.filter(t => this.picked.includes(t.id)); },
            get allPicked() { return this.matched.length > 0 && this.matchedPicked.length === this.matched.length; },
            get chosen() { return this.riders.find(r => String(r.id) === String(this.rider)); },
            get room() { return this.chosen ? this.cap - this.load(this.chosen) : 0; },
            get tooMany() { return !!this.chosen && this.picked.length > this.room; },
            get outside() {
                if (!this.chosen) return [];
                return [...new Set(this.pickedTasks.filter(t => !this.serves(this.chosen, t.barangay)).map(t => t.barangay))].sort();
            },
            get riderOptions() {
                const areas = [...new Set(this.pickedTasks.map(t => t.barangay))];
                return this.riders
                    .map(r => ({ ...r, ok: this.canTake(r), fits: areas.length > 0 && areas.every(a => this.serves(r, a)) }))
                    .sort((a, b) => (b.ok - a.ok) || (b.fits - a.fits) || (this.load(a) - this.load(b)));
            },
            isPicked(t) { return this.picked.includes(t.id); },
            toggle(t) { this.isPicked(t) ? this.picked = this.picked.filter(id => id !== t.id) : this.picked.push(t.id); },
            toggleAll() { this.picked = this.allPicked ? this.picked.filter(id => !this.matched.some(t => t.id === id)) : [...new Set([...this.picked, ...this.matched.map(t => t.id)])]; },
            clearPicked() { this.picked = []; this.rider = ''; },
            setTab(k) { this.tab = k; this.q = ''; this.brgy = ''; this.limit = 25; this.clearPicked(); },
            assign() {
                if (!this.chosen || !this.picked.length || this.tooMany) return;
                const r = this.chosen, type = this.tab, n = this.picked.length;
                r[type] += n;
                this.tasks = this.tasks.filter(t => !this.picked.includes(t.id));
                this.clearPicked();
                if (this.brgy && !this.barangays.includes(this.brgy)) this.brgy = '';
                const noun = type === 'pickup' ? (n === 1 ? 'pickup' : 'pickups') : (n === 1 ? 'delivery' : 'deliveries');
                this.$dispatch('lg-toast', { type: 'success', message: n + ' ' + noun + ' assigned to ' + r.name + '. ' + (n === 1 ? 'It is' : 'They are') + ' now in their task list.' });
                if (!this.count(this.tab)) { const other = this.tab === 'delivery' ? 'pickup' : 'delivery'; if (this.count(other)) this.setTab(other); }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Task Assignment</h1>
                <p>Give pickup and delivery tasks to riders. Tick the tasks, choose one rider and assign. Only this station assigns tasks; riders cannot claim them.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.pickups') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="inbox" :size="18" /> Pickup requests</a>
            </div>
        </div>

        <section class="lg-card" aria-labelledby="tkRiders">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="tkRiders">Riders</h2>
                    <p class="lg-section-sub">Every rider can do pickups and deliveries. Off-duty and full riders cannot take more tasks.</p>
                </div>
                <a href="{{ route('lgp.station.riders') }}" class="lg-btn lg-btn--ghost lg-btn--sm">Manage riders</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Rider</th><th>Status</th><th>Barangays</th><th class="is-num">Pickup</th><th class="is-num">Delivery</th><th>Workload</th></tr></thead>
                    <tbody>
                        <template x-for="r in riders" :key="r.id">
                            <tr>
                                <td data-label="Rider">
                                    <div class="lg-person">
                                        <span class="lg-avatar lg-avatar--sm" aria-hidden="true" x-text="r.name.charAt(0)"></span>
                                        <div><strong x-text="r.name"></strong><div class="lg-cell-sub" x-text="r.vehicle"></div></div>
                                    </div>
                                </td>
                                <td data-label="Status"><span class="lg-pill" :class="{ 'lg-pill--green': r.status === 'Available', 'lg-pill--blue': r.status === 'On route', 'lg-pill--gray': r.status === 'Off duty' }" x-text="r.status"></span></td>
                                <td data-label="Barangays" x-text="r.barangays"></td>
                                <td data-label="Pickup" class="is-num" x-text="r.pickup"></td>
                                <td data-label="Delivery" class="is-num" x-text="r.delivery"></td>
                                <td data-label="Workload">
                                    <div class="lg-meter-row">
                                        <div class="lg-meter-row__top">
                                            <span x-text="tone(r) === 'ok' ? 'Has room' : (tone(r) === 'warn' ? 'Near limit' : 'Full')"></span>
                                            <strong x-text="load(r) + ' of ' + cap"></strong>
                                        </div>
                                        <div class="lg-meter" :class="'lg-meter--' + tone(r)" role="progressbar" aria-valuemin="0" :aria-valuemax="cap" :aria-valuenow="load(r)" :aria-label="r.name + ' workload'">
                                            <span :style="'--w:' + pct(r) + '%'"></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <nav class="lg-tabs" aria-label="Task type">
            @foreach ($tabs as $k => [$label])
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="setTab('{{ $k }}')">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')">{{ $typeCounts[$k] ?? 0 }}</span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Tracking number, seller or address" aria-label="Search tasks">
            </div>
            <label class="sr-only" for="tkBrgy">Barangay</label>
            <select id="tkBrgy" class="lg-select" x-model="brgy" @change="limit = 25">
                <option value="">All barangays</option>
                <template x-for="b in barangays" :key="b"><option :value="b" x-text="b"></option></template>
            </select>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="matched.length + (matched.length === 1 ? ' task' : ' tasks') + ' need a rider'"></span>
        </div>

        <div class="lg-note" x-show="tab === 'pickup'" x-cloak>
            <x-logistics.icon name="info" :size="18" />
            <span>You can also assign or reassign a single pickup in <a href="{{ route('lgp.station.pickups') }}">Pickup Requests</a>. A delivery task is created when a parcel is sorted for this station's barangays.</span>
        </div>

        <section class="lg-card" aria-label="Tasks that need a rider">
            <div class="lg-table-wrap" x-show="matched.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll()"
                                x-effect="$el.indeterminate = matchedPicked.length > 0 && !allPicked" aria-label="Select all tasks in this list"></th>
                            <th>Tracking number</th>
                            <th x-text="tab === 'pickup' ? 'Seller' : 'Brought in by'"></th>
                            <th x-text="tab === 'pickup' ? 'Pickup address' : 'Delivery area'"></th>
                            <th>Waiting</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="t in shown" :key="t.id">
                            <tr :class="{ 'is-selected': isPicked(t) }">
                                <td class="is-check"><input type="checkbox" class="lg-check" :checked="isPicked(t)" @change="toggle(t)" :aria-label="'Select ' + t.code"></td>
                                <td data-label="Tracking number"><strong x-text="t.code"></strong></td>
                                <td data-label="Party" x-text="t.party"></td>
                                <td data-label="Where" x-text="t.place"></td>
                                <td data-label="Waiting"><span x-text="t.since"></span><div class="lg-cell-sub" x-text="t.ago"></div></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="lg-toolbar" style="justify-content:center;padding:12px" x-show="matched.length > limit" x-cloak>
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="limit += 25" x-text="'Show 25 more (' + (matched.length - limit) + ' left)'"></button>
                </div>
            </div>

            @foreach ($tabs as $k => [$label, $emptyTitle, $emptyText, $emptyUrl, $emptyCta])
                <x-logistics.empty x-show="tab === '{{ $k }}' && !typeTasks.length" x-cloak icon="check-circle" :title="$emptyTitle" :text="$emptyText">
                    <a href="{{ $emptyUrl }}" class="lg-btn lg-btn--outline lg-btn--sm">{{ $emptyCta }}</a>
                </x-logistics.empty>
            @endforeach
            <x-logistics.empty x-show="typeTasks.length && !matched.length" x-cloak icon="search" title="No tasks match" text="Check the spelling, change the barangay, or look in the other tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; brgy = ''">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- The rider is required before Assign. Riders who serve every selected barangay are listed first, then the lightest workload. --}}
        <div class="lg-bulkbar" x-show="picked.length" x-cloak x-transition.opacity.duration.150ms role="region" aria-label="Assign selected tasks">
            <span class="lg-bulkbar__count" x-text="picked.length + ' selected'"></span>
            <span class="lg-bulkbar__meta" x-show="tooMany" x-cloak x-text="chosen ? chosen.name + ' only has room for ' + room + '. Pick fewer tasks or another rider.' : ''"></span>
            <span class="lg-bulkbar__meta" x-show="!tooMany && outside.length" x-cloak x-text="chosen ? chosen.name + ' does not serve ' + outside.join(', ') + '. You can still assign.' : ''"></span>
            <span class="lg-bulkbar__spacer"></span>
            <label class="sr-only" for="tkRider">Rider</label>
            <select id="tkRider" class="lg-select" x-model="rider">
                <option value="">Choose a rider</option>
                <template x-for="r in riderOptions" :key="r.id">
                    <option :value="r.id" :disabled="!r.ok" x-text="r.name + ' (' + load(r) + '/' + cap + ')' + (r.status === 'Off duty' ? ', off duty' : (load(r) >= cap ? ', full' : (r.fits ? ', serves this area' : '')))"></option>
                </template>
            </select>
            <button type="button" class="lg-btn" :disabled="!chosen || tooMany" @click="assign()" x-text="'Assign ' + picked.length"></button>
            <button type="button" class="lg-btn lg-btn--ghost" @click="clearPicked()">Clear</button>
        </div>
    </div>
</x-logistics.shell>
