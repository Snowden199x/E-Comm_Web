<x-logistics.shell title="Province Hubs" role="company">
    <div class="lg-page"
         x-data="{
            hubs: @js($hubs),
            provinces: @js($provinces),
            showUrl: @js(route('lgp.company.hubs.show', '__hub__')),
            refreshUrl: @js($refreshUrl),
            createUrl: @js($createUrl),
            updateUrl: @js($updateUrl),
            deleteUrl: @js($deleteUrl),
            statusUrl: @js($statusUrl),
            q: '',
            tab: 'all',
            open: false,
            editingHub: null,
            nameEdited: false,
            form: { province: '', name: '', address: '', manager: '', email: '', phone: '' },
            errors: {},
            confirmOpen: false,
            confirmDeleteOpen: false,
            target: null,
            pollTimer: null,
            init() { this.pollTimer = window.setInterval(() => this.refreshHubs(), 5000); },
            destroy() { if (this.pollTimer) window.clearInterval(this.pollTimer); },
            async refreshHubs() {
                if (document.hidden) return;
                try {
                    const response = await fetch(this.refreshUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    if (!response.ok) return;
                    const payload = await response.json();
                    payload.managers.forEach(updated => {
                        const current = this.hubs.find(h => h.id === updated.id);
                        if (current) Object.assign(current, updated);
                    });
                } catch (_) {}
            },
            get activeProvinces() { return this.hubs.filter(h => h.status === 'Active').map(h => h.province); },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.hubs
                    .filter(h => (this.tab === 'all' || h.status.toLowerCase() === this.tab)
                        && (!s || (h.name + ' ' + h.province + ' ' + h.manager + ' ' + h.email).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return k === 'all' ? this.hubs.length : this.hubs.filter(h => h.status.toLowerCase() === k).length; },
            url(h) { return this.showUrl.replace('__hub__', h.slug); },
            openAdd() {
                this.errors = {}; this.nameEdited = false; this.editingHub = null;
                this.form = { province: '', name: '', address: '', manager: '', email: '', phone: '' };
                this.open = true;
                this.$nextTick(() => document.getElementById('hubProvince')?.focus());
            },
            openEdit(h) {
                this.errors = {}; this.editingHub = h;
                this.form = { province: h.province, name: h.name, address: h.address, manager: h.manager, email: h.pending_email || h.email, phone: h.phone };
                this.open = true;
                this.$nextTick(() => document.getElementById('hubName')?.focus());
            },
            suggestName() { if (!this.nameEdited) this.form.name = this.form.province ? this.form.province + ' Hub' : ''; },
            validate() {
                const e = {}, f = this.form, mail = /^\S+@\S+\.\S+$/;
                const phone = f.phone.replace(/[\s-]/g, '');
                if (!this.editingHub && !f.province) e.province = 'Choose a province.';
                else if (!this.editingHub && this.activeProvinces.includes(f.province)) e.province = 'This company already has an active Province Hub.';
                if (!f.name.trim()) e.name = 'Enter a hub name.';
                else if (this.hubs.some(h => h.id !== this.editingHub?.id && h.name.toLowerCase() === f.name.trim().toLowerCase())) e.name = 'A hub with this name already exists.';
                if (f.address.trim().length < 10) e.address = 'Enter the full street address so drivers can find the hub.';
                if (!f.manager.trim()) e.manager = 'Enter the manager name.';
                if (!this.editingHub && !mail.test(f.email.trim())) e.email = 'Enter a valid email address.';
                else if (this.hubs.some(h => h.id !== this.editingHub?.id && [h.email, h.pending_email].filter(Boolean).some(email => email.toLowerCase() === f.email.trim().toLowerCase()))) e.email = 'This email is already used by another account.';
                if (!/^(09|\+639)\d{9}$/.test(phone)) e.phone = 'Enter a Philippine mobile number, like 0917 123 4567.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save() {
                if (!this.validate()) { this.$nextTick(() => document.querySelector('.lg-modal--hub-form [aria-invalid=true]')?.focus()); return; }
                const f = this.form;
                try {
                    const editing = this.editingHub;
                    const response = await fetch(editing ? this.updateUrl.replace('__hub__', editing.id) : this.createUrl, {
                        method: editing ? 'PATCH' : 'POST', headers: this.headers(),
                        body: JSON.stringify(editing ? { name: f.name, address: f.address, manager: f.manager, email: f.email, phone: f.phone } : f),
                    });
                    const payload = await response.json();
                    if (!response.ok) { this.errors = payload.errors || {}; throw new Error(payload.message || (editing ? 'Could not update Province Hub.' : 'Could not create Province Hub.')); }
                    if (editing) Object.assign(editing, payload.hub);
                    else this.hubs.push(payload.hub);
                    this.q = ''; this.tab = 'all'; this.open = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
            },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            async toggle(h) {
                if (h.status === 'Active') { this.target = h; this.confirmOpen = true; return; }
                await this.updateStatus(h, true);
            },
            async deactivate() {
                if (!this.target || this.target.open > 0) return;
                const changed = await this.updateStatus(this.target, false);
                if (changed) this.confirmOpen = false;
            },
            async updateStatus(h, active) {
                try {
                    const response = await fetch(this.statusUrl.replace('__hub__', h.id), { method: 'PATCH', headers: this.headers(), body: JSON.stringify({ is_active: active }) });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not update Province Hub.');
                    Object.assign(h, payload.hub);
                    this.$dispatch('lg-toast', { type: 'success', message: h.name + (active ? ' activated.' : ' deactivated.') });
                    return true;
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); return false; }
            },
            async deleteHub() {
                if (!this.target) return;
                try {
                    const response = await fetch(this.deleteUrl.replace('__hub__', this.target.id), { method: 'DELETE', headers: this.headers() });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not delete Province Hub.');
                    this.hubs = this.hubs.filter(h => h.id !== payload.id);
                    this.confirmDeleteOpen = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Province Hubs</h1>
                <p>One Province Hub per province for this company. Each hub runs its own stations, transport drivers and vehicles.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openAdd()"><x-logistics.icon name="plus" :size="18" /> Create Province Hub</button>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Hub status">
            @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search hub, province or manager" aria-label="Search Province Hubs">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' hub' : ' hubs')"></span>
        </div>

        <section class="lg-card" aria-label="Province Hubs">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Hub</th><th>Province</th><th>Manager</th>
                            <th class="is-num">Stations</th><th class="is-num">Drivers</th><th class="is-num">Vehicles</th><th class="is-num">Parcels</th>
                            <th>Status</th><th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="h in shown" :key="h.slug">
                            <tr>
                                <td data-label="Hub"><div class="lg-cell-title" x-text="h.name"></div><div class="lg-cell-sub" x-text="h.address"></div></td>
                                <td data-label="Province" x-text="h.province"></td>
                                <td data-label="Manager"><div class="lg-cell-title" x-text="h.manager"></div><div class="lg-cell-sub" x-text="h.pending_email ? h.pending_email + ' · awaiting confirmation' : h.email"></div></td>
                                <td data-label="Stations" class="is-num" x-text="h.stations.length"></td>
                                <td data-label="Drivers" class="is-num" x-text="h.drivers"></td>
                                <td data-label="Vehicles" class="is-num" x-text="h.vehicles"></td>
                                <td data-label="Parcels" class="is-num" x-text="h.parcels.toLocaleString()"></td>
                                <td data-label="Status"><span class="lg-pill" :class="h.status === 'Active' ? 'lg-pill--green' : 'lg-pill--gray'" x-text="h.status"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                    <a :href="url(h)" class="lg-btn lg-btn--outline lg-btn--sm" :aria-label="'View ' + h.name"><x-logistics.icon name="eye" :size="16" /> View</a>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openEdit(h)" :aria-label="'Edit ' + h.name">Edit</button>
                                        <button type="button" class="lg-btn lg-btn--sm" :class="h.status === 'Active' ? 'lg-btn--danger' : 'lg-btn--outline'"
                                                :aria-label="(h.status === 'Active' ? 'Deactivate ' : 'Activate ') + h.name" @click="toggle(h)"
                                                x-text="h.status === 'Active' ? 'Deactivate' : 'Activate'"></button>
                                        <button type="button" class="lg-btn lg-btn--danger lg-btn--sm" @click="target = h; confirmDeleteOpen = true" :aria-label="'Delete ' + h.name">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!hubs.length" x-cloak icon="map-pin" title="No Province Hubs yet" text="Create a hub for each province you deliver to. Each hub then adds its own stations.">
                <button type="button" class="lg-btn lg-btn--sm" @click="openAdd()">Create Province Hub</button>
            </x-logistics.empty>
            <x-logistics.empty x-show="hubs.length && !shown.length" x-cloak icon="search" title="No hubs match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; tab = 'all'">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Create or edit Province Hub --}}
        <template x-teleport="body">
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal lg-modal--hub-form" role="dialog" aria-modal="true" aria-labelledby="hubDlgTitle">
                <form @submit.prevent="save()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="hubDlgTitle" x-text="editingHub ? 'Edit Province Hub' : 'Create Province Hub'"></h3>
                            <p x-text="editingHub ? 'Update the hub and manager details. Confirming a new manager email opens password setup.' : 'The manager receives a secure password setup link by email.'"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="hubProvince">Province <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="hubProvince" class="lg-select" x-ref="first" x-model="form.province" @change="suggestName()" :disabled="!!editingHub" :aria-invalid="!!errors.province" aria-describedby="hubProvinceErr">
                                <option value="">Choose a province</option>
                                <template x-for="p in provinces" :key="p">
                                    <option :value="p" :disabled="activeProvinces.includes(p)" x-text="p + (activeProvinces.includes(p) ? ' (already has a hub)' : '')"></option>
                                </template>
                            </select>
                            <p class="lg-error" id="hubProvinceErr" x-show="errors.province" x-text="errors.province" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="hubName">Hub name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubName" class="lg-input" x-model="form.name" @input="nameEdited = true" placeholder="Laguna Hub" autocomplete="off" maxlength="80" :aria-invalid="!!errors.name" aria-describedby="hubNameErr">
                            <p class="lg-error" id="hubNameErr" x-show="errors.name" x-text="errors.name" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="hubAddress">Street address <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="hubAddress" class="lg-textarea" rows="2" x-model="form.address" maxlength="200" placeholder="Unit or building, street, barangay, city" :aria-invalid="!!errors.address" aria-describedby="hubAddressErr"></textarea>
                            <p class="lg-error" id="hubAddressErr" x-show="errors.address" x-text="errors.address" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="hubMgr">Manager name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubMgr" class="lg-input" x-model="form.manager" placeholder="Full name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.manager" aria-describedby="hubMgrErr">
                            <p class="lg-error" id="hubMgrErr" x-show="errors.manager" x-text="errors.manager" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="hubEmail">Manager email <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubEmail" type="email" class="lg-input" x-model="form.email" placeholder="manager@company.com" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="hubEmailErr">
                            <p class="lg-error" id="hubEmailErr" x-show="errors.email" x-text="errors.email" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="hubPhone">Manager phone <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubPhone" type="tel" class="lg-input" x-model="form.phone" placeholder="0917 123 4567" autocomplete="off" maxlength="16" :aria-invalid="!!errors.phone" aria-describedby="hubPhoneErr">
                            <p class="lg-error" id="hubPhoneErr" x-show="errors.phone" x-text="errors.phone" role="alert"></p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn" x-text="editingHub ? 'Save changes' : 'Create hub'"></button>
                    </div>
                </form>
            </div>
        </div>
        </template>

        {{-- Delete only empty hubs with no operational history. --}}
        <template x-teleport="body">
        <div class="lg-overlay" x-show="confirmDeleteOpen" x-cloak @click.self="confirmDeleteOpen = false" @keydown.escape.window="confirmDeleteOpen = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="hubDeleteTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="hubDeleteTitle" x-text="'Delete ' + (target ? target.name : 'Province Hub') + '?' "></h3>
                        <p>This permanently removes the empty Province Hub. Hubs with Station Hubs or parcel, trip, driver, vehicle, scan, settlement, or message history are protected. The linked manager account will be deactivated.</p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirmDeleteOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirmDeleteOpen = false">Cancel</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" @click="deleteHub()">Delete Province Hub</button>
                </div>
            </div>
        </div>
        </template>

        {{-- Confirm deactivate. Blocked while unfinished parcels still use the hub (spec 3.3). --}}
        <template x-teleport="body">
        <div class="lg-overlay" x-show="confirmOpen" x-cloak @click.self="confirmOpen = false" @keydown.escape.window="confirmOpen = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="hubOffTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="hubOffTitle" x-text="'Deactivate ' + (target ? target.name : 'hub')"></h3>
                        <p x-text="(target ? target.name : 'This hub') + ' will stop receiving new parcels and trips.'"></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirmOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="target && target.open > 0">
                    <div class="lg-note lg-note--warn">
                        <x-logistics.icon name="alert" :size="18" />
                        <span><strong x-text="target ? target.open : 0"></strong> parcels are still in progress at this hub. You can deactivate it after they are delivered or moved.</span>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirmOpen = false">Cancel</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" :disabled="!target || target.open > 0" @click="deactivate()">Deactivate</button>
                </div>
            </div>
        </div>
        </template>
    </div>
</x-logistics.shell>
