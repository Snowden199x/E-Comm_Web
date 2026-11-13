{{--
    Province Hub > Vehicles. Company trucks, vans and L300s of this Province Hub. A vehicle is picked for each trip, not tied to one driver.
    Fed by PortalController::provinceVehicles (vehicles from vehicleCard, vehicle types, and the JSON urls). The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Transport Drivers. Styles: hubs-theme.css, drivers-theme.css
    (pills, callouts, facts) and vehicles-theme.css, loaded last. Dialogs sit in .lgh-layer and carry their own copy of the tokens.
    Status colors: Available = mint, On trip = sky, Maintenance = butter (waiting), Inactive = lilac.
    Search runs as you type; status chips show counts; one click on a row opens the vehicle. Each row has a visible button:
    Back in service for a vehicle in maintenance, Activate vehicle for an inactive one, otherwise View details.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no vehicles at all, nothing in a status, and no vehicles match the search. Errors: message under each field, inside the dialog, or in the top banner.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/vehicles-theme.css'])
@endpush

<x-logistics.shell title="Vehicles" role="province">
    <div class="lgh"
         x-data="{
            vehicles: @js($vehicles),
            types: @js($types),
            storeUrl: @js($storeUrl),
            updateUrl: @js($updateUrl),
            statusUrl: @js($statusUrl),
            q: '',
            tab: 'all',
            view: null,
            working: false,
            formOpen: false,
            editing: null,
            form: { code: '', plate: '', type: '', capacity: '' },
            errors: {},
            confirm: { open: false, kind: '', v: null, reason: '', error: '', alert: '' },
            labels: { available: 'Available', on_trip: 'On trip', maintenance: 'Maintenance', inactive: 'Inactive' },
            tones: { available: 'lgh-pill--mint', on_trip: 'lgh-pill--sky', maintenance: 'lgh-pill--butter', inactive: 'lgh-pill--lilac' },
            tabEmpty: {
                available: ['No available vehicles', 'A vehicle shows here while it is free for a trip. Put one back in service, or add a new one.'],
                maintenance: ['No vehicles in maintenance', 'A vehicle you set to maintenance shows here until it is back in service.'],
                inactive: ['No inactive vehicles', 'Deactivated vehicles show here. Activate one to use it for trips again.']
            },
            init() {
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.view !== null || this.formOpen || this.confirm.open, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            firstErrors(errors) { return Object.fromEntries(Object.entries(errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])); },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            async request(url, method, body) {
                const response = await fetch(url, { method, headers: this.headers(), body: JSON.stringify(body) });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const fallback = response.status === 419 ? 'Your session expired. Reload the page and try again.'
                        : (response.status === 403 ? 'This account can’t change vehicles. Sign in with the Province Hub account.'
                        : (response.status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'Could not save the change. Reload the page and try again.'));
                    const error = new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || fallback);
                    error.fields = payload.errors || null;
                    throw error;
                }
                return payload;
            },
            get searching() { return this.q.trim() !== ''; },
            get filtered() { return this.searching || this.tab !== 'all'; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.vehicles
                    .filter(v => (this.tab === 'all' || v.status === this.tab)
                        && (!s || (v.code + ' ' + v.plate + ' ' + v.type + ' ' + (v.trip || '') + ' ' + (v.driver || '')).toLowerCase().includes(s)))
                    .sort((a, b) => a.code.localeCompare(b.code));
            },
            count(k) { return k === 'all' ? this.vehicles.length : this.vehicles.filter(v => v.status === k).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            cap(n) { return Number(n) + (Number(n) === 1 ? ' ton' : ' tons'); },
            fmt(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Not set'; },
            go(v, event) { if (!event.target.closest('a, button')) this.openView(v); },
            openView(v) { this.view = v; },
            closeView() { this.view = null; },
            closeTop() { if (this.confirm.open) this.confirm.open = false; else if (this.formOpen) this.formOpen = false; else if (this.view) this.view = null; },
            nextCode() {
                const max = Math.max(0, ...this.vehicles.map(v => parseInt(v.code.replace(/\D/g, ''), 10) || 0));
                return 'TRK-' + String(max + 1).padStart(3, '0');
            },
            // Philippine plate: three letters and three or four digits, stored as ABC 1234.
            normPlate(p) {
                const m = p.toUpperCase().replace(/\s+/g, '').match(/^([A-Z]{3})(\d{3,4})$/);
                return m ? m[1] + ' ' + m[2] : null;
            },
            openAdd() {
                this.editing = null; this.errors = {};
                this.form = { code: this.nextCode(), plate: '', type: '', capacity: '' };
                this.formOpen = true;
                this.$nextTick(() => document.getElementById('vehPlate')?.focus());
            },
            openEdit(v) {
                if (v.status === 'on_trip') return;
                this.view = null; this.editing = v; this.errors = {};
                this.form = { code: v.code, plate: v.plate, type: v.type, capacity: String(v.capacity) };
                this.formOpen = true;
                this.$nextTick(() => document.getElementById('vehPlate')?.focus());
            },
            validate() {
                const e = {}, f = this.form, others = this.vehicles.filter(v => v !== this.editing);
                const code = f.code.trim().toUpperCase(), plate = this.normPlate(f.plate), cap = Number(f.capacity);
                if (!code) e.code = 'Vehicle code is empty. Enter a code, like TRK-010.';
                else if (!/^[A-Z0-9-]+$/.test(code)) e.code = 'Vehicle code has symbols that aren’t allowed. Use only letters, numbers and dashes.';
                else if (others.some(v => v.code === code)) e.code = 'This vehicle code is already used by another vehicle. Enter a different code.';
                if (!f.plate.trim()) e.plate = 'Plate number is empty. Enter the plate number, like ABC 1234.';
                else if (!plate) e.plate = 'Plate number isn’t in the right format. Use three letters and three or four digits, like ABC 1234.';
                else if (others.some(v => v.plate === plate)) e.plate = 'This plate number is already registered to another vehicle. Check the plate number.';
                if (!f.type) e.type = 'Vehicle type is not chosen. Pick one from the list.';
                if (f.capacity === '') e.capacity = 'Capacity is empty. Enter the capacity in tons, from 0.5 to 40.';
                else if (!(cap >= 0.5 && cap <= 40)) e.capacity = 'Capacity is out of range. Enter the capacity in tons, from 0.5 to 40.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save(event) {
                if (this.working) return;
                if (!this.validate()) { this.$nextTick(() => document.querySelector('[data-vehicle-form] [aria-invalid=true]')?.focus()); return; }
                const f = this.form, button = event.submitter, adding = !this.editing;
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(
                        adding ? this.storeUrl : this.updateUrl.replace('__vehicle__', this.editing.id),
                        adding ? 'POST' : 'PUT',
                        { code: f.code.trim().toUpperCase(), plate: this.normPlate(f.plate), type: f.type, capacity: Number(f.capacity) }
                    );
                    if (adding) { this.vehicles.push(payload.vehicle); this.clearFilters(); }
                    else Object.assign(this.editing, payload.vehicle);
                    this.formOpen = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) {
                    if (error.fields) { this.errors = this.firstErrors(error.fields); this.$dispatch('lg-toast', { type: 'error', message: 'Fix the highlighted fields and save the vehicle again.' }); }
                    else this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not save the vehicle. Try again.') });
                } finally { this.working = false; this.busy(button, false); }
            },
            async updateStatus(v, status, reason) {
                const payload = await this.request(this.statusUrl.replace('__vehicle__', v.id), 'PATCH', { status: status, maintenance_note: reason || '' });
                Object.assign(v, payload.vehicle);
                this.$dispatch('lg-toast', { type: 'success', message: payload.message });
            },
            // Back in service and Activate. Both make the vehicle available again.
            async makeAvailable(v, event) {
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true);
                try { await this.updateStatus(v, 'available'); this.view = null; }
                catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not update the vehicle. Try again.') }); }
                finally { this.working = false; this.busy(button, false); }
            },
            askMaintenance(v) { this.confirm = { open: true, kind: 'maintenance', v: v, reason: '', error: '', alert: '' }; this.$nextTick(() => document.getElementById('vehReason')?.focus()); },
            askDeactivate(v) { this.confirm = { open: true, kind: 'deactivate', v: v, reason: '', error: '', alert: '' }; },
            async submitConfirm(event) {
                const c = this.confirm, v = c.v;
                if (this.working) return;
                if (v.status === 'on_trip') { c.alert = v.code + ' is on a trip. Change its status after the trip ends.'; return; }
                if (c.kind === 'maintenance' && c.reason.trim().length < 5) {
                    c.error = 'Reason is too short. Write at least 5 characters so the team knows what the vehicle needs.';
                    document.getElementById('vehReason')?.focus();
                    return;
                }
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); c.error = ''; c.alert = '';
                try {
                    await this.updateStatus(v, c.kind === 'maintenance' ? 'maintenance' : 'inactive', c.kind === 'maintenance' ? c.reason.trim() : '');
                    c.open = false; this.view = null;
                } catch (error) { c.alert = this.failure(error, 'Could not update the vehicle. Try again.'); }
                finally { this.working = false; this.busy(button, false); }
            }
         }"
         @keydown.escape.window="closeTop()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Vehicles</h1>
                <p>Company trucks and vans of {{ $hub['name'] }}. Each trip picks one, so a vehicle isn’t tied to a driver.</p>
            </div>
            <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()">
                <x-logistics.icon name="plus" :size="16" /> Add vehicle
            </button>
        </header>

        @if (count($vehicles))
            {{-- Status chips with counts, then search --}}
            <div class="lgh-chips" role="group" aria-label="Filter vehicles by status">
                @foreach (['all' => 'All', 'available' => 'Available', 'on_trip' => 'On trip', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'] as $key => $text)
                    <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                        {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                    </button>
                @endforeach
                <button type="button" class="lgh-chip lgh-chip--clear" x-show="searching" x-cloak @click="q = ''">
                    <x-logistics.icon name="x" :size="14" /> Clear search
                </button>
            </div>

            <div class="lgh-toolbar">
                <label class="lgh-search">
                    <span class="lgh-sr">Search vehicles</span>
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search code, plate, type, trip or driver">
                </label>
                <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' vehicle' : ' vehicles')"></span>
            </div>

            <section class="lgh-card lgh-table-card" aria-label="Vehicles">
                <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                    <table class="lgh-table lgh-table--vehicles">
                        <thead>
                            <tr>
                                <th scope="col">Vehicle</th>
                                <th scope="col">Type</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="is-num">Trips done</th>
                                <th scope="col"><span class="lgh-sr">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="v in shown" :key="v.id">
                                <tr class="lgh-row" @click="go(v, $event)">
                                    <td>
                                        <button type="button" class="lgh-name lgh-name--btn" @click="openView(v)" x-text="v.code"></button>
                                        <small x-text="v.plate"></small>
                                    </td>
                                    <td data-label="Type">
                                        <span x-text="v.type"></span>
                                        <small x-text="cap(v.capacity) + ' capacity'"></small>
                                    </td>
                                    <td data-label="Status">
                                        <span class="lgh-pill" :class="tones[v.status]" x-text="labels[v.status]"></span>
                                        <template x-if="v.status === 'on_trip' && v.trip"><small x-text="v.trip + (v.driver ? ' · ' + v.driver : '')"></small></template>
                                        <template x-if="v.status === 'maintenance' && v.since"><small x-text="'Since ' + fmt(v.since)"></small></template>
                                    </td>
                                    <td class="is-num" data-label="Trips done" x-text="v.done"></td>
                                    <td class="lgh-actions">
                                        <div class="lgh-actions__row">
                                            <template x-if="v.status === 'maintenance' || v.status === 'inactive'">
                                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--primary" @click="makeAvailable(v, $event)"
                                                        :aria-label="(v.status === 'maintenance' ? 'Put back in service: ' : 'Activate ') + v.code">
                                                    <span x-text="v.status === 'maintenance' ? 'Back in service' : 'Activate vehicle'"></span>
                                                </button>
                                            </template>
                                            <template x-if="v.status === 'available' || v.status === 'on_trip'">
                                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--soft" @click="openView(v)" :aria-label="'View details for ' + v.code">
                                                    View details <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                                </button>
                                            </template>
                                            <button type="button" class="lgh-btn lgh-btn--small lgh-btn--ghost" @click="openEdit(v)" :disabled="v.status === 'on_trip'"
                                                    :title="v.status === 'on_trip' ? 'Edit after the trip ends' : ''" :aria-label="'Edit ' + v.code">
                                                <x-logistics.icon name="edit" :size="14" /> Edit vehicle
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Nothing matches the search --}}
                <div class="lgh-empty" x-show="!shown.length && searching" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No vehicles match your search</strong>
                    <p>Check the spelling, or search by plate, type, trip or driver.<span x-show="tab !== 'all'"> You are searching in one status only.</span></p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
                </div>

                {{-- Nothing in this status --}}
                <div class="lgh-empty" x-show="!shown.length && !searching && tab !== 'all' && tab !== 'on_trip'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong x-text="(tabEmpty[tab] || [''])[0]"></strong>
                    <p x-text="(tabEmpty[tab] || ['', ''])[1]"></p>
                    <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = 'all'">Show all vehicles</button>
                </div>
                <div class="lgh-empty" x-show="!shown.length && !searching && tab === 'on_trip'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-sky"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong>No vehicles on a trip</strong>
                    <p>A vehicle shows here while its trip is scheduled or in transit.</p>
                    <a href="{{ route('lgp.province.trips.create') }}" class="lgh-btn lgh-btn--soft"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                </div>
            </section>
        @else
            {{-- No vehicles at all --}}
            <section class="lgh-card" aria-label="Vehicles">
                <div class="lgh-empty" role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong>No vehicles yet</strong>
                    <p>Add the trucks, vans and L300s your drivers use. You pick one when you create a trip.</p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()"><x-logistics.icon name="plus" :size="16" /> Add vehicle</button>
                </div>
            </section>
        @endif

        {{-- Vehicle details. Status actions (maintenance, deactivate, back in service, activate) happen here. --}}
        <div class="lgh-layer" x-show="view" x-cloak>
            <div class="lgh-backdrop" @click="closeView()"></div>
            <div class="lgh-dialog lgh-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="vehTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="vehTitle" x-text="view ? view.code + ' · ' + view.plate : ''"></h2>
                        <p><span class="lgh-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lgh-close" @click="closeView()" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <template x-if="view && view.status === 'on_trip'">
                        <div class="lgh-callout lgh-callout--sky">
                            <x-logistics.icon name="truck" :size="18" />
                            <p>On trip <strong x-text="view.trip"></strong><span x-show="view.driver"> with <strong x-text="view.driver"></strong></span>. You can edit it, set maintenance or deactivate it after the trip ends.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'maintenance'">
                        <div class="lgh-callout">
                            <x-logistics.icon name="alert" :size="18" />
                            <p><strong>In maintenance since <span x-text="fmt(view.since)"></span>.</strong> <span x-text="view.note"></span> It can’t be assigned to trips until it is back in service.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'inactive'">
                        <div class="lgh-callout lgh-callout--lilac">
                            <x-logistics.icon name="info" :size="18" />
                            <p>This vehicle is deactivated. Activate it to assign it to trips again.</p>
                        </div>
                    </template>

                    <dl class="lgh-facts" x-show="view">
                        <div><dt>Vehicle code</dt><dd x-text="view && view.code"></dd></div>
                        <div><dt>Plate number</dt><dd x-text="view && view.plate"></dd></div>
                        <div><dt>Type</dt><dd x-text="view && view.type"></dd></div>
                        <div><dt>Capacity</dt><dd x-text="view && cap(view.capacity)"></dd></div>
                        <div><dt>Province Hub</dt><dd>{{ $hub['name'] }}</dd></div>
                        <div><dt>Completed trips</dt><dd x-text="view && view.done"></dd></div>
                    </dl>

                    <footer class="lgh-dialog__foot lgh-dialog__foot--sticky">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="closeView()">Close</button>
                        <template x-if="view && view.status !== 'on_trip'">
                            <button type="button" class="lgh-btn lgh-btn--soft" @click="openEdit(view)">Edit vehicle</button>
                        </template>
                        <template x-if="view && view.status === 'available'">
                            <button type="button" class="lgh-btn lgh-btn--soft" @click="askMaintenance(view)">Set maintenance</button>
                        </template>
                        <template x-if="view && (view.status === 'available' || view.status === 'maintenance')">
                            <button type="button" class="lgh-btn lgh-btn--danger" @click="askDeactivate(view)">Deactivate vehicle</button>
                        </template>
                        <template x-if="view && view.status === 'maintenance'">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="makeAvailable(view, $event)">Back in service</button>
                        </template>
                        <template x-if="view && view.status === 'inactive'">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="makeAvailable(view, $event)">Activate vehicle</button>
                        </template>
                    </footer>
                </div>
            </div>
        </div>

        {{-- Add or edit a vehicle. The code is fixed once the vehicle exists. --}}
        <div class="lgh-layer" x-show="formOpen" x-cloak>
            <div class="lgh-backdrop" @click="formOpen = false"></div>
            <div class="lgh-dialog" role="dialog" aria-modal="true" aria-labelledby="vehFormTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="vehFormTitle" x-text="editing ? 'Edit ' + editing.code : 'Add vehicle'"></h2>
                        <p x-text="editing ? 'Changes apply to future trips.' : 'A new vehicle starts as available for trips.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="formOpen = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form @submit.prevent="save($event)" novalidate data-vehicle-form class="lgh-dialog__body lgh-form">
                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="vehCode">Vehicle code <span aria-hidden="true">*</span></label>
                            <input id="vehCode" x-model="form.code" @input="errors.code = ''" :disabled="!!editing" maxlength="40" autocomplete="off" style="text-transform: uppercase"
                                   :aria-invalid="!!errors.code" aria-describedby="vehCodeErr" :class="{ 'has-error': errors.code }">
                            <p class="lgh-err" id="vehCodeErr" x-show="errors.code" x-cloak x-text="errors.code" role="alert"></p>
                            <p class="lgh-hint" x-show="editing" x-cloak>The code can’t change once the vehicle exists.</p>
                        </div>
                        <div class="lgh-field">
                            <label for="vehPlate">Plate number <span aria-hidden="true">*</span></label>
                            <input id="vehPlate" x-model="form.plate" @input="errors.plate = ''" maxlength="10" placeholder="ABC 1234" autocomplete="off" style="text-transform: uppercase"
                                   :aria-invalid="!!errors.plate" aria-describedby="vehPlateErr" :class="{ 'has-error': errors.plate }">
                            <p class="lgh-err" id="vehPlateErr" x-show="errors.plate" x-cloak x-text="errors.plate" role="alert"></p>
                        </div>
                    </div>
                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="vehType">Vehicle type <span aria-hidden="true">*</span></label>
                            <select id="vehType" x-model="form.type" @change="errors.type = ''" :aria-invalid="!!errors.type" aria-describedby="vehTypeErr" :class="{ 'has-error': errors.type }">
                                <option value="">Choose a type</option>
                                <template x-for="t in types" :key="t"><option :value="t" x-text="t"></option></template>
                            </select>
                            <p class="lgh-err" id="vehTypeErr" x-show="errors.type" x-cloak x-text="errors.type" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="vehCap">Capacity in tons <span aria-hidden="true">*</span></label>
                            <input id="vehCap" type="number" inputmode="decimal" min="0.5" max="40" step="0.5" x-model="form.capacity" @input="errors.capacity = ''" placeholder="15"
                                   :aria-invalid="!!errors.capacity" aria-describedby="vehCapErr" :class="{ 'has-error': errors.capacity }">
                            <p class="lgh-err" id="vehCapErr" x-show="errors.capacity" x-cloak x-text="errors.capacity" role="alert"></p>
                        </div>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="formOpen = false">Cancel</button>
                        <button type="submit" class="lgh-btn lgh-btn--primary" x-text="editing ? 'Save changes' : 'Add vehicle'"></button>
                    </footer>
                </form>
            </div>
        </div>

        {{-- Maintenance needs a short reason; deactivate is a plain confirm. --}}
        <div class="lgh-layer" x-show="confirm.open" x-cloak style="z-index: 80">
            <div class="lgh-backdrop" @click="confirm.open = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="vehConfirmTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="vehConfirmTitle" x-text="(confirm.kind === 'maintenance' ? 'Set maintenance for ' : 'Deactivate ') + (confirm.v ? confirm.v.code : '') + '?'"></h2>
                        <p x-text="confirm.kind === 'maintenance' ? 'It can’t be assigned to new trips until it is back in service.' : 'It is removed from trip assignment until you activate it again.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirm.open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-field" x-show="confirm.kind === 'maintenance'">
                        <label for="vehReason">What is the maintenance for? <span aria-hidden="true">*</span></label>
                        <textarea id="vehReason" rows="3" maxlength="200" x-model="confirm.reason" @input="confirm.error = ''" placeholder="For example: brake check or tire replacement"
                                  :aria-invalid="confirm.kind === 'maintenance' && !!confirm.error" aria-describedby="vehReasonErr" :class="{ 'has-error': confirm.kind === 'maintenance' && confirm.error }"></textarea>
                        <p class="lgh-err" id="vehReasonErr" x-show="confirm.kind === 'maintenance' && confirm.error" x-cloak x-text="confirm.error" role="alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--rose" x-show="confirm.alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="confirm.alert"></p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirm.open = false">Cancel</button>
                        <button type="button" class="lgh-btn" :class="confirm.kind === 'maintenance' ? 'lgh-btn--primary' : 'lgh-btn--danger-solid'" @click="submitConfirm($event)"
                                x-text="confirm.kind === 'maintenance' ? 'Set maintenance' : 'Deactivate vehicle'"></button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
