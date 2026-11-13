{{--
    Station Hub > Driver assignments. The Station Hub assigns one of the Transport Drivers linked to it to a trip and names the Province Hub
    or Station Hub that driver must reach. Saving creates a scheduled trip, the same trip the driver sees in the Driver app.
    Fed by PortalController::stationTrips (trips from tripCard, destinations, drivers, vehicles, the parcels ready per destination, and the JSON urls).
    The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Transport Trips. Styles: hubs-theme.css, drivers-theme.css
    (pills, callouts, facts, details dialog), trips-theme.css (select, manifest, steps) and station-trips-theme.css (assign dialog), loaded last.
    Dialogs sit in .lgh-layer and carry their own copy of the tokens.
    Status colors: Scheduled = butter (waiting), In transit = sky, Completed = mint, Cancelled = rose.
    Status chips with counts; search runs as you type. One click on a row opens the trip.
    Each row has a visible primary button: Record departure for a scheduled trip that leaves here, Record arrival for a trip in transit that arrives here, otherwise View details.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no trips at all, nothing in a status, and no trips match the search. Errors: under each field, inside the dialog that was open, or in the top banner.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/trips-theme.css', 'resources/css/logistics/station-trips-theme.css'])
@endpush

<x-logistics.shell title="Driver Assignments" role="station">
    <div class="lgh"
         x-data="{
            trips: @js($trips),
            destinations: @js($destinations),
            drivers: @js($drivers),
            vehicles: @js($vehicles),
            parcelMap: @js($parcels),
            now: @js($now),
            storeUrl: @js($storeUrl),
            cancelUrl: @js($cancelUrl),
            departUrl: @js($departUrl),
            arriveUrl: @js($arriveUrl),
            trackingUrl: @js($trackingUrl),
            driversUrl: @js($driversUrl),
            defaults: { departure: @js($defaultDeparture), arrival: @js($defaultArrival) },
            q: '',
            tab: 'all',
            view: null,
            alert: '',
            working: false,
            stale: false,
            pq: '',
            confirm: { open: false, t: null, reason: '', error: '', alert: '' },
            form: { open: false, destination: '', driver: '', vehicle: '', departure: '', arrival: '', selected: [], errors: {}, alert: '' },
            labels: { scheduled: 'Scheduled', in_transit: 'In transit', completed: 'Completed', cancelled: 'Cancelled' },
            tones: { scheduled: 'lgh-pill--butter', in_transit: 'lgh-pill--sky', completed: 'lgh-pill--mint', cancelled: 'lgh-pill--rose' },
            tabEmpty: {
                scheduled: ['No scheduled trips', 'A trip shows here from the moment you assign a driver until its departure is recorded.'],
                in_transit: ['No trips in transit', 'A trip shows here after its departure is recorded, until it arrives.'],
                completed: ['No completed trips', 'A trip shows here after its arrival is recorded.'],
                cancelled: ['No cancelled trips', 'A trip you cancel shows here, with your reason.']
            },
            init() {
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.view !== null || this.confirm.open || this.form.open, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
                // Arriving from the Drivers page (Assign trip) opens the form with that driver chosen.
                const wanted = @js($selectedDriver);
                if (wanted > 0) {
                    if (this.drivers.some(d => d.id === wanted) && !this.blocked) this.openForm(wanted);
                    else this.$nextTick(() => window.logisticsBanner && window.logisticsBanner({ type: 'warning', title: 'That driver can’t take a trip right now', message: 'Choose another driver, or wait until the driver’s current trip arrives.' }));
                }
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            headers(json) { const h = { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; if (json) h['Content-Type'] = 'application/json'; return h; },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            fallbackFor(status, action) {
                if (status === 419) return 'Your session expired. Reload the page and try again.';
                if (status === 403) return 'This account can’t change trips. Sign in with the Station Hub account.';
                if (status >= 500) return 'Something went wrong on our side. Try again in a moment.';
                return 'Could not ' + action + '. Reload the page and try again.';
            },
            async request(url, method, body, action) {
                const response = await fetch(url, { method: method, headers: this.headers(!!body), body: body ? JSON.stringify(body) : undefined });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const error = new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || this.fallbackFor(response.status, action));
                    error.payload = payload; error.status = response.status;
                    throw error;
                }
                return payload;
            },
            get needs() {
                const list = [];
                if (!this.destinations.length) list.push({ text: 'There is no other active hub in your network, so there is nowhere to send a driver. Ask your Province Hub to activate a hub.' });
                if (!this.drivers.length) list.push({ text: 'No Transport Driver is free to take a trip. Approve a driver, or wait until a driver on a trip arrives.', href: this.driversUrl, label: 'Open drivers' });
                if (!this.vehicles.length) list.push({ text: 'No vehicle from your Province Hub is free right now. Ask your Province Hub to add a vehicle or release one.' });
                return list;
            },
            get blocked() { return this.needs.length > 0; },
            rank(t) { return t.status === 'in_transit' ? 0 : (t.status === 'scheduled' ? 1 : 2); },
            get searching() { return this.q.trim() !== ''; },
            get filtered() { return this.searching || this.tab !== 'all'; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.trips
                    .filter(t => (this.tab === 'all' || t.status === this.tab)
                        && (!s || (t.id + ' ' + t.origin + ' ' + t.destination + ' ' + t.driver + ' ' + t.vehicle).toLowerCase().includes(s)))
                    // Running and upcoming trips first (soonest departure first), then past trips (latest first).
                    .sort((a, b) => this.rank(a) - this.rank(b) || (this.rank(a) < 2 ? (a.scheduled || '').localeCompare(b.scheduled || '') : (b.scheduled || '').localeCompare(a.scheduled || '')));
            },
            count(k) { return k === 'all' ? this.trips.length : this.trips.filter(t => t.status === k).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            fmt(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Not set'; },
            fmtDate(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            canDepart(t) { return t.status === 'scheduled' && t.can_depart; },
            canArrive(t) { return t.status === 'in_transit' && t.can_arrive; },
            go(t, event) { if (!event.target.closest('a, button')) this.openView(t); },
            openView(t) { this.alert = ''; this.view = t; },
            closeView() { this.view = null; },
            closeTop() { if (this.confirm.open) this.confirm.open = false; else if (this.form.open) this.form.open = false; else if (this.view) this.view = null; },

            // ---- Assign a driver ----
            get parcels() { return this.parcelMap[this.form.destination] || []; },
            get visible() { const s = this.pq.trim().toLowerCase(); return this.parcels.filter(p => !s || (p.tracking + ' ' + (p.number || '') + ' ' + (p.where || '') + ' ' + (p.buyer || '')).toLowerCase().includes(s)); },
            get chosen() { return this.parcels.filter(p => this.form.selected.includes(p.order)); },
            get hereParcels() { return this.parcels.filter(p => p.delivers_here); },
            get currentDestination() { return this.destinations.find(x => String(x.id) === String(this.form.destination)); },
            get destinationName() { return this.currentDestination ? this.currentDestination.name : 'the chosen hub'; },
            get destinationArea() { const d = this.currentDestination; return d ? (d.area || d.name) : 'the chosen hub'; },
            get parcelSummary() {
                const n = this.parcels.length;
                if (!n) return '';
                const here = this.hereParcels.length;
                return n + (n === 1 ? ' parcel is' : ' parcels are') + ' sorted here for ' + this.destinationName + '. ' + here + (here === 1 ? ' ends' : ' end') + ' in ' + this.destinationArea + '; the rest only pass through.';
            },
            openForm(driverId) {
                if (this.blocked) return;
                this.pq = '';
                this.form = { open: true, destination: '', driver: driverId ? String(driverId) : '', vehicle: '', departure: this.defaults.departure, arrival: this.defaults.arrival, selected: [], errors: {}, alert: '' };
                this.$nextTick(() => document.getElementById('assignDestination')?.focus());
            },
            closeForm() { this.form.open = false; },
            onDestination() { this.form.errors.destination_id = ''; this.form.errors.orders = ''; this.pq = ''; this.form.selected = this.parcels.map(p => p.order); },
            toggleAll(on) { this.form.errors.orders = ''; this.form.selected = on ? this.parcels.map(p => p.order) : []; },
            selectHere() { this.form.errors.orders = ''; this.form.selected = this.hereParcels.map(p => p.order); },
            clearError(key) { if (this.form.errors[key]) this.form.errors[key] = ''; },
            focusFirstError() {
                this.$nextTick(() => {
                    const el = document.querySelector('.lgh-dialog--assign [aria-invalid=true]') || document.getElementById('loadErr');
                    if (!el) return;
                    el.scrollIntoView({ block: 'center' });
                    if (el.tabIndex >= 0) el.focus();
                });
            },
            validate() {
                const f = this.form, e = {};
                if (!f.destination) e.destination_id = 'Hub is not chosen. Pick the hub the driver must reach.';
                if (!f.driver) e.driver_id = 'Driver is not chosen. Pick an available driver.';
                else {
                    const d = this.drivers.find(x => String(x.id) === String(f.driver));
                    if (d && f.departure && d.expiry < f.departure.slice(0, 10)) e.driver_id = d.name + '’s licence expires on ' + this.fmtDate(d.expiry) + ', before this departure. Pick another driver, or move the departure earlier.';
                }
                if (!f.vehicle) e.vehicle_id = 'Vehicle is not chosen. Pick an available vehicle.';
                if (!f.departure) e.departure = 'Departure is empty. Set when the trip leaves.';
                else if (f.departure < this.now) e.departure = 'Departure is in the past. Pick a time from now on.';
                if (!f.arrival) e.expected_arrival_at = 'Expected arrival is empty. Set when the trip should arrive.';
                else if (f.departure && f.arrival <= f.departure) e.expected_arrival_at = 'Expected arrival is before the departure. Pick a time after the departure.';
                if (f.destination && f.selected.length === 0) e.orders = 'No parcels are selected. Select at least one parcel to load.';
                f.errors = e;
                return Object.keys(e).length === 0;
            },
            fieldErrors(errors) {
                const known = ['destination_id', 'driver_id', 'vehicle_id', 'departure', 'expected_arrival_at', 'orders'], out = {};
                Object.keys(errors).forEach(key => { const base = key.split('.')[0]; if (known.includes(base) && !out[base]) out[base] = errors[key][0]; });
                return out;
            },
            async save(event) {
                const f = this.form;
                if (this.working) return;
                f.alert = '';
                if (!this.validate()) { this.focusFirstError(); return; }
                const button = event.submitter || event.currentTarget.querySelector('button[type=submit]');
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(this.storeUrl, 'POST', {
                        destination_id: Number(f.destination), driver_id: Number(f.driver), vehicle_id: Number(f.vehicle),
                        departure: f.departure, expected_arrival_at: f.arrival, orders: f.selected.map(Number)
                    }, 'assign the driver');
                    // The driver, the vehicle and the loaded parcels are taken now, so they leave the lists.
                    const taken = f.selected.map(Number);
                    this.drivers = this.drivers.filter(d => String(d.id) !== String(f.driver));
                    this.vehicles = this.vehicles.filter(v => String(v.id) !== String(f.vehicle));
                    this.parcelMap[f.destination] = (this.parcelMap[f.destination] || []).filter(p => !taken.includes(p.order));
                    this.trips.unshift(payload.trip);
                    f.open = false; this.tab = 'all'; this.q = '';
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) {
                    const errors = this.fieldErrors((error.payload && error.payload.errors) || {});
                    if (Object.keys(errors).length) { f.errors = errors; this.focusFirstError(); }
                    else f.alert = this.failure(error, 'Could not assign the driver. Try again.');
                } finally { this.working = false; this.busy(button, false); }
            },

            // ---- Cancel, depart, arrive ----
            askCancel(t) { this.confirm = { open: true, t: t, reason: '', error: '', alert: '' }; this.$nextTick(() => document.getElementById('tripReason')?.focus()); },
            async submitCancel(event) {
                const c = this.confirm, t = c.t;
                if (this.working) return;
                if (t.status !== 'scheduled') { c.alert = t.id + ' is no longer scheduled, so it can’t be cancelled. Close this and check its status.'; return; }
                if (c.reason.trim().length < 5) {
                    c.error = 'Reason is too short. Write at least 5 characters so the stations know why the trip was cancelled.';
                    document.getElementById('tripReason')?.focus();
                    return;
                }
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); c.error = ''; c.alert = '';
                try {
                    const payload = await this.request(this.cancelUrl.replace('__trip__', t.key), 'PATCH', { reason: c.reason.trim() }, 'cancel the trip');
                    Object.assign(t, payload.trip);
                    c.open = false; this.view = null; this.stale = true;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.alert = this.failure(error, 'Could not cancel the trip. Try again.'); }
                finally { this.working = false; this.busy(button, false); }
            },
            async changeStatus(t, action, event) {
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); this.alert = '';
                try {
                    const payload = await this.request((action === 'depart' ? this.departUrl : this.arriveUrl).replace('__trip__', t.key), 'PATCH', null, 'update the trip');
                    Object.assign(t, payload.trip);
                    if (action === 'arrive') this.stale = true;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) {
                    const message = this.failure(error, 'Could not update the trip. Try again.');
                    if (this.view === t) this.alert = message;
                    else this.$dispatch('lg-toast', { type: 'error', message: message });
                } finally { this.working = false; this.busy(button, false); }
            }
         }"
         @keydown.escape.window="closeTop()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Driver assignments</h1>
                <p>Assign a Transport Driver from {{ $station['name'] }} to a trip. The trip shows in the driver app right away.</p>
            </div>
            <button type="button" class="lgh-btn lgh-btn--primary" :disabled="blocked" @click="openForm(null)">
                <x-logistics.icon name="plus" :size="16" /> Assign driver
            </button>
        </header>

        {{-- Why Assign driver is off: something it needs is missing --}}
        <div class="lgh-callout lgh-callout--rose" x-show="blocked" x-cloak role="alert">
            <x-logistics.icon name="alert" :size="18" />
            <ul class="lgh-needs">
                <template x-for="need in needs" :key="need.text">
                    <li>
                        <span x-text="need.text"></span>
                        <a :href="need.href" x-show="need.href" x-text="need.label"></a>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Status chips with counts, then search --}}
        <div class="lgh-chips" role="group" aria-label="Filter trips by status" x-show="trips.length" x-cloak>
            @foreach (['all' => 'All', 'scheduled' => 'Scheduled', 'in_transit' => 'In transit', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $text)
                <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                    {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgh-chip lgh-chip--clear" x-show="filtered" x-cloak @click="clearFilters()">
                <x-logistics.icon name="x" :size="14" /> Clear filters
            </button>
        </div>

        <div class="lgh-toolbar" x-show="trips.length" x-cloak>
            <label class="lgh-search">
                <span class="lgh-sr">Search trips</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search trip, hub, driver or vehicle">
            </label>
            <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' trip' : ' trips')"></span>
        </div>

        <section class="lgh-card lgh-table-card" aria-label="Trips to and from this station">
            <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                <table class="lgh-table lgh-table--trips">
                    <thead>
                        <tr>
                            <th scope="col">Trip</th>
                            <th scope="col">Route</th>
                            <th scope="col">Driver and vehicle</th>
                            <th scope="col" class="is-num">Parcels</th>
                            <th scope="col">Departure</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgh-sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="t in shown" :key="t.id">
                            <tr class="lgh-row" @click="go(t, $event)">
                                <td>
                                    <button type="button" class="lgh-name lgh-name--btn" @click="openView(t)" x-text="t.id"></button>
                                    <small x-text="t.can_depart ? 'Sent from this station' : 'Arriving here'"></small>
                                </td>
                                <td data-label="Route">
                                    <span x-text="t.origin"></span>
                                    <small x-text="'to ' + t.destination"></small>
                                </td>
                                <td data-label="Driver and vehicle">
                                    <span x-text="t.driver"></span>
                                    <small x-text="t.vehicle"></small>
                                </td>
                                <td class="is-num" data-label="Parcels" x-text="t.parcels"></td>
                                <td data-label="Departure">
                                    <span x-text="fmt(t.departed || t.scheduled)"></span>
                                    <small x-text="t.departed ? 'Departed' : 'Scheduled'"></small>
                                </td>
                                <td data-label="Status"><span class="lgh-pill" :class="tones[t.status]" x-text="labels[t.status]"></span></td>
                                <td class="lgh-actions">
                                    <div class="lgh-actions__row">
                                        <template x-if="canDepart(t) || canArrive(t)">
                                            <button type="button" class="lgh-btn lgh-btn--small lgh-btn--primary" @click="changeStatus(t, canDepart(t) ? 'depart' : 'arrive', $event)"
                                                    :aria-label="(canDepart(t) ? 'Record departure of ' : 'Record arrival of ') + t.id">
                                                <span x-text="canDepart(t) ? 'Record departure' : 'Record arrival'"></span>
                                            </button>
                                        </template>
                                        <button type="button" class="lgh-btn lgh-btn--small" :class="(canDepart(t) || canArrive(t)) ? 'lgh-btn--ghost' : 'lgh-btn--soft'" @click="openView(t)" :aria-label="'View details for ' + t.id">
                                            View details <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- No trips at all --}}
            <div class="lgh-empty" x-show="!trips.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="calendar" :size="26" /></span>
                <strong>No driver assigned yet</strong>
                <p>Assign a driver to move sorted parcels to another hub. The trip shows here and in the driver app.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" :disabled="blocked" @click="openForm(null)"><x-logistics.icon name="plus" :size="16" /> Assign driver</button>
            </div>

            {{-- Nothing matches the search --}}
            <div class="lgh-empty" x-show="trips.length && !shown.length && searching" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                <strong>No trips match your search</strong>
                <p>Check the spelling, or search by trip, hub, driver or vehicle. Try another status too.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
            </div>

            {{-- Nothing in this status --}}
            <div class="lgh-empty" x-show="trips.length && !shown.length && !searching && tab !== 'all'" x-cloak role="status">
                <span class="lgh-empty__icon" :class="tab === 'in_transit' ? 'lgh-tone-sky' : 'lgh-tone-lilac'"><x-logistics.icon name="truck" :size="26" /></span>
                <strong x-text="(tabEmpty[tab] || [''])[0]"></strong>
                <p x-text="(tabEmpty[tab] || ['', ''])[1]"></p>
                <button type="button" class="lgh-btn lgh-btn--soft" x-show="tab === 'scheduled'" :disabled="blocked" @click="openForm(null)"><x-logistics.icon name="plus" :size="16" /> Assign driver</button>
                <button type="button" class="lgh-btn lgh-btn--soft" x-show="tab !== 'scheduled'" @click="tab = 'all'">Show all trips</button>
            </div>
        </section>

        {{-- Assign a driver: destination hub, driver, vehicle, schedule and the parcels to load. --}}
        <div class="lgh-layer" x-show="form.open" x-cloak>
            <div class="lgh-backdrop" @click="closeForm()"></div>
            <div class="lgh-dialog lgh-dialog--assign" role="dialog" aria-modal="true" aria-labelledby="assignTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="assignTitle">Assign a driver</h2>
                        <p>The trip starts at {{ $station['name'] }}. Choose the hub the driver must reach.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="closeForm()" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form class="lgh-dialog__body" @submit.prevent="save($event)" novalidate>
                    <div class="lgh-callout lgh-callout--rose" x-show="form.alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="form.alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--lilac" x-show="stale" x-cloak>
                        <x-logistics.icon name="info" :size="18" />
                        <p>A driver or vehicle freed by a cancelled or finished trip shows here after you reload. <a href="#" @click.prevent="window.location.reload()">Reload page</a></p>
                    </div>

                    <div class="lgh-field">
                        <label for="assignDestination">Driver must go to <span aria-hidden="true">*</span></label>
                        <select id="assignDestination" x-model="form.destination" @change="onDestination()" :class="{ 'has-error': form.errors.destination_id }"
                                :aria-invalid="!!form.errors.destination_id" aria-describedby="assignDestinationErr">
                            <option value="">Choose a hub</option>
                            <template x-for="d in destinations" :key="d.id">
                                <option :value="String(d.id)" x-text="d.name + ' · ' + d.kind + (d.area ? ' · ' + d.area : '')"></option>
                            </template>
                        </select>
                        <p class="lgh-err" id="assignDestinationErr" x-show="form.errors.destination_id" x-cloak x-text="form.errors.destination_id" role="alert"></p>
                    </div>

                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="assignDriver">Driver <span aria-hidden="true">*</span></label>
                            <select id="assignDriver" x-model="form.driver" @change="clearError('driver_id')" :class="{ 'has-error': form.errors.driver_id }"
                                    :aria-invalid="!!form.errors.driver_id" aria-describedby="assignDriverErr">
                                <option value="">Choose a driver</option>
                                <template x-for="d in drivers" :key="d.id">
                                    <option :value="String(d.id)" x-text="d.name + ' · ' + (d.category || 'Licence') + ' · expires ' + fmtDate(d.expiry)"></option>
                                </template>
                            </select>
                            <p class="lgh-err" id="assignDriverErr" x-show="form.errors.driver_id" x-cloak x-text="form.errors.driver_id" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="assignVehicle">Vehicle <span aria-hidden="true">*</span></label>
                            <select id="assignVehicle" x-model="form.vehicle" @change="clearError('vehicle_id')" :class="{ 'has-error': form.errors.vehicle_id }"
                                    :aria-invalid="!!form.errors.vehicle_id" aria-describedby="assignVehicleErr">
                                <option value="">Choose a vehicle</option>
                                <template x-for="v in vehicles" :key="v.id">
                                    <option :value="String(v.id)" x-text="v.code + ' · ' + v.type + ' · ' + v.plate"></option>
                                </template>
                            </select>
                            <p class="lgh-err" id="assignVehicleErr" x-show="form.errors.vehicle_id" x-cloak x-text="form.errors.vehicle_id" role="alert"></p>
                        </div>
                    </div>

                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="assignDeparture">Departure <span aria-hidden="true">*</span></label>
                            <input id="assignDeparture" type="datetime-local" x-model="form.departure" @input="clearError('departure')" :class="{ 'has-error': form.errors.departure }"
                                   :aria-invalid="!!form.errors.departure" aria-describedby="assignDepartureErr">
                            <p class="lgh-err" id="assignDepartureErr" x-show="form.errors.departure" x-cloak x-text="form.errors.departure" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="assignArrival">Expected arrival <span aria-hidden="true">*</span></label>
                            <input id="assignArrival" type="datetime-local" x-model="form.arrival" @input="clearError('expected_arrival_at')" :class="{ 'has-error': form.errors.expected_arrival_at }"
                                   :aria-invalid="!!form.errors.expected_arrival_at" aria-describedby="assignArrivalErr">
                            <p class="lgh-err" id="assignArrivalErr" x-show="form.errors.expected_arrival_at" x-cloak x-text="form.errors.expected_arrival_at" role="alert"></p>
                        </div>
                    </div>

                    {{-- Parcel checklist. Parcels whose delivery address is inside the chosen hub are listed first, so the station can load the ones that end
                         their journey there and still see the ones passing through. All are selected when a hub is chosen. --}}
                    <div class="lgh-load">
                        <div class="lgh-load__head">
                            <div>
                                <h3>Parcels to load <span aria-hidden="true">*</span></h3>
                                <p aria-live="polite" x-text="!form.destination ? 'Choose a hub to see the parcels waiting for it.' : (parcelSummary || '')"></p>
                            </div>
                            <div class="lgh-load__actions" x-show="parcels.length" x-cloak>
                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--soft" x-show="hereParcels.length && form.selected.length !== hereParcels.length" x-cloak
                                        @click="selectHere()" x-text="'Select the ' + hereParcels.length + ' for ' + destinationName"></button>
                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--ghost" @click="toggleAll(form.selected.length !== parcels.length)"
                                        x-text="form.selected.length === parcels.length ? 'Clear all' : 'Select all'"></button>
                            </div>
                        </div>
                        <p class="lgh-err" id="loadErr" x-show="form.errors.orders" x-cloak x-text="form.errors.orders" role="alert"></p>

                        <label class="lgh-search lgh-load__search" x-show="parcels.length > 8" x-cloak>
                            <span class="lgh-sr">Search parcels</span>
                            <x-logistics.icon name="search" :size="18" />
                            <input type="search" x-model="pq" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search tracking number, buyer or address">
                        </label>

                        <ul class="lgh-load__list" x-show="form.destination && visible.length" x-cloak>
                            <template x-for="p in visible" :key="p.order">
                                <li>
                                    <label :class="{ 'is-on': form.selected.includes(p.order) }">
                                        <input type="checkbox" :value="p.order" x-model="form.selected" @change="clearError('orders')">
                                        <span class="lgh-load__body">
                                            <span class="lgh-load__top">
                                                <strong x-text="p.tracking"></strong>
                                                <span class="lgh-pill" :class="p.delivers_here ? 'lgh-pill--mint' : 'lgh-pill--lilac'" x-text="p.delivers_here ? 'Delivers in this hub' : 'Passes through'"></span>
                                            </span>
                                            <span class="lgh-load__facts">
                                                <span x-text="'Order ' + p.number"></span>
                                                <span x-show="p.buyer" x-text="'Buyer: ' + p.buyer"></span>
                                                <span x-show="p.where" x-text="'To ' + p.where"></span>
                                                <span x-show="p.sorted" x-text="'Sorted ' + fmt(p.sorted)"></span>
                                            </span>
                                        </span>
                                    </label>
                                </li>
                            </template>
                        </ul>

                        <div class="lgh-empty lgh-empty--tight" x-show="form.destination && !parcels.length" x-cloak role="status">
                            <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                            <strong>No parcels ready for this hub</strong>
                            <p x-text="'Parcels show here after they are sorted for ' + destinationName + '. Sort them first, then assign the driver.'"></p>
                            <a href="{{ route('lgp.station.sorting') }}" class="lgh-btn lgh-btn--soft">Open parcel sorting</a>
                        </div>
                        <div class="lgh-empty lgh-empty--tight" x-show="form.destination && parcels.length && !visible.length" x-cloak role="status">
                            <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                            <strong>No parcels match your search</strong>
                            <p>Check the spelling, or search by tracking number, buyer or address.</p>
                            <button type="button" class="lgh-btn lgh-btn--soft" @click="pq = ''">Clear search</button>
                        </div>
                    </div>

                    <footer class="lgh-dialog__foot lgh-dialog__foot--bar">
                        <p class="lgh-bar__sum" aria-live="polite" x-text="form.destination ? chosen.length + (chosen.length === 1 ? ' parcel' : ' parcels') + ' will be loaded' : 'Choose a hub to start'"></p>
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="closeForm()">Cancel</button>
                        <button type="submit" class="lgh-btn lgh-btn--primary">Assign driver</button>
                    </footer>
                </form>
            </div>
        </div>

        {{-- Trip details with its progress. A scheduled trip that leaves here can be cancelled; one already in transit cannot. --}}
        <div class="lgh-layer" x-show="view" x-cloak>
            <div class="lgh-backdrop" @click="closeView()"></div>
            <div class="lgh-dialog lgh-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="tripTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="tripTitle" x-text="view ? view.id + ' · Station transfer' : ''"></h2>
                        <p><span class="lgh-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lgh-close" @click="closeView()" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-callout lgh-callout--rose" x-show="alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="alert"></p>
                    </div>
                    <template x-if="view && view.status === 'scheduled' && view.can_depart">
                        <div class="lgh-callout lgh-callout--lilac">
                            <x-logistics.icon name="info" :size="18" />
                            <p>Scan every parcel for departure at <strong x-text="view.origin"></strong>, then record the departure.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'scheduled' && !view.can_depart">
                        <div class="lgh-callout lgh-callout--lilac">
                            <x-logistics.icon name="info" :size="18" />
                            <p>This trip leaves <strong x-text="view.origin"></strong>. It arrives here after the departure is recorded.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'in_transit' && view.can_arrive">
                        <div class="lgh-callout lgh-callout--sky">
                            <x-logistics.icon name="truck" :size="18" />
                            <p>Scan every parcel at <strong x-text="view.destination"></strong>, then record the arrival.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'in_transit' && !view.can_arrive">
                        <div class="lgh-callout lgh-callout--sky">
                            <x-logistics.icon name="truck" :size="18" />
                            <p>On its way to <strong x-text="view.destination"></strong>. That hub records the arrival.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'cancelled'">
                        <div class="lgh-callout lgh-callout--rose">
                            <x-logistics.icon name="alert" :size="18" />
                            <p><strong>Cancelled.</strong> <span x-text="view.note || 'No reason was recorded.'"></span></p>
                        </div>
                    </template>

                    <dl class="lgh-facts" x-show="view">
                        <div><dt>From</dt><dd x-text="view && view.origin"></dd></div>
                        <div><dt>To</dt><dd x-text="view && view.destination"></dd></div>
                        <div><dt>Driver</dt><dd x-text="view && view.driver"></dd></div>
                        <div><dt>Vehicle</dt><dd x-text="view && view.vehicle"></dd></div>
                        <div><dt>Parcels loaded</dt><dd x-text="view && view.parcels"></dd></div>
                        <div><dt>Scheduled departure</dt><dd x-text="view && fmt(view.scheduled)"></dd></div>
                        <div><dt>Expected arrival</dt><dd x-text="view && fmt(view.expectedArrival)"></dd></div>
                    </dl>

                    <div class="lgh-docs" x-show="view && view.manifest && view.manifest.length" x-cloak>
                        <h3>Manifest · open a parcel to track it</h3>
                        <ul class="lgh-manifest">
                            <template x-for="code in (view ? view.manifest : [])" :key="code">
                                <li><a :href="trackingUrl + '?q=' + encodeURIComponent(code)" x-text="code"></a></li>
                            </template>
                        </ul>
                    </div>

                    <template x-if="view && view.status !== 'cancelled'">
                        <ol class="lgh-steps">
                            <li :class="view.status === 'scheduled' ? 'is-current' : 'is-done'">
                                <strong>Scheduled</strong>
                                <small x-text="'Driver assigned for ' + view.destination + ' · departure planned ' + fmt(view.scheduled)"></small>
                            </li>
                            <li :class="view.status === 'in_transit' ? 'is-current' : (view.status === 'completed' ? 'is-done' : '')">
                                <strong>Departed</strong>
                                <small x-text="view.departed ? 'Departure recorded ' + fmt(view.departed) : 'Not departed yet'"></small>
                            </li>
                            <li :class="view.status === 'completed' ? 'is-done' : ''">
                                <strong>Arrived</strong>
                                <small x-text="view.arrived ? 'Received at ' + view.destination + ' ' + fmt(view.arrived) : 'Not received at ' + view.destination + ' yet'"></small>
                            </li>
                        </ol>
                    </template>

                    <footer class="lgh-dialog__foot lgh-dialog__foot--sticky">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="closeView()">Close</button>
                        <template x-if="view && view.status === 'scheduled' && view.can_cancel">
                            <button type="button" class="lgh-btn lgh-btn--danger" @click="askCancel(view)">Cancel trip</button>
                        </template>
                        <template x-if="view && canDepart(view)">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="changeStatus(view, 'depart', $event)">Record departure</button>
                        </template>
                        <template x-if="view && canArrive(view)">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="changeStatus(view, 'arrive', $event)">Record arrival</button>
                        </template>
                    </footer>
                </div>
            </div>
        </div>

        {{-- Cancel asks for a reason. It releases the driver, the vehicle and the loaded parcels. --}}
        <div class="lgh-layer" x-show="confirm.open" x-cloak style="z-index: 80">
            <div class="lgh-backdrop" @click="confirm.open = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="tripCancelTitle" x-text="'Cancel ' + (confirm.t ? confirm.t.id : 'trip') + '?'"></h2>
                        <p>The driver, the vehicle and the loaded parcels are released, and the trip leaves the driver app.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirm.open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-field">
                        <label for="tripReason">Reason <span aria-hidden="true">*</span></label>
                        <textarea id="tripReason" rows="3" maxlength="500" x-model="confirm.reason" @input="confirm.error = ''" placeholder="For example: the destination station is closed that day"
                                  :aria-invalid="!!confirm.error" aria-describedby="tripReasonErr" :class="{ 'has-error': confirm.error }"></textarea>
                        <p class="lgh-err" id="tripReasonErr" x-show="confirm.error" x-cloak x-text="confirm.error" role="alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--rose" x-show="confirm.alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="confirm.alert"></p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirm.open = false">Keep trip</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" @click="submitCancel($event)">Cancel trip</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
