<x-logistics.shell title="Station Hubs" role="province">
    <div class="lg-page"
         x-data="{
            stations: @js($stations),
            showUrl: @js($showUrl),
            createUrl: @js($createUrl),
            statusUrl: @js($statusUrl),
            municipalities: @js($municipalities),
            q: '',
            tab: 'all',
            open: false,
            nameEdited: false,
            form: { municipality: '', name: '', address: '', manager: '', email: '', phone: '' },
            errors: {},
            confirmOpen: false,
            target: null,
            url(s) { return this.showUrl.replace('__station__', s.slug); },
            // One active Station Hub per municipality. A station still in setup also holds its municipality.
            get takenTowns() { return this.stations.filter(s => s.status !== 'Inactive').map(s => s.municipality); },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.stations
                    .filter(x => (this.tab === 'all' || x.status.toLowerCase() === this.tab)
                        && (!s || (x.name + ' ' + x.municipality + ' ' + x.manager + ' ' + x.email).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return k === 'all' ? this.stations.length : this.stations.filter(x => x.status.toLowerCase() === k).length; },
            openAdd() {
                this.errors = {}; this.nameEdited = false;
                this.form = { municipality: '', name: '', address: '', manager: '', email: '', phone: '' };
                this.open = true;
                this.$nextTick(() => this.$refs.first && this.$refs.first.focus());
            },
            suggestName() { if (!this.nameEdited) this.form.name = this.form.municipality ? this.form.municipality + ' Station' : ''; },
            validate() {
                const e = {}, f = this.form, mail = /^\S+@\S+\.\S+$/;
                const phone = f.phone.replace(/[\s-]/g, '');
                if (!f.municipality) e.municipality = 'Choose a municipality.';
                else if (this.takenTowns.includes(f.municipality)) e.municipality = 'This municipality already has a Station Hub.';
                if (!f.name.trim()) e.name = 'Enter a station name.';
                else if (this.stations.some(x => x.name.toLowerCase() === f.name.trim().toLowerCase())) e.name = 'A station with this name already exists.';
                if (f.address.trim().length < 10) e.address = 'Enter the full street address so riders and drivers can find the station.';
                if (!f.manager.trim()) e.manager = 'Enter the manager name.';
                if (!mail.test(f.email.trim())) e.email = 'Enter a valid email address.';
                else if (this.stations.some(x => x.email.toLowerCase() === f.email.trim().toLowerCase())) e.email = 'This email is already used by another account.';
                if (!/^(09|\+639)\d{9}$/.test(phone)) e.phone = 'Enter a Philippine mobile number, like 0917 123 4567.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save() {
                if (!this.validate()) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                try {
                    const response = await fetch(this.createUrl, { method: 'POST', headers: this.headers(), body: JSON.stringify(this.form) });
                    const payload = await response.json();
                    if (!response.ok) { this.errors = payload.errors || {}; throw new Error(payload.message || 'Could not create Station Hub.'); }
                    this.stations.push(payload.station);
                    this.q = ''; this.tab = 'all'; this.open = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
            },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            async toggle(s) {
                if (s.status === 'Active') { this.target = s; this.confirmOpen = true; return; }
                await this.updateStatus(s, true);
            },
            async deactivate() {
                if (!this.target || this.target.open > 0) return;
                const changed = await this.updateStatus(this.target, false);
                if (changed) this.confirmOpen = false;
            },
            async updateStatus(s, active) {
                try {
                    const response = await fetch(this.statusUrl.replace('__station__', s.id), { method: 'PATCH', headers: this.headers(), body: JSON.stringify({ is_active: active }) });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not update Station Hub.');
                    Object.assign(s, payload.station);
                    this.$dispatch('lg-toast', { type: 'success', message: s.name + (active ? ' activated.' : ' deactivated.') });
                    return true;
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); return false; }
            },
            pill(status) { return status === 'Active' ? 'lg-pill--green' : (status === 'Setup' ? 'lg-pill--amber' : 'lg-pill--gray'); }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Station Hubs</h1>
                <p>One Station Hub per municipality for {{ $hub['province'] }}. Stations receive, sort and hand parcels to Riders.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openAdd()"><x-logistics.icon name="plus" :size="18" /> Create Station Hub</button>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Station status">
            @foreach (['all' => 'All', 'active' => 'Active', 'setup' => 'Setup', 'inactive' => 'Inactive'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search station, municipality or manager" aria-label="Search Station Hubs">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' station' : ' stations')"></span>
        </div>

        <section class="lg-card" aria-label="Station Hubs">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Station</th><th>Municipality</th><th>Manager</th>
                            <th class="is-num">Riders</th><th class="is-num">Parcels</th>
                            <th>Status</th><th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in shown" :key="s.name">
                            <tr>
                                <td data-label="Station"><div class="lg-cell-title" x-text="s.name"></div><div class="lg-cell-sub" x-text="s.address"></div></td>
                                <td data-label="Municipality" x-text="s.municipality"></td>
                                <td data-label="Manager"><div class="lg-cell-title" x-text="s.manager"></div><div class="lg-cell-sub" x-text="s.email"></div></td>
                                <td data-label="Riders" class="is-num" x-text="s.riders"></td>
                                <td data-label="Parcels" class="is-num" x-text="s.parcels.toLocaleString()"></td>
                                <td data-label="Status"><span class="lg-pill" :class="pill(s.status)" x-text="s.status"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <a :href="url(s)" class="lg-btn lg-btn--outline lg-btn--sm" :aria-label="'View ' + s.name"><x-logistics.icon name="eye" :size="16" /> View</a>
                                        <button type="button" class="lg-btn lg-btn--sm" :class="s.status === 'Active' ? 'lg-btn--danger' : 'lg-btn--outline'"
                                                :aria-label="(s.status === 'Active' ? 'Deactivate ' : 'Activate ') + s.name" @click="toggle(s)"
                                                x-text="s.status === 'Active' ? 'Deactivate' : 'Activate'"></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!stations.length" x-cloak icon="building" title="No Station Hubs yet" text="Create a station for each municipality you serve. Riders then register under their station.">
                <button type="button" class="lg-btn lg-btn--sm" @click="openAdd()">Create Station Hub</button>
            </x-logistics.empty>
            <x-logistics.empty x-show="stations.length && !shown.length" x-cloak icon="search" title="No stations match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; tab = 'all'">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Create Station Hub. Municipality list is limited to this Province Hub's province (spec 3.3). --}}
        <template x-teleport="body">
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal lg-modal--hub-form" role="dialog" aria-modal="true" aria-labelledby="stDlgTitle">
                <form @submit.prevent="save()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="stDlgTitle">Create Station Hub</h3>
                            <p>The manager receives a secure password setup link by email.</p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="stTown">Municipality in {{ $hub['province'] }} <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="stTown" class="lg-select" x-ref="first" x-model="form.municipality" @change="suggestName()" :aria-invalid="!!errors.municipality" aria-describedby="stTownErr">
                                <option value="">Choose a municipality</option>
                                <template x-for="m in municipalities" :key="m">
                                    <option :value="m" :disabled="takenTowns.includes(m)" x-text="m + (takenTowns.includes(m) ? ' (already has a station)' : '')"></option>
                                </template>
                            </select>
                            <p class="lg-error" id="stTownErr" x-show="errors.municipality" x-text="errors.municipality" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="stName">Station name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="stName" class="lg-input" x-model="form.name" @input="nameEdited = true" placeholder="Santa Cruz Station" autocomplete="off" maxlength="80" :aria-invalid="!!errors.name" aria-describedby="stNameErr">
                            <p class="lg-error" id="stNameErr" x-show="errors.name" x-text="errors.name" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="stAddress">Street address <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="stAddress" class="lg-textarea" rows="2" x-model="form.address" maxlength="200" placeholder="Unit or building, street, barangay, municipality" :aria-invalid="!!errors.address" aria-describedby="stAddressErr"></textarea>
                            <p class="lg-error" id="stAddressErr" x-show="errors.address" x-text="errors.address" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="stMgr">Manager name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="stMgr" class="lg-input" x-model="form.manager" placeholder="Full name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.manager" aria-describedby="stMgrErr">
                            <p class="lg-error" id="stMgrErr" x-show="errors.manager" x-text="errors.manager" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="stEmail">Manager email <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="stEmail" type="email" class="lg-input" x-model="form.email" placeholder="manager@company.com" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="stEmailErr">
                            <p class="lg-error" id="stEmailErr" x-show="errors.email" x-text="errors.email" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="stPhone">Manager phone <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="stPhone" type="tel" class="lg-input" x-model="form.phone" placeholder="0917 123 4567" autocomplete="off" maxlength="16" :aria-invalid="!!errors.phone" aria-describedby="stPhoneErr">
                            <p class="lg-error" id="stPhoneErr" x-show="errors.phone" x-text="errors.phone" role="alert"></p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Create station</button>
                    </div>
                </form>
            </div>
        </div>
        </template>

        {{-- Confirm deactivate. Blocked while unfinished parcels still use the station (spec 3.3). --}}
        <div class="lg-overlay" x-show="confirmOpen" x-cloak @click.self="confirmOpen = false" @keydown.escape.window="confirmOpen = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="stOffTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="stOffTitle" x-text="'Deactivate ' + (target ? target.name : 'station')"></h3>
                        <p x-text="(target ? target.name : 'This station') + ' will stop receiving new parcels, pickups and transfers.'"></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirmOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="target && target.open > 0">
                    <div class="lg-note lg-note--warn">
                        <x-logistics.icon name="alert" :size="18" />
                        <span><strong x-text="target ? target.open : 0"></strong> parcels are still in progress at this station. You can deactivate it after they are delivered or moved.</span>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirmOpen = false">Cancel</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" :disabled="!target || target.open > 0" @click="deactivate()">Deactivate</button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
