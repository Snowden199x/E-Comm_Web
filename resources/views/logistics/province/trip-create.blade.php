{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec: section 6B (trip flow and rules) and 8 (Province Hub > Create Transport Trip).
     Trip type decides the route options. Only available drivers and vehicles are offered. The new trip is handed to the list through sessionStorage (no database yet). --}}
<x-logistics.shell title="Create Transport Trip" role="province">
    <div class="lg-page"
         x-data="{
            hubName: @js($hub['name']),
            stations: @js($stations),
            otherHubs: @js($otherHubs),
            drivers: @js($drivers),
            vehicles: @js($vehicles),
            parcelMap: @js($parcels),
            nextId: @js($nextId),
            now: @js($now),
            listUrl: @js($listUrl),
            type: 'station_transfer',
            origin: @js($hub['name']),
            destination: '',
            driver: '',
            vehicle: '',
            departure: @js($defaultDeparture),
            selected: [],
            errors: {},
            get originOptions() { return this.type === 'inter_province' ? [this.hubName] : [this.hubName].concat(this.stations.map(s => s.name)); },
            get destinationOptions() {
                if (this.type === 'inter_province') return this.otherHubs.map(h => h.name);
                return this.origin === this.hubName ? this.stations.map(s => s.name) : [this.hubName];
            },
            slugOf(name) {
                const f = this.stations.concat(this.otherHubs).find(x => x.name === name);
                return f ? f.slug : '';
            },
            // Which parcels are ready: from the hub to the chosen destination, or from the chosen station to the hub.
            get parcelKey() {
                if (!this.origin || !this.destination) return '';
                return this.origin === this.hubName ? 'to:' + this.slugOf(this.destination) : 'from:' + this.slugOf(this.origin);
            },
            get parcels() { return this.parcelMap[this.parcelKey] || []; },
            get chosen() { return this.parcels.filter(p => this.selected.includes(p.order)); },
            get weight() { return this.chosen.reduce((sum, p) => sum + p.weight, 0); },
            get allPicked() { return this.parcels.length > 0 && this.chosen.length === this.parcels.length; },
            setType(t) {
                this.type = t; this.origin = this.hubName; this.destination = ''; this.errors = {};
                this.selected = [];
            },
            onOrigin() {
                this.destination = this.origin === this.hubName ? '' : this.hubName;
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
                    const d = this.drivers.find(x => x.name === this.driver);
                    // The licence is re-checked when a trip is assigned (spec 6B).
                    if (d && this.departure && d.expiry < this.departure.slice(0, 10)) e.driver = d.name + ' has a licence that expires on ' + this.fmtDate(d.expiry) + ', before this departure.';
                }
                if (!this.vehicle) e.vehicle = 'Choose an available vehicle.';
                if (!this.departure) e.departure = 'Set the scheduled departure.';
                else if (this.departure < this.now) e.departure = 'The departure cannot be in the past.';
                if (this.destination && this.selected.length === 0) e.parcels = 'Select at least one parcel to load.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            save() {
                if (!this.validate()) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                const trip = {
                    id: this.nextId, type: this.type, origin: this.origin, destination: this.destination,
                    driver: this.driver, vehicle: this.vehicle, parcels: this.selected.length, status: 'scheduled',
                    scheduled: this.departure, departed: null, arrived: null, note: ''
                };
                try { sessionStorage.setItem('lgp.newTrip', JSON.stringify(trip)); } catch (e) {}
                window.location.href = this.listUrl;
            }
         }">
        <div class="lg-page-head">
            <div>
                <a href="{{ $listUrl }}" class="lg-facility-back">← Transport Trips</a>
                <h1>Create Transport Trip</h1>
                <p>Trip {{ $nextId }}. It starts as scheduled. The driver confirms departure in the driver app.</p>
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
                                <template x-for="o in originOptions" :key="o"><option :value="o" x-text="o"></option></template>
                            </select>
                        </div>
                        <div class="lg-field">
                            <label for="tripDest">To <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="tripDest" class="lg-select" x-model="destination" @change="onDestination()" :disabled="origin !== hubName && type === 'station_transfer'" :aria-invalid="!!errors.destination" aria-describedby="tripDestErr">
                                <option value="">Choose a destination</option>
                                <template x-for="o in destinationOptions" :key="o"><option :value="o" x-text="o"></option></template>
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
                                <template x-for="d in drivers" :key="d.name"><option :value="d.name" x-text="d.name + ' · ' + d.category"></option></template>
                            </select>
                            <p class="lg-error" id="tripDriverErr" x-show="errors.driver" x-text="errors.driver" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!drivers.length" x-cloak>No driver is available right now.</p>
                        </div>
                        <div class="lg-field">
                            <label for="tripVehicle">Vehicle <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="tripVehicle" class="lg-select" x-model="vehicle" :aria-invalid="!!errors.vehicle" aria-describedby="tripVehicleErr">
                                <option value="">Choose a vehicle</option>
                                <template x-for="v in vehicles" :key="v.code"><option :value="v.code" x-text="v.code + ' · ' + v.type + ' · ' + cap(v.capacity)"></option></template>
                            </select>
                            <p class="lg-error" id="tripVehicleErr" x-show="errors.vehicle" x-text="errors.vehicle" role="alert"></p>
                            <p class="lg-cell-sub" x-show="!vehicles.length" x-cloak>No vehicle is available right now.</p>
                        </div>
                        <div class="lg-field">
                            <label for="tripDeparture">Scheduled departure <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="tripDeparture" type="datetime-local" class="lg-input" x-model="departure" :min="now" :aria-invalid="!!errors.departure" aria-describedby="tripDepartureErr">
                            <p class="lg-error" id="tripDepartureErr" x-show="errors.departure" x-text="errors.departure" role="alert"></p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="lg-card" aria-labelledby="loadTitle" style="margin-top:16px">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="loadTitle">Parcels to load</h2>
                        <p class="lg-section-sub" aria-live="polite" x-text="parcels.length ? chosen.length + ' of ' + parcels.length + ' selected · ' + weight.toFixed(1) + ' kg' : 'Ready parcels for the chosen route show here.'"></p>
                    </div>
                </div>
                <p class="lg-error" style="padding:0 24px 8px" x-show="errors.parcels" x-text="errors.parcels" role="alert"></p>

                <div class="lg-table-wrap" x-show="parcels.length">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead>
                            <tr>
                                <th style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll($event.target.checked)"
                                    x-effect="$el.indeterminate = chosen.length > 0 && !allPicked" aria-label="Select all ready parcels"></th>
                                <th>Parcel</th><th>Delivery area</th><th class="is-num">Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="p in parcels" :key="p.order">
                                <tr :class="{ 'is-selected': selected.includes(p.order) }">
                                    <td class="is-check"><input type="checkbox" class="lg-check" :value="p.order" x-model="selected" :aria-label="'Select ' + p.order"></td>
                                    <td data-label="Parcel"><strong x-text="p.order"></strong></td>
                                    <td data-label="Delivery area" x-text="p.area"></td>
                                    <td data-label="Weight" class="is-num" x-text="p.weight.toFixed(1) + ' kg'"></td>
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