{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec: sections 6B and 8 (Province Hub > Vehicles).
     Company vehicles belong to the Province Hub, not to a driver: a vehicle is picked per trip. Only an available vehicle can be assigned to a new trip. --}}
<x-logistics.shell title="Vehicles" role="province">
    <div class="lg-page"
         x-data="{
            vehicles: @js($vehicles),
            types: @js($types),
            today: @js($today),
            q: '',
            tab: 'all',
            view: null,
            formOpen: false,
            editing: null,
            form: { code: '', plate: '', type: '', capacity: '' },
            errors: {},
            confirm: { open: false, kind: '', v: null, reason: '', error: '' },
            labels: { available: 'Available', on_trip: 'On trip', maintenance: 'Maintenance', inactive: 'Inactive' },
            tones: { available: 'lg-pill--green', on_trip: 'lg-pill--blue', maintenance: 'lg-pill--amber', inactive: 'lg-pill--gray' },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.vehicles
                    .filter(v => (this.tab === 'all' || v.status === this.tab)
                        && (!s || (v.code + ' ' + v.plate + ' ' + v.type + ' ' + (v.trip || '') + ' ' + (v.driver || '')).toLowerCase().includes(s)))
                    .sort((a, b) => a.code.localeCompare(b.code));
            },
            count(k) { return k === 'all' ? this.vehicles.length : this.vehicles.filter(v => v.status === k).length; },
            cap(n) { return Number(n) + (Number(n) === 1 ? ' ton' : ' tons'); },
            fmt(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            nextCode() {
                const max = Math.max(0, ...this.vehicles.map(v => parseInt(v.code.replace(/\D/g, ''), 10) || 0));
                return 'TRK-' + String(max + 1).padStart(3, '0');
            },
            // Philippine plate: three letters and three or four digits, stored as ABC 1234.
            normPlate(p) {
                const m = p.toUpperCase().replace(/\s+/g, '').match(/^([A-Z]{3})(\d{3,4})$/);
                return m ? m[1] + ' ' + m[2] : null;
            },
            openView(v) { this.view = v; },
            closeView() { this.view = null; },
            openAdd() {
                this.editing = null; this.errors = {};
                this.form = { code: this.nextCode(), plate: '', type: '', capacity: '' };
                this.formOpen = true;
                this.$nextTick(() => this.$refs.plate && this.$refs.plate.focus());
            },
            openEdit(v) {
                if (v.status === 'on_trip') return;
                this.view = null; this.editing = v; this.errors = {};
                this.form = { code: v.code, plate: v.plate, type: v.type, capacity: String(v.capacity) };
                this.formOpen = true;
                this.$nextTick(() => this.$refs.plate && this.$refs.plate.focus());
            },
            validate() {
                const e = {}, f = this.form, others = this.vehicles.filter(v => v !== this.editing);
                const code = f.code.trim().toUpperCase(), plate = this.normPlate(f.plate), cap = Number(f.capacity);
                if (!code) e.code = 'Enter a vehicle code.';
                else if (others.some(v => v.code === code)) e.code = 'This code is already used by another vehicle.';
                if (!f.plate.trim()) e.plate = 'Enter the plate number.';
                else if (!plate) e.plate = 'Use three letters and three or four digits, like ABC 1234.';
                else if (others.some(v => v.plate === plate)) e.plate = 'This plate number is already registered.';
                if (!f.type) e.type = 'Choose a vehicle type.';
                if (f.capacity === '' || !(cap >= 0.5 && cap <= 40)) e.capacity = 'Enter the capacity in tons, from 0.5 to 40.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            save() {
                if (!this.validate()) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                const f = this.form, code = f.code.trim().toUpperCase(), plate = this.normPlate(f.plate), cap = Number(f.capacity);
                if (this.editing) {
                    Object.assign(this.editing, { plate: plate, type: f.type, capacity: cap });
                    this.$dispatch('lg-toast', { type: 'success', message: code + ' updated.' });
                } else {
                    this.vehicles.push({ id: Date.now(), code: code, plate: plate, type: f.type, capacity: cap, status: 'available', done: 0, trip: null, driver: null, note: '', since: null });
                    this.q = ''; this.tab = 'all';
                    this.$dispatch('lg-toast', { type: 'success', message: code + ' added. It can be assigned to trips now.' });
                }
                this.formOpen = false;
            },
            askMaintenance(v) { this.confirm = { open: true, kind: 'maintenance', v: v, reason: '', error: '' }; },
            askDeactivate(v) { this.confirm = { open: true, kind: 'deactivate', v: v, reason: '', error: '' }; },
            submitConfirm() {
                const c = this.confirm, v = c.v;
                if (v.status === 'on_trip') return;
                if (c.kind === 'maintenance') {
                    if (c.reason.trim().length < 5) { c.error = 'Say what the vehicle is in maintenance for, so the team knows.'; return; }
                    v.status = 'maintenance'; v.note = c.reason.trim(); v.since = this.today;
                    this.$dispatch('lg-toast', { type: 'success', message: v.code + ' set to maintenance. It cannot be assigned to trips.' });
                } else {
                    v.status = 'inactive'; v.note = ''; v.since = null;
                    this.$dispatch('lg-toast', { type: 'success', message: v.code + ' deactivated.' });
                }
                c.open = false;
                this.view = null;
            },
            backInService(v) {
                v.status = 'available'; v.note = ''; v.since = null;
                this.view = null;
                this.$dispatch('lg-toast', { type: 'success', message: v.code + ' is back in service.' });
            },
            activate(v) {
                v.status = 'available';
                this.view = null;
                this.$dispatch('lg-toast', { type: 'success', message: v.code + ' activated.' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Vehicles</h1>
                <p>Company trucks, vans and L300s of {{ $hub['name'] }}. A vehicle is picked for each trip, not tied to one driver.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openAdd()"><x-logistics.icon name="plus" :size="18" /> Add vehicle</button>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Vehicle status">
            @foreach (['all' => 'All', 'available' => 'Available', 'on_trip' => 'On trip', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search code, plate, type or trip" aria-label="Search vehicles">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' vehicle' : ' vehicles')"></span>
        </div>

        <section class="lg-card" aria-label="Vehicles">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Vehicle</th><th>Type</th><th class="is-num">Capacity</th><th>Status</th>
                            <th>Current trip</th><th class="is-num">Trips done</th><th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="v in shown" :key="v.id">
                            <tr>
                                <td data-label="Vehicle"><div class="lg-cell-title" x-text="v.code"></div><div class="lg-cell-sub" x-text="v.plate"></div></td>
                                <td data-label="Type" x-text="v.type"></td>
                                <td data-label="Capacity" class="is-num" x-text="cap(v.capacity)"></td>
                                <td data-label="Status"><span class="lg-pill" :class="tones[v.status]" x-text="labels[v.status]"></span></td>
                                <td data-label="Current trip">
                                    <template x-if="v.trip"><div><div class="lg-cell-title" x-text="v.trip"></div><div class="lg-cell-sub" x-text="v.driver"></div></div></template>
                                    <template x-if="!v.trip"><span>None</span></template>
                                </td>
                                <td data-label="Trips done" class="is-num" x-text="v.done"></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openView(v)" :aria-label="'View ' + v.code"><x-logistics.icon name="eye" :size="16" /> View</button>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openEdit(v)" :disabled="v.status === 'on_trip'"
                                                :title="v.status === 'on_trip' ? 'Edit after the trip ends' : ''" :aria-label="'Edit ' + v.code"><x-logistics.icon name="edit" :size="16" /> Edit</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!vehicles.length" x-cloak icon="truck" title="No vehicles yet" text="Add the company trucks, vans and L300s your drivers use. They are picked when you create a trip.">
                <button type="button" class="lg-btn lg-btn--sm" @click="openAdd()">Add vehicle</button>
            </x-logistics.empty>
            <x-logistics.empty x-show="vehicles.length && !shown.length" x-cloak icon="search" title="No vehicles match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; tab = 'all'">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Vehicle details with the status actions. --}}
        <div class="lg-overlay" x-show="view" x-cloak @click.self="closeView()" @keydown.escape.window="closeView()">
            <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="vehTitle" x-show="view">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="vehTitle" x-text="view ? view.code + ' · ' + view.plate : ''"></h3>
                        <p x-show="view"><span class="lg-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="closeView()" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="view">
                    <template x-if="view && view.status === 'on_trip'">
                        <div class="lg-note">
                            <x-logistics.icon name="info" :size="18" />
                            <span>On trip <strong x-text="view.trip"></strong> with <strong x-text="view.driver"></strong>. It can be edited, set to maintenance or deactivated after the trip ends.</span>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'maintenance'">
                        <div class="lg-note lg-note--warn">
                            <x-logistics.icon name="alert" :size="18" />
                            <span><strong>In maintenance since <span x-text="fmt(view.since)"></span>.</strong> <span x-text="view.note"></span> It cannot be assigned to trips.</span>
                        </div>
                    </template>
                    <dl class="lg-facts" x-show="view">
                        <div><dt>Vehicle code</dt><dd x-text="view && view.code"></dd></div>
                        <div><dt>Plate number</dt><dd x-text="view && view.plate"></dd></div>
                        <div><dt>Type</dt><dd x-text="view && view.type"></dd></div>
                        <div><dt>Capacity</dt><dd x-text="view && cap(view.capacity)"></dd></div>
                        <div><dt>Province Hub</dt><dd>{{ $hub['name'] }}</dd></div>
                        <div><dt>Completed trips</dt><dd x-text="view && view.done"></dd></div>
                    </dl>
                </div>
                <div class="lg-modal__foot" x-show="view">
                    <button type="button" class="lg-btn lg-btn--outline" @click="closeView()">Close</button>
                    <template x-if="view && view.status !== 'on_trip'">
                        <button type="button" class="lg-btn lg-btn--outline" @click="openEdit(view)">Edit</button>
                    </template>
                    <template x-if="view && view.status === 'available'">
                        <button type="button" class="lg-btn lg-btn--outline" @click="askMaintenance(view)">Set maintenance</button>
                    </template>
                    <template x-if="view && (view.status === 'available' || view.status === 'maintenance')">
                        <button type="button" class="lg-btn lg-btn--danger" @click="askDeactivate(view)">Deactivate</button>
                    </template>
                    <template x-if="view && view.status === 'maintenance'">
                        <button type="button" class="lg-btn" @click="backInService(view)">Back in service</button>
                    </template>
                    <template x-if="view && view.status === 'inactive'">
                        <button type="button" class="lg-btn" @click="activate(view)">Activate</button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Add / edit vehicle. The code is fixed once the vehicle exists. --}}
        <div class="lg-overlay" x-show="formOpen" x-cloak @click.self="formOpen = false" @keydown.escape.window="formOpen = false">
            <div class="lg-modal" style="width:min(100%,520px)" role="dialog" aria-modal="true" aria-labelledby="vehFormTitle">
                <form @submit.prevent="save()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="vehFormTitle" x-text="editing ? 'Edit ' + editing.code : 'Add vehicle'"></h3>
                            <p x-text="editing ? 'Changes apply to future trips.' : 'New vehicles start as available.'"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="formOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="vehCode">Vehicle code <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="vehCode" class="lg-input" x-model="form.code" :disabled="!!editing" maxlength="20" autocomplete="off" :aria-invalid="!!errors.code" aria-describedby="vehCodeErr">
                            <p class="lg-error" id="vehCodeErr" x-show="errors.code" x-text="errors.code" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="vehPlate">Plate number <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="vehPlate" class="lg-input" x-ref="plate" x-model="form.plate" maxlength="10" placeholder="ABC 1234" autocomplete="off" style="text-transform:uppercase" :aria-invalid="!!errors.plate" aria-describedby="vehPlateErr">
                            <p class="lg-error" id="vehPlateErr" x-show="errors.plate" x-text="errors.plate" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="vehType">Vehicle type <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="vehType" class="lg-select" x-model="form.type" :aria-invalid="!!errors.type" aria-describedby="vehTypeErr">
                                <option value="">Choose a type</option>
                                <template x-for="t in types" :key="t"><option :value="t" x-text="t"></option></template>
                            </select>
                            <p class="lg-error" id="vehTypeErr" x-show="errors.type" x-text="errors.type" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="vehCap">Capacity in tons <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="vehCap" type="number" inputmode="decimal" min="0.5" max="40" step="0.5" class="lg-input" x-model="form.capacity" placeholder="15" :aria-invalid="!!errors.capacity" aria-describedby="vehCapErr">
                            <p class="lg-error" id="vehCapErr" x-show="errors.capacity" x-text="errors.capacity" role="alert"></p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="formOpen = false">Cancel</button>
                        <button type="submit" class="lg-btn" x-text="editing ? 'Save changes' : 'Add vehicle'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Maintenance needs a short reason; deactivate is a plain confirm. --}}
        <div class="lg-overlay" x-show="confirm.open" x-cloak @click.self="confirm.open = false" @keydown.escape.window="confirm.open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="vehConfirmTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="vehConfirmTitle" x-text="(confirm.kind === 'maintenance' ? 'Set maintenance for ' : 'Deactivate ') + (confirm.v ? confirm.v.code : '')"></h3>
                        <p x-text="confirm.kind === 'maintenance' ? 'It cannot be assigned to new trips until it is back in service.' : 'It is removed from trip assignment until you activate it again.'"></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirm.open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="confirm.kind === 'maintenance'">
                    <div class="lg-field">
                        <label for="vehReason">What is the maintenance for? <span class="lg-req" aria-hidden="true">*</span></label>
                        <textarea id="vehReason" class="lg-textarea" rows="3" maxlength="200" x-model="confirm.reason" placeholder="For example: brake check, tire replacement" :aria-invalid="!!confirm.error" aria-describedby="vehReasonErr"></textarea>
                        <p class="lg-error" id="vehReasonErr" x-show="confirm.error" x-text="confirm.error" role="alert"></p>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirm.open = false">Cancel</button>
                    <button type="button" class="lg-btn" :class="confirm.kind === 'maintenance' ? '' : 'lg-btn--danger-solid'" @click="submitConfirm()" x-text="confirm.kind === 'maintenance' ? 'Set maintenance' : 'Deactivate'"></button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>