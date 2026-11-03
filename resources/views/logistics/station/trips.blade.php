{{--
    Station Hub → Driver Assignments.
    The Station Hub assigns one of the Transport Drivers linked to it to a trip
    and names the Province Hub or Station Hub that driver must reach. Saving
    creates a scheduled trip, which is the same trip the driver sees in the
    Driver app, so the assignment and the destination hub stay in one record.
--}}
<x-logistics.shell title="Driver Assignments" role="station">
    <div class="lg-page"
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
            working: false,
            q: '',
            filter: 'all',
            view: null,
            cancel: { open: false, t: null, reason: '', error: '' },
            form: {
                // Arriving from the Drivers page opens the form with that driver chosen.
                open: @js($selectedDriver > 0),
                destination: '',
                driver: String(@js($selectedDriver) || ''),
                vehicle: '',
                departure: @js($defaultDeparture),
                arrival: @js($defaultArrival),
                selected: [],
                errors: {}
            },
            filters: {
                all: t => true,
                scheduled: t => t.status === 'scheduled',
                in_transit: t => t.status === 'in_transit',
                completed: t => t.status === 'completed',
                cancelled: t => t.status === 'cancelled'
            },
            labels: { scheduled: 'Scheduled', in_transit: 'In transit', completed: 'Completed', cancelled: 'Cancelled' },
            tones: { scheduled: 'lg-pill--blue', in_transit: 'lg-pill--amber', completed: 'lg-pill--green', cancelled: 'lg-pill--gray' },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.trips
                    .filter(t => this.filters[this.filter](t)
                        && (!s || [t.id, t.origin, t.destination, t.driver, t.vehicle].join(' ').toLowerCase().includes(s)))
                    .sort((a, b) => {
                        const rank = t => t.status === 'in_transit' ? 0 : (t.status === 'scheduled' ? 1 : 2);
                        return rank(a) - rank(b) || (rank(a) < 2 ? a.scheduled.localeCompare(b.scheduled) : b.scheduled.localeCompare(a.scheduled));
                    });
            },
            count(k) { return this.trips.filter(this.filters[k]).length; },
            fmt(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''; },
            fmtDate(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            get parcels() { return this.parcelMap[this.form.destination] || []; },
            get chosen() { return this.parcels.filter(p => this.form.selected.includes(p.order)); },
            get hereParcels() { return this.parcels.filter(p => p.delivers_here); },
            get currentDestination() {
                return this.destinations.find(x => String(x.id) === String(this.form.destination));
            },
            get destinationName() { return this.currentDestination ? this.currentDestination.name : 'the chosen hub'; },
            get destinationArea() {
                const d = this.currentDestination;
                return d ? (d.area || d.name) : 'the chosen hub';
            },
            get parcelSummary() {
                const n = this.parcels.length;
                if (!n) return '';
                return n + (n === 1 ? ' parcel' : ' parcels') + ' is sorted here for ' + this.destinationName
                    + '. ' + this.hereParcels.length + ' end' + (this.hereParcels.length === 1 ? 's' : '')
                    + ' in ' + this.destinationArea + '; the rest only pass through.';
            },
            openForm(driverId) {
                this.form = {
                    open: true, destination: '', driver: driverId ? String(driverId) : '',
                    vehicle: '', departure: @js($defaultDeparture), arrival: @js($defaultArrival),
                    selected: [], errors: {}
                };
            },
            closeForm() { this.form.open = false; },
            onDestination() { this.form.selected = this.parcels.map(p => p.order); this.form.errors = {}; },
            toggleAll(on) { this.form.selected = on ? this.parcels.map(p => p.order) : []; },
            selectHere() { this.form.selected = this.hereParcels.map(p => p.order); },
            validate() {
                const f = this.form, e = {};
                if (!f.destination) e.destination_id = 'Choose the hub the driver must reach.';
                if (!f.driver) e.driver_id = 'Choose an available driver.';
                else {
                    const d = this.drivers.find(x => String(x.id) === String(f.driver));
                    if (d && f.departure && d.expiry < f.departure.slice(0, 10)) e.driver_id = d.name + ' has a licence that expires on ' + this.fmtDate(d.expiry) + ', before this departure.';
                }
                if (!f.vehicle) e.vehicle_id = 'Choose an available vehicle.';
                if (!f.departure) e.departure = 'Set the scheduled departure.';
                else if (f.departure < this.now) e.departure = 'The departure cannot be in the past.';
                if (!f.arrival || f.arrival <= f.departure) e.expected_arrival_at = 'Set an expected arrival after departure.';
                if (f.destination && f.selected.length === 0) e.orders = 'Select at least one parcel to load.';
                f.errors = e;
                return Object.keys(e).length === 0;
            },
            async save() {
                const f = this.form;
                if (this.working || !this.validate()) return;
                this.working = true;
                try {
                    const response = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({
                            destination_id: Number(f.destination), driver_id: Number(f.driver), vehicle_id: Number(f.vehicle),
                            departure: f.departure, expected_arrival_at: f.arrival, orders: f.selected.map(Number)
                        })
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) { f.errors = payload.errors || {}; throw new Error(payload.message || 'Could not assign the driver.'); }
                    this.trips.unshift(payload.trip);
                    f.open = false;
                    this.filter = 'all';
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
                finally { this.working = false; }
            },
            openView(t) { this.view = t; },
            closeView() { this.view = null; },
            askCancel(t) { this.cancel = { open: true, t: t, reason: '', error: '' }; },
            async submitCancel() {
                const c = this.cancel, t = c.t;
                if (!t || t.status !== 'scheduled') return;
                if (c.reason.trim().length < 5) { c.error = 'Give a short reason so the driver knows why the trip was cancelled.'; return; }
                try {
                    const response = await fetch(this.cancelUrl.replace('__trip__', t.key), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({ reason: c.reason.trim() })
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Could not cancel the trip.');
                    Object.assign(t, payload.trip);
                    c.open = false; this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                    this.trips = this.trips.filter(x => x.key !== t.key).concat([t]);
                } catch (error) { c.error = error.message; }
            },
            async changeStatus(t, action) {
                if (this.working) return;
                this.working = true;
                try {
                    const response = await fetch((action === 'depart' ? this.departUrl : this.arriveUrl).replace('__trip__', t.key), {
                        method: 'PATCH',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Could not update the trip.');
                    Object.assign(t, payload.trip);
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
                finally { this.working = false; }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Driver Assignments</h1>
                <p>Assign a Transport Driver from {{ $station['name'] }} to a trip and choose the hub they must reach. The trip and its destination appear in the driver app right away.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openForm(null)"><x-logistics.icon name="plus" :size="18" /> Assign driver</button>
            </div>
        </div>

        <div class="lg-grid lg-grid--4">
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--green"><x-logistics.icon name="users" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Drivers ready</p>
                    <p class="lg-stat__value">{{ count($drivers) }}</p>
                </div>
            </div>
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--blue"><x-logistics.icon name="truck" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Trips scheduled</p>
                    <p class="lg-stat__value">{{ collect($trips)->where('status', 'scheduled')->count() }}</p>
                </div>
            </div>
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--amber"><x-logistics.icon name="clock" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">On the road</p>
                    <p class="lg-stat__value">{{ collect($trips)->where('status', 'in_transit')->count() }}</p>
                </div>
            </div>
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--blue"><x-logistics.icon name="map-pin" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Hub destinations</p>
                    <p class="lg-stat__value">{{ count($destinations) }}</p>
                </div>
            </div>
        </div>

        @if (! count($drivers))
            <div class="lg-note lg-note--warn">
                <x-logistics.icon name="alert" :size="18" />
                <span>No Transport Driver linked to {{ $station['name'] }} is available right now. Approve a driver on the <a href="{{ route('lgp.station.drivers') }}">Drivers</a> page, or wait for a driver who is on a trip to finish.</span>
            </div>
        @endif

        <nav class="lg-tabs" aria-label="Assignment filters">
            @foreach (['all' => 'All', 'scheduled' => 'Scheduled', 'in_transit' => 'In transit', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="filter === '{{ $k }}'" @click="filter = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search trip, driver, vehicle or hub" aria-label="Search assignments">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' trip' : ' trips')"></span>
        </div>

        <section class="lg-card" aria-label="Trips from this station">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Trip</th><th>Driver</th><th>Send to</th><th>Vehicle</th>
                            <th class="is-num">Parcels</th><th>Departure</th><th>Status</th><th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="t in shown" :key="t.id">
                            <tr>
                                <td data-label="Trip"><div class="lg-cell-title" x-text="t.id"></div><div class="lg-cell-sub" x-text="'From ' + t.origin"></div></td>
                                <td data-label="Driver"><div x-text="t.driver"></div><div class="lg-cell-sub" x-text="t.can_depart ? 'Assigned by this station' : 'Arriving here'"></div></td>
                                <td data-label="Send to"><div x-text="t.destination"></div><div class="lg-cell-sub" x-text="t.can_arrive ? 'This station receives it' : 'Destination hub'"></div></td>
                                <td data-label="Vehicle" x-text="t.vehicle"></td>
                                <td data-label="Parcels" class="is-num" x-text="t.parcels"></td>
                                <td data-label="Departure">
                                    <div x-text="fmt(t.departed || t.scheduled)"></div>
                                    <div class="lg-cell-sub" x-text="t.departed ? 'Departed' : 'Scheduled'"></div>
                                </td>
                                <td data-label="Status"><span class="lg-pill" :class="tones[t.status]" x-text="labels[t.status]"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openView(t)" :aria-label="'View ' + t.id"><x-logistics.icon name="eye" :size="16" /> View</button>
                                        <button type="button" class="lg-btn lg-btn--danger lg-btn--sm" x-show="t.status === 'scheduled' && t.can_cancel" @click="askCancel(t)" :aria-label="'Cancel ' + t.id">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!trips.length" x-cloak icon="truck" title="No driver assigned yet"
                text="Assign a driver to a trip and pick the hub they must reach. It shows here and in the driver app.">
                <button type="button" class="lg-btn lg-btn--sm" @click="openForm(null)">Assign driver</button>
            </x-logistics.empty>
            <x-logistics.empty x-show="trips.length && !shown.length" x-cloak icon="search" title="No trips match"
                text="Check the spelling, or pick another filter.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; filter = 'all'">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Assign a driver: destination hub, driver, vehicle and the parcels to load. --}}
        <div class="lg-overlay" x-show="form.open" x-cloak @click.self="closeForm()" @keydown.escape.window="closeForm()">
            <div class="lg-modal" style="width:min(100%,640px)" role="dialog" aria-modal="true" aria-labelledby="assignTitle">
                <form @submit.prevent="save()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="assignTitle">Assign a driver</h3>
                            <p>Trip starts at {{ $station['name'] }}. Choose the hub the driver must reach.</p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="closeForm()" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field is-wide">
                            <label for="assignDestination">Driver must go to <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="assignDestination" class="lg-select" x-model="form.destination" @change="onDestination()"
                                :aria-invalid="!!form.errors.destination_id" aria-describedby="assignDestinationErr">
                                <option value="">Choose a hub</option>
                                <template x-for="d in destinations" :key="d.id">
                                    <option :value="String(d.id)" x-text="d.name + ' · ' + d.kind + (d.area ? ' · ' + d.area : '')"></option>
                                </template>
                            </select>
                            <p class="lg-error" id="assignDestinationErr" x-show="form.errors.destination_id" x-text="form.errors.destination_id" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!destinations.length" x-cloak>No other hub is active in this network yet.</p>
                        </div>

                        <div class="lg-field">
                            <label for="assignDriver">Driver <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="assignDriver" class="lg-select" x-model="form.driver"
                                :aria-invalid="!!form.errors.driver_id" aria-describedby="assignDriverErr">
                                <option value="">Choose a driver</option>
                                <template x-for="d in drivers" :key="d.id">
                                    <option :value="String(d.id)" x-text="d.name + ' · ' + (d.category || 'Licence') + ' · expires ' + fmtDate(d.expiry)"></option>
                                </template>
                            </select>
                            <p class="lg-error" id="assignDriverErr" x-show="form.errors.driver_id" x-text="form.errors.driver_id" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!drivers.length" x-cloak>No driver linked to this station is free. A driver on a trip is released when that trip arrives.</p>
                        </div>

                        <div class="lg-field">
                            <label for="assignVehicle">Vehicle <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="assignVehicle" class="lg-select" x-model="form.vehicle"
                                :aria-invalid="!!form.errors.vehicle_id" aria-describedby="assignVehicleErr">
                                <option value="">Choose a vehicle</option>
                                <template x-for="v in vehicles" :key="v.id">
                                    <option :value="String(v.id)" x-text="v.code + ' · ' + v.type + ' · ' + v.plate"></option>
                                </template>
                            </select>
                            <p class="lg-error" id="assignVehicleErr" x-show="form.errors.vehicle_id" x-text="form.errors.vehicle_id" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!vehicles.length" x-cloak>No vehicle from the Province Hub is free right now.</p>
                        </div>

                        <div class="lg-field">
                            <label for="assignDeparture">Scheduled departure <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="assignDeparture" type="datetime-local" class="lg-input" x-model="form.departure"
                                :aria-invalid="!!form.errors.departure" aria-describedby="assignDepartureErr">
                            <p class="lg-error" id="assignDepartureErr" x-show="form.errors.departure" x-text="form.errors.departure" role="alert"></p>
                        </div>

                        <div class="lg-field">
                            <label for="assignArrival">Expected arrival <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="assignArrival" type="datetime-local" class="lg-input" x-model="form.arrival"
                                :aria-invalid="!!form.errors.expected_arrival_at" aria-describedby="assignArrivalErr">
                            <p class="lg-error" id="assignArrivalErr" x-show="form.errors.expected_arrival_at" x-text="form.errors.expected_arrival_at" role="alert"></p>
                        </div>

                        {{-- Parcel checklist. Rows whose delivery address sits inside the chosen hub are marked first, so the
                             station can load the parcels that end their journey there and still see the ones passing through. --}}
                        <div class="is-wide">
                            <div class="lg-card__head" style="padding:0;margin-bottom:8px;align-items:flex-start">
                                <div>
                                    <p class="lg-section-sub">Parcels to load <span class="lg-req" aria-hidden="true">*</span></p>
                                    <p class="lg-cell-sub" x-show="!form.destination">Choose the hub the driver must reach to see the parcels waiting for it.</p>
                                    <p class="lg-cell-sub" x-show="form.destination" x-text="parcelSummary"></p>
                                </div>
                                <div class="lg-actions" x-show="parcels.length">
                                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm"
                                        x-show="hereParcels.length && form.selected.length !== hereParcels.length"
                                        @click="selectHere()"
                                        x-text="'Select the ' + hereParcels.length + ' for ' + destinationName"></button>
                                    <button type="button" class="lg-btn lg-btn--ghost lg-btn--sm"
                                        @click="toggleAll(form.selected.length !== parcels.length)"
                                        x-text="form.selected.length === parcels.length ? 'Clear all' : 'Select all '"
                                    ></button>
                                </div>
                            </div>
                            <p class="lg-error" x-show="form.errors.orders" x-text="form.errors.orders" role="alert"></p>
                            <template x-if="form.destination && parcels.length">
                                <ul style="display:grid;gap:6px;max-height:260px;overflow:auto;padding:0;margin:0;list-style:none">
                                    <template x-for="p in parcels" :key="p.order">
                                        <li>
                                            <label class="lg-parcel-option" :class="p.delivers_here ? 'lg-parcel-option--here' : ''">
                                                <input type="checkbox" :value="p.order" x-model="form.selected">
                                                <span>
                                                    <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                                        <strong x-text="p.tracking"></strong>
                                                        <span class="lg-pill"
                                                            :class="p.delivers_here ? 'lg-pill--green' : 'lg-pill--gray'"
                                                            x-text="p.delivers_here ? 'Delivers in this hub' : 'Passes through'"></span>
                                                    </span>
                                                    <span class="lg-parcel-option__facts">
                                                        <span x-text="'Order ' + p.number"></span>
                                                        <span x-show="p.buyer" x-text="'Buyer: ' + p.buyer"></span>
                                                        <span x-show="p.where" x-text="'To ' + p.where"></span>
                                                        <span x-show="p.sorted" x-text="'Sorted ' + fmt(p.sorted)"></span>
                                                    </span>
                                                </span>
                                                <span class="lg-cell-sub" style="text-align:right"
                                                    x-text="form.selected.includes(p.order) ? 'Loaded' : 'Not loaded'"></span>
                                            </label>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="form.destination && !parcels.length">
                                <div class="lg-note">
                                    <x-logistics.icon name="info" :size="18" />
                                    <span>No parcel is waiting for a trip to <span x-text="destinationName"></span> (<span x-text="destinationArea"></span>). Sort the parcels for that hub on the <a href="{{ route('lgp.station.sorting') }}">Sorting</a> page first, then assign the driver.</span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="closeForm()">Cancel</button>
                        <button type="submit" class="lg-btn" :disabled="working"><span x-text="working ? 'Assigning…' : 'Assign driver'"></span></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Trip details, with the destination hub the driver was sent to. --}}
        <div class="lg-overlay" x-show="view" x-cloak @click.self="closeView()" @keydown.escape.window="closeView()">
            <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="tripTitle" x-show="view">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="tripTitle" x-text="view ? view.id + ' · Station transfer' : ''"></h3>
                        <p x-show="view"><span class="lg-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="closeView()" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="view">
                    <template x-if="view && view.status === 'in_transit'">
                        <div class="lg-note">
                            <x-logistics.icon name="info" :size="18" />
                            <span>Scan every parcel at <span x-text="view.destination"></span>, then record the trip arrival.</span>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'cancelled'">
                        <div class="lg-note lg-note--warn">
                            <x-logistics.icon name="alert" :size="18" />
                            <span><strong>Cancelled.</strong> <span x-text="view.note"></span></span>
                        </div>
                    </template>
                    <dl class="lg-facts" x-show="view">
                        <div><dt>Driver</dt><dd x-text="view && view.driver"></dd></div>
                        <div><dt>Sent to</dt><dd x-text="view && view.destination"></dd></div>
                        <div><dt>From</dt><dd x-text="view && view.origin"></dd></div>
                        <div><dt>Vehicle</dt><dd x-text="view && view.vehicle"></dd></div>
                        <div><dt>Parcels loaded</dt><dd x-text="view && view.parcels"></dd></div>
                        <div><dt>Scheduled departure</dt><dd x-text="view && fmt(view.scheduled)"></dd></div>
                        <div><dt>Expected arrival</dt><dd x-text="view && fmt(view.expectedArrival)"></dd></div>
                    </dl>
                    <div class="is-wide" x-show="view && view.manifest && view.manifest.length">
                        <strong>Manifest</strong>
                        <ul class="lg-cell-sub" style="margin-top:8px;max-height:120px;overflow:auto">
                            <template x-for="code in (view?.manifest || [])" :key="code"><li x-text="code"></li></template>
                        </ul>
                    </div>
                    <template x-if="view && view.status !== 'cancelled'">
                        <ol class="lg-journey" style="padding:0">
                            <li :class="view.status === 'scheduled' ? 'is-current' : ''">
                                <div class="lg-journey__what">Scheduled</div>
                                <div class="lg-journey__meta" x-text="'Driver assigned for ' + view.destination + ' · departure ' + fmt(view.scheduled)"></div>
                            </li>
                            <li :class="view.status === 'in_transit' ? 'is-current' : ''">
                                <div class="lg-journey__what">Departed</div>
                                <div class="lg-journey__meta" x-text="view.departed ? 'Departure recorded ' + fmt(view.departed) : 'Scan every manifest parcel for departure first'"></div>
                            </li>
                            <li :class="view.status === 'completed' ? 'is-current' : ''">
                                <div class="lg-journey__what">Arrived</div>
                                <div class="lg-journey__meta" x-text="view.arrived ? 'Received at ' + view.destination + ' ' + fmt(view.arrived) : 'Not yet received at ' + view.destination"></div>
                            </li>
                        </ol>
                    </template>
                </div>
                <div class="lg-modal__foot" x-show="view">
                    <button type="button" class="lg-btn lg-btn--outline" @click="closeView()">Close</button>
                    <button type="button" class="lg-btn lg-btn--danger" x-show="view && view.status === 'scheduled' && view.can_cancel" @click="askCancel(view)">Cancel trip</button>
                    <button type="button" class="lg-btn" x-show="view && view.status === 'scheduled' && view.can_depart" :disabled="working" @click="changeStatus(view, 'depart')">Record departure</button>
                    <button type="button" class="lg-btn" x-show="view && view.status === 'in_transit' && view.can_arrive" :disabled="working" @click="changeStatus(view, 'arrive')">Record arrival</button>
                </div>
            </div>
        </div>

        {{-- Cancel asks for a reason and releases the driver, the vehicle and the parcels. --}}
        <div class="lg-overlay" x-show="cancel.open" x-cloak @click.self="cancel.open = false" @keydown.escape.window="cancel.open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="tripCancelTitle" x-text="'Cancel ' + (cancel.t ? cancel.t.id : 'trip')"></h3>
                        <p>The driver is released and the trip leaves their app.</p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="cancel.open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid">
                    <div class="lg-field">
                        <label for="tripReason">Reason <span class="lg-req" aria-hidden="true">*</span></label>
                        <textarea id="tripReason" class="lg-textarea" rows="3" maxlength="200" x-model="cancel.reason"
                            placeholder="For example: the destination station is closed that day"
                            :aria-invalid="!!cancel.error" aria-describedby="tripReasonErr"></textarea>
                        <p class="lg-error" id="tripReasonErr" x-show="cancel.error" x-text="cancel.error" role="alert"></p>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="cancel.open = false">Keep trip</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" @click="submitCancel()">Cancel trip</button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
