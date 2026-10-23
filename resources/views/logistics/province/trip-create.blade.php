{{-- Province Hub trip form. Facility, driver, vehicle and parcel options are loaded from scoped database records. --}}
<x-logistics.shell title="Create Transport Trip" role="province">
    <div class="lg-page"
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
            errors: {},
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
            get chosen() { return this.parcels.filter(p => this.selected.includes(p.order)); },
            get allPicked() { return this.parcels.length > 0 && this.chosen.length === this.parcels.length; },
            setType(t) {
                this.type = t; this.origin = String(this.hubId); this.destination = ''; this.errors = {};
                this.selected = [];
            },
            onOrigin() {
                this.destination = Number(this.origin) === Number(this.hubId) ? '' : String(this.hubId);
                this.selected = this.parcels.map(p => p.order);
            },
            onDestination() { this.selected = this.parcels.map(p => p.order); },
            toggleAll(on) { this.selected = on ? this.parcels.map(p => p.order) : []; },
            cap(n) { return Number(n) + (Number(n) === 1 ? ' ton' : ' tons'); },
            fmtDate(iso) { return new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); },
            validate() {
                const e = {};
                if (!this.destination) e.destination = 'Choose where this trip goes.';
                if (!this.driver) e.driver = 'Choose an available driver.';
                else {
                    const d = this.drivers.find(x => Number(x.id) === Number(this.driver));
                    // The licence is re-checked when a trip is assigned (spec 6B).
                    if (d && this.departure && d.expiry < this.departure.slice(0, 10)) e.driver = d.name + ' has a licence that expires on ' + this.fmtDate(d.expiry) + ', before this departure.';
                }
                if (!this.vehicle) e.vehicle = 'Choose an available vehicle.';
                if (!this.departure) e.departure = 'Set the scheduled departure.';
                else if (this.departure < this.now) e.departure = 'The departure cannot be in the past.';
                if (!this.expectedArrival || this.expectedArrival <= this.departure) e.expected_arrival_at = 'Set an expected arrival after departure.';
                if (this.destination && this.selected.length === 0) e.parcels = 'Select at least one parcel to load.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save() {
                if (!this.validate()) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                try {
                    const response = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({ type: this.type, origin_id: Number(this.origin), destination_id: Number(this.destination), driver_id: Number(this.driver), vehicle_id: Number(this.vehicle), departure: this.departure, expected_arrival_at: this.expectedArrival, orders: this.selected.map(Number) })
                    });
                    const payload = await response.json();
                    if (!response.ok) { this.errors = payload.errors || {}; throw new Error(payload.message || 'Could not create the trip.'); }
                    window.location.href = this.listUrl;
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
            }
         }">
        <div class="lg-page-head">
            <div>
                <a href="{{ $listUrl }}" class="lg-facility-back">← Transport Trips</a>
                <h1>Create Transport Trip</h1>
                <p>The trip ID is assigned after saving. Scan each parcel at departure and arrival before recording the trip milestones.</p>
            </div>
        </div>

        <form @submit.prevent="save()" novalidate>
            <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
                <section class="lg-card" aria-labelledby="routeTitle">
                    <div class="lg-card__head"><h2 class="lg-section-title" id="routeTitle">Route</h2></div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <fieldset style="border:0;padding:0;margin:0;display:grid;gap:8px">
                            <legend class="lg-section-sub" style="padding:0;margin-bottom:8px">Trip type</legend>
                            <label class="lg-radio">
                                <input type="radio" name="tripType" value="station_transfer" :checked="type === 'station_transfer'" @change="setType('station_transfer')">
                                <span><strong>Station transfer</strong><span class="lg-cell-sub" style="display:block">Between a Station Hub and {{ $hub['name'] }}</span></span>
                            </label>
                            <label class="lg-radio">
                                <input type="radio" name="tripType" value="inter_province" :checked="type === 'inter_province'" @change="setType('inter_province')">
                                <span><strong>Inter-province</strong><span class="lg-cell-sub" style="display:block">From {{ $hub['name'] }} to another Province Hub</span></span>
                            </label>
                        </fieldset>

                        <div class="lg-field">
                            <label for="tripOrigin">From</label>
                            <select id="tripOrigin" class="lg-select" x-model="origin" @change="onOrigin()" :disabled="type === 'inter_province'">
                                <template x-for="o in originOptions" :key="o.id"><option :value="String(o.id)" x-text="o.name"></option></template>
                            </select>
                        </div>
                        <div class="lg-field">
                            <label for="tripDest">To <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="tripDest" class="lg-select" x-model="destination" @change="onDestination()" :disabled="Number(origin) !== Number(hubId) && type === 'station_transfer'" :aria-invalid="!!errors.destination" aria-describedby="tripDestErr">
                                <option value="">Choose a destination</option>
                                <template x-for="o in destinationOptions" :key="o.id"><option :value="String(o.id)" x-text="o.name"></option></template>
                            </select>
                            <p class="lg-error" id="tripDestErr" x-show="errors.destination" x-text="errors.destination" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!destinationOptions.length" x-cloak x-text="type === 'inter_province' ? 'There is no other active Province Hub to send parcels to.' : 'Add and activate a Station Hub first.'"></p>
                        </div>
                    </div>
                </section>

                <section class="lg-card" aria-labelledby="crewTitle">
                    <div class="lg-card__head">
                        <div>
                            <h2 class="lg-section-title" id="crewTitle">Driver, vehicle and departure</h2>
                            <p class="lg-section-sub">Only available drivers and vehicles are listed.</p>
                        </div>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="tripDriver">Transport driver <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="tripDriver" class="lg-select" x-model="driver" :aria-invalid="!!errors.driver" aria-describedby="tripDriverErr">
                                <option value="">Choose a driver</option>
                                <template x-for="d in drivers" :key="d.id"><option :value="String(d.id)" x-text="d.name + ' · ' + d.category"></option></template>
                            </select>
                            <p class="lg-error" id="tripDriverErr" x-show="errors.driver" x-text="errors.driver" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!drivers.length" x-cloak>No driver is available right now.</p>
                        </div>
                        <div class="lg-field">
                            <label for="tripVehicle">Vehicle <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="tripVehicle" class="lg-select" x-model="vehicle" :aria-invalid="!!errors.vehicle" aria-describedby="tripVehicleErr">
                                <option value="">Choose a vehicle</option>
                                <template x-for="v in vehicles" :key="v.id"><option :value="String(v.id)" x-text="v.code + ' · ' + v.type + ' · ' + cap(v.capacity)"></option></template>
                            </select>
                            <p class="lg-error" id="tripVehicleErr" x-show="errors.vehicle" x-text="errors.vehicle" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!vehicles.length" x-cloak>No vehicle is available right now.</p>
                        </div>
                        <div class="lg-field">
                            <label for="tripDeparture">Scheduled departure <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="tripDeparture" type="datetime-local" class="lg-input" x-model="departure" :min="now" :aria-invalid="!!errors.departure" aria-describedby="tripDepartureErr">
                            <p class="lg-error" id="tripDepartureErr" x-show="errors.departure" x-text="errors.departure" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="tripExpectedArrival">Expected arrival <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="tripExpectedArrival" type="datetime-local" class="lg-input" x-model="expectedArrival" :min="departure" :aria-invalid="!!errors.expected_arrival_at" aria-describedby="tripArrivalErr">
                            <p class="lg-error" id="tripArrivalErr" x-show="errors.expected_arrival_at" x-text="errors.expected_arrival_at" role="alert"></p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="lg-card" aria-labelledby="loadTitle" style="margin-top:16px">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="loadTitle">Parcels to load</h2>
                        <p class="lg-section-sub" aria-live="polite" x-text="parcels.length ? chosen.length + ' of ' + parcels.length + ' parcels selected' : 'Ready parcels for the chosen route show here.'"></p>
                    </div>
                </div>
                <p class="lg-error" style="padding:0 24px 8px" x-show="errors.parcels" x-text="errors.parcels" role="alert"></p>

                <div class="lg-table-wrap" x-show="parcels.length">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead>
                            <tr>
                                <th style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll($event.target.checked)"
                                    x-effect="$el.indeterminate = chosen.length > 0 && !allPicked" aria-label="Select all ready parcels"></th>
                                <th>Tracking number</th><th>Delivery area</th><th class="is-num">Shipment weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="p in parcels" :key="p.order">
                                <tr :class="{ 'is-selected': selected.includes(p.order) }">
                                    <td class="is-check"><input type="checkbox" class="lg-check" :value="p.order" x-model="selected" :aria-label="'Select ' + p.order"></td>
                                    <td data-label="Tracking number"><strong x-text="p.tracking"></strong></td>
                                    <td data-label="Delivery area" x-text="p.area"></td>
                                    <td data-label="Shipment weight" class="is-num">Not recorded</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <x-logistics.empty x-show="!destination" x-cloak icon="package" title="Choose where the trip goes" text="The parcels waiting for that destination appear here, ready to load." />
                <x-logistics.empty x-show="destination && !parcels.length" x-cloak icon="package" title="No parcels ready for this route" text="Parcels show up here after sorting assigns them to this destination. You can still come back later." />
            </section>

            <div class="lg-actions" style="justify-content:flex-end;margin-top:16px">
                <a href="{{ $listUrl }}" class="lg-btn lg-btn--outline">Cancel</a>
                <button type="submit" class="lg-btn">Create trip</button>
            </div>
        </form>
    </div>
</x-logistics.shell>
