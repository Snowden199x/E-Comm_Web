{{--
    Province Hub > Create trip. Facility, driver, vehicle and parcel options are loaded from scoped database records by
    PortalController::createTripPage; the trip is saved by storeTrip (JSON), which also flashes the success message for the Trips page.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Transport Trips. Styles: hubs-theme.css plus trip-create-theme.css (loaded last).
    Flow: trip type, route, driver and vehicle, schedule, then the parcels ready for that route (all are selected by default). The primary
    button sits in a bar at the bottom that stays in view. Selecting happens in the browser; nothing is saved until Create trip.
    Blockers (no available driver or vehicle) show a notice with a button to fix them.
    Empty: no destination chosen yet, no parcels ready for the route, and no parcels match the search. Errors: message under each field, top banner for the rest.
    Loading: shell skeleton on open; spinner on the Create trip button while saving.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/trip-create-theme.css'])
@endpush

<x-logistics.shell title="Create Transport Trip" role="province">
    <div class="lgh"
         x-data="{
            hubName: @js($hub['name']),
            hubId: @js($hub['id']),
            stations: @js($stations),
            otherHubs: @js($otherHubs),
            drivers: @js($drivers),
            vehicles: @js($vehicles),
            parcelMap: @js($parcels),
            now: @js($now),
            listUrl: @js($listUrl),
            storeUrl: @js($storeUrl),
            type: 'station_transfer',
            origin: String(@js($hub['id'])),
            destination: '',
            driver: '',
            vehicle: '',
            departure: @js($defaultDeparture),
            expectedArrival: @js($defaultArrival),
            selected: [],
            q: '',
            errors: {},
            working: false,
            get originOptions() { return this.type === 'inter_province' ? [{ id: this.hubId, name: this.hubName }] : [{ id: this.hubId, name: this.hubName }].concat(this.stations); },
            get destinationOptions() {
                if (this.type === 'inter_province') return this.otherHubs;
                return Number(this.origin) === Number(this.hubId) ? this.stations : [{ id: this.hubId, name: this.hubName }];
            },
            // Which parcels are ready: from the hub to the chosen destination, or from the chosen station to the hub.
            get parcelKey() {
                if (!this.origin || !this.destination) return '';
                return Number(this.origin) === Number(this.hubId) ? 'to:' + this.destination : 'from:' + this.hubId;
            },
            get parcels() { return this.parcelMap[this.parcelKey] || []; },
            get searching() { return this.q.trim() !== ''; },
            get visible() { const s = this.q.trim().toLowerCase(); return this.parcels.filter(p => !s || (p.tracking + ' ' + p.destination).toLowerCase().includes(s)); },
            get chosen() { return this.parcels.filter(p => this.selected.includes(p.order)); },
            get allPicked() { return this.visible.length > 0 && this.visible.every(p => this.selected.includes(p.order)); },
            get blocked() { return !this.drivers.length || !this.vehicles.length; },
            isPicked(p) { return this.selected.includes(p.order); },
            toggle(p) { this.errors.parcels = ''; this.selected = this.isPicked(p) ? this.selected.filter(o => o !== p.order) : this.selected.concat([p.order]); },
            toggleAll(on) {
                this.errors.parcels = '';
                const ids = this.visible.map(p => p.order);
                this.selected = on ? Array.from(new Set(this.selected.concat(ids))) : this.selected.filter(o => !ids.includes(o));
            },
            setType(t) {
                this.type = t; this.origin = String(this.hubId); this.destination = ''; this.errors = {};
                this.selected = []; this.q = '';
            },
            onOrigin() {
                this.destination = Number(this.origin) === Number(this.hubId) ? '' : String(this.hubId);
                this.errors.destination = ''; this.q = '';
                this.selected = this.parcels.map(p => p.order);
            },
            onDestination() { this.errors.destination = ''; this.q = ''; this.selected = this.parcels.map(p => p.order); },
            cap(n) { return Number(n) + (Number(n) === 1 ? ' ton' : ' tons'); },
            fmtDate(iso) { return new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            // Server field names to the names used on this page.
            serverErrors(errors) {
                const map = { origin_id: 'destination', destination_id: 'destination', driver_id: 'driver', vehicle_id: 'vehicle', departure: 'departure', expected_arrival_at: 'expected_arrival_at', orders: 'parcels' };
                const out = {};
                Object.entries(errors || {}).forEach(([key, value]) => { const field = map[key] || map[key.split('.')[0]]; if (field && !out[field]) out[field] = Array.isArray(value) ? value[0] : value; });
                return out;
            },
            validate() {
                const e = {};
                if (!this.destination) e.destination = 'Destination is not chosen. Pick where this trip goes.';
                if (!this.driver) e.driver = 'Driver is not chosen. Pick an available driver.';
                else {
                    const d = this.drivers.find(x => Number(x.id) === Number(this.driver));
                    // The licence is re-checked when a trip is assigned (spec 6B).
                    if (d && this.departure && d.expiry < this.departure.slice(0, 10)) e.driver = d.name + '’s licence expires on ' + this.fmtDate(d.expiry) + ', before this departure. Pick another driver, or set an earlier departure.';
                }
                if (!this.vehicle) e.vehicle = 'Vehicle is not chosen. Pick an available vehicle.';
                if (!this.departure) e.departure = 'Departure is empty. Set when the trip leaves.';
                else if (this.departure < this.now) e.departure = 'Departure is in the past. Pick a time from now on.';
                if (!this.expectedArrival) e.expected_arrival_at = 'Expected arrival is empty. Set when the trip should arrive.';
                else if (this.departure && this.expectedArrival <= this.departure) e.expected_arrival_at = 'Expected arrival is before the departure. Pick a time after the departure.';
                if (this.destination && this.chosen.length === 0) e.parcels = 'No parcels are selected. Select at least one parcel to load.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save(event) {
                if (this.working) return;
                if (!this.validate()) { this.$nextTick(() => document.querySelector('[data-trip-form] [aria-invalid=true]')?.focus()); return; }
                const button = event.submitter;
                this.working = true; this.busy(button, true);
                try {
                    const response = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({ type: this.type, origin_id: Number(this.origin), destination_id: Number(this.destination), driver_id: Number(this.driver), vehicle_id: Number(this.vehicle), departure: this.departure, expected_arrival_at: this.expectedArrival, orders: this.chosen.map(p => Number(p.order)) })
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        if (payload.errors) {
                            this.errors = this.serverErrors(payload.errors);
                            const e = new Error('Fix the highlighted fields and create the trip again.');
                            throw e;
                        }
                        throw new Error(payload.message || (response.status === 419 ? 'Your session expired. Reload the page and try again.' : (response.status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'Could not create the trip. Reload the page and try again.')));
                    }
                    // The Trips page shows the success message. Keep the spinner on while it opens.
                    window.location.href = this.listUrl;
                } catch (error) {
                    this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not create the trip. Try again.') });
                    this.working = false; this.busy(button, false);
                }
            }
         }">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <a href="{{ $listUrl }}" class="lgh-back"><x-logistics.icon name="arrow-right" :size="14" /> Back to transport trips</a>
                <h1>Create trip</h1>
                <p>Pick a route, a driver and a vehicle, then choose the parcels to load. The trip ID is assigned when you save.</p>
            </div>
        </header>

        {{-- Blockers: a trip needs an available driver and vehicle --}}
        <div class="lgh-callout lgh-callout--rose" x-show="!drivers.length" x-cloak role="status">
            <x-logistics.icon name="alert" :size="18" />
            <p>No driver is available right now. Approve or activate a driver, or wait for one to finish a trip.
                <a href="{{ route('lgp.province.drivers') }}">Open transport drivers</a></p>
        </div>
        <div class="lgh-callout lgh-callout--rose" x-show="!vehicles.length" x-cloak role="status">
            <x-logistics.icon name="alert" :size="18" />
            <p>No vehicle is available right now. Add a vehicle, or put one back in service.
                <a href="{{ route('lgp.province.vehicles') }}">Open vehicles</a></p>
        </div>

        <form @submit.prevent="save($event)" novalidate data-trip-form class="lgh-trip">
            <div class="lgh-trip__grid">
                <section class="lgh-card lgh-panel" aria-labelledby="routeTitle">
                    <header class="lgh-panel__head">
                        <span class="lgh-tile lgh-tone-lilac"><x-logistics.icon name="map-pin" :size="20" /></span>
                        <div><h2 id="routeTitle">Route</h2><p>Where the parcels start and end.</p></div>
                    </header>
                    <div class="lgh-panel__body">
                        <fieldset class="lgh-choices">
                            <legend>Trip type</legend>
                            <label class="lgh-choice" :class="{ 'is-on': type === 'station_transfer' }">
                                <input type="radio" name="tripType" value="station_transfer" :checked="type === 'station_transfer'" @change="setType('station_transfer')">
                                <span><strong>Station transfer</strong><small>Between a Station Hub and {{ $hub['name'] }}</small></span>
                            </label>
                            <label class="lgh-choice" :class="{ 'is-on': type === 'inter_province' }">
                                <input type="radio" name="tripType" value="inter_province" :checked="type === 'inter_province'" @change="setType('inter_province')">
                                <span><strong>Inter-province</strong><small>From {{ $hub['name'] }} to another Province Hub</small></span>
                            </label>
                        </fieldset>

                        <div class="lgh-field">
                            <label for="tripOrigin">From</label>
                            <select id="tripOrigin" x-model="origin" @change="onOrigin()" :disabled="type === 'inter_province'">
                                <template x-for="o in originOptions" :key="o.id"><option :value="String(o.id)" x-text="o.name"></option></template>
                            </select>
                        </div>
                        <div class="lgh-field">
                            <label for="tripDest">To <span aria-hidden="true">*</span></label>
                            <select id="tripDest" x-model="destination" @change="onDestination()" :disabled="Number(origin) !== Number(hubId) && type === 'station_transfer'"
                                    :aria-invalid="!!errors.destination" aria-describedby="tripDestErr" :class="{ 'has-error': errors.destination }">
                                <option value="">Choose a destination</option>
                                <template x-for="o in destinationOptions" :key="o.id"><option :value="String(o.id)" x-text="o.name"></option></template>
                            </select>
                            <p class="lgh-err" id="tripDestErr" x-show="errors.destination" x-cloak x-text="errors.destination" role="alert"></p>
                            <p class="lgh-hint" x-show="!destinationOptions.length" x-cloak x-text="type === 'inter_province' ? 'There is no other active Province Hub to send parcels to.' : 'There is no active Station Hub yet. Activate one in Station Hubs first.'"></p>
                        </div>
                    </div>
                </section>

                <section class="lgh-card lgh-panel" aria-labelledby="crewTitle">
                    <header class="lgh-panel__head">
                        <span class="lgh-tile lgh-tone-lilac"><x-logistics.icon name="truck" :size="20" /></span>
                        <div><h2 id="crewTitle">Driver, vehicle and schedule</h2><p>Only available drivers and vehicles are listed.</p></div>
                    </header>
                    <div class="lgh-panel__body">
                        <div class="lgh-field">
                            <label for="tripDriver">Transport driver <span aria-hidden="true">*</span></label>
                            <select id="tripDriver" x-model="driver" @change="errors.driver = ''" :aria-invalid="!!errors.driver" aria-describedby="tripDriverErr" :class="{ 'has-error': errors.driver }">
                                <option value="">Choose a driver</option>
                                <template x-for="d in drivers" :key="d.id"><option :value="String(d.id)" x-text="d.name + ' · ' + d.category"></option></template>
                            </select>
                            <p class="lgh-err" id="tripDriverErr" x-show="errors.driver" x-cloak x-text="errors.driver" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="tripVehicle">Vehicle <span aria-hidden="true">*</span></label>
                            <select id="tripVehicle" x-model="vehicle" @change="errors.vehicle = ''" :aria-invalid="!!errors.vehicle" aria-describedby="tripVehicleErr" :class="{ 'has-error': errors.vehicle }">
                                <option value="">Choose a vehicle</option>
                                <template x-for="v in vehicles" :key="v.id"><option :value="String(v.id)" x-text="v.code + ' · ' + v.type + ' · ' + cap(v.capacity)"></option></template>
                            </select>
                            <p class="lgh-err" id="tripVehicleErr" x-show="errors.vehicle" x-cloak x-text="errors.vehicle" role="alert"></p>
                        </div>
                        <div class="lgh-form__row">
                            <div class="lgh-field">
                                <label for="tripDeparture">Departure <span aria-hidden="true">*</span></label>
                                <input id="tripDeparture" type="datetime-local" x-model="departure" @input="errors.departure = ''" :min="now"
                                       :aria-invalid="!!errors.departure" aria-describedby="tripDepartureErr" :class="{ 'has-error': errors.departure }">
                                <p class="lgh-err" id="tripDepartureErr" x-show="errors.departure" x-cloak x-text="errors.departure" role="alert"></p>
                            </div>
                            <div class="lgh-field">
                                <label for="tripExpectedArrival">Expected arrival <span aria-hidden="true">*</span></label>
                                <input id="tripExpectedArrival" type="datetime-local" x-model="expectedArrival" @input="errors.expected_arrival_at = ''" :min="departure"
                                       :aria-invalid="!!errors.expected_arrival_at" aria-describedby="tripArrivalErr" :class="{ 'has-error': errors.expected_arrival_at }">
                                <p class="lgh-err" id="tripArrivalErr" x-show="errors.expected_arrival_at" x-cloak x-text="errors.expected_arrival_at" role="alert"></p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="lgh-card lgh-panel" aria-labelledby="loadTitle">
                <header class="lgh-panel__head">
                    <span class="lgh-tile lgh-tone-lilac"><x-logistics.icon name="package" :size="20" /></span>
                    <div>
                        <h2 id="loadTitle">Parcels to load</h2>
                        <p aria-live="polite" x-text="parcels.length ? chosen.length + ' of ' + parcels.length + ' parcels selected' : 'Parcels that are ready for the route show here.'"></p>
                    </div>
                    <label class="lgh-search lgh-panel__search" x-show="parcels.length > 8" x-cloak>
                        <span class="lgh-sr">Search parcels</span>
                        <x-logistics.icon name="search" :size="18" />
                        <input type="search" x-model="q" @keydown.enter.prevent @keydown.escape="q = ''" autocomplete="off" maxlength="100" placeholder="Search tracking number or city">
                    </label>
                </header>
                <p class="lgh-err lgh-panel__err" x-show="errors.parcels" x-cloak x-text="errors.parcels" role="alert"></p>

                <div class="lgh-table-wrap" x-show="visible.length" x-cloak>
                    <table class="lgh-table lgh-table--load">
                        <thead>
                            <tr>
                                <th scope="col" class="lgh-check"><input type="checkbox" :checked="allPicked" @change="toggleAll($event.target.checked)"
                                    x-effect="$el.indeterminate = visible.some(p => isPicked(p)) && !allPicked" aria-label="Select all parcels in this list"></th>
                                <th scope="col">Tracking number</th>
                                <th scope="col">Delivery city</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="p in visible" :key="p.order">
                                <tr class="lgh-row" :class="{ 'is-selected': isPicked(p) }" @click="if (!$event.target.closest('input')) toggle(p)">
                                    <td class="lgh-check"><input type="checkbox" :checked="isPicked(p)" @change="toggle(p)" :aria-label="'Select ' + p.tracking"></td>
                                    <td data-label="Tracking number"><span class="lgh-name" x-text="p.tracking"></span></td>
                                    <td data-label="Delivery city" x-text="p.destination"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="lgh-empty" x-show="!destination" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="map-pin" :size="26" /></span>
                    <strong>Choose where the trip goes</strong>
                    <p>The parcels waiting for that destination show here, ready to load.</p>
                </div>
                <div class="lgh-empty" x-show="destination && !parcels.length" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                    <strong>No parcels ready for this route</strong>
                    <p>Parcels show up here after they are sorted for this destination. Check Parcel operations, or pick another destination.</p>
                    <a href="{{ route('lgp.province.parcels') }}" class="lgh-btn lgh-btn--soft">Open parcel operations</a>
                </div>
                <div class="lgh-empty" x-show="destination && parcels.length && !visible.length" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No parcels match your search</strong>
                    <p>Check the tracking number for typos.</p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="q = ''">Clear filters</button>
                </div>
            </section>

            {{-- Stays in view while the page scrolls --}}
            <div class="lgh-bar">
                <p class="lgh-bar__sum" aria-live="polite" x-text="destination ? chosen.length + (chosen.length === 1 ? ' parcel' : ' parcels') + ' will be loaded' : 'Choose a destination to start'"></p>
                <span class="lgh-spacer"></span>
                <a href="{{ $listUrl }}" class="lgh-btn lgh-btn--soft">Cancel</a>
                <button type="submit" class="lgh-btn lgh-btn--primary" :disabled="blocked">Create trip</button>
            </div>
        </form>
    </div>
</x-logistics.shell>
