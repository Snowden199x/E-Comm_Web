{{--
    Company > Province Hubs (spec section 8). One Province Hub per province for this company.
    Fed by PortalController::companyHubs (hubs, provinces and the JSON urls). The page state lives in one Alpine scope.
    Look: the seller pastel lilac system. Styles: resources/css/logistics/hubs-theme.css (pushed last, tokens copied from
    seller/delivered-orders.css, dialogs carry their own copy). No page script: all behavior is in the Alpine scope below.
    Status colors: Active = mint, Inactive = lilac, email waiting = butter. Failed or blocked = rose.
    Search runs as you type; status chips show counts. One click on a row opens the hub.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no hubs at all, and no hubs match the filters. Errors: field messages in the form, top banner for the rest.
--}}
@push('lg-head')
    @vite('resources/css/logistics/hubs-theme.css')
@endpush

<x-logistics.shell title="Province Hubs" role="company">
    <div class="lgh"
         x-data="{
            hubs: @js($hubs),
            provinces: @js($provinces),
            showUrl: @js(route('lgp.company.hubs.show', '__hub__')),
            refreshUrl: @js($refreshUrl ?? null),
            createUrl: @js($createUrl ?? null),
            updateUrl: @js($updateUrl ?? null),
            deleteUrl: @js($deleteUrl ?? null),
            statusUrl: @js($statusUrl ?? null),
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
            refreshFailed: false,
            init() {
                if (this.refreshUrl) this.pollTimer = window.setInterval(() => this.refreshHubs(), 5000);
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.open || this.confirmOpen || this.confirmDeleteOpen, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            destroy() { if (this.pollTimer) window.clearInterval(this.pollTimer); },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            firstErrors(errors) { return Object.fromEntries(Object.entries(errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])); },
            async refreshHubs() {
                if (document.hidden) return;
                try {
                    const response = await fetch(this.refreshUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    if (!response.ok) throw new Error('Refresh failed');
                    const payload = await response.json();
                    payload.managers.forEach(updated => {
                        const current = this.hubs.find(h => h.id === updated.id);
                        if (current) Object.assign(current, updated);
                    });
                    this.refreshFailed = false;
                } catch (_) {
                    if (!this.refreshFailed) {
                        window.logisticsBanner?.({ type: 'error', title: 'Manager emails are not updating', message: 'Check your internet connection. Reload the page if this keeps happening.' });
                    }
                    this.refreshFailed = true;
                }
            },
            get activeProvinces() { return this.hubs.filter(h => h.status === 'Active').map(h => h.province); },
            get filtered() { return this.q.trim() !== '' || this.tab !== 'all'; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.hubs
                    .filter(h => (this.tab === 'all' || h.status.toLowerCase() === this.tab)
                        && (!s || (h.name + ' ' + h.province + ' ' + h.manager + ' ' + h.email).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return k === 'all' ? this.hubs.length : this.hubs.filter(h => h.status.toLowerCase() === k).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            url(h) { return this.showUrl.replace('__hub__', h.slug); },
            go(h, event) {
                if (event.target.closest('a, button')) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey) { window.open(this.url(h), '_blank', 'noopener'); return; }
                window.location.assign(this.url(h));
            },
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
            askDelete() { this.target = this.editingHub; this.open = false; this.confirmDeleteOpen = true; },
            suggestName() { if (!this.nameEdited) this.form.name = this.form.province ? this.form.province + ' Hub' : ''; },
            validate() {
                const e = {}, f = this.form, mail = /^\S+@\S+\.\S+$/;
                const phone = f.phone.replace(/[\s-]/g, '');
                if (!this.editingHub && !f.province) e.province = 'Choose a province for this hub.';
                else if (!this.editingHub && this.activeProvinces.includes(f.province)) e.province = 'This province already has an active hub. Choose another province, or deactivate that hub first.';
                if (!f.name.trim()) e.name = 'Enter a hub name.';
                else if (this.hubs.some(h => h.id !== this.editingHub?.id && h.name.toLowerCase() === f.name.trim().toLowerCase())) e.name = 'Another hub has this name. Use a different name.';
                if (f.address.trim().length < 10) e.address = 'Add the unit, street, barangay and city so drivers can find the hub.';
                if (!f.manager.trim()) e.manager = 'Enter the manager’s full name.';
                if (!mail.test(f.email.trim())) e.email = 'Enter an email like name@company.com.';
                else if (this.hubs.some(h => h.id !== this.editingHub?.id && [h.email, h.pending_email].filter(Boolean).some(email => email.toLowerCase() === f.email.trim().toLowerCase()))) e.email = 'This email is already in use. Enter a different email.';
                if (!/^(09|\+639)\d{9}$/.test(phone)) e.phone = 'Enter a mobile number like 0917 123 4567.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save(event) {
                if (!this.validate()) { this.$nextTick(() => document.querySelector('[data-hub-form] [aria-invalid=true]')?.focus()); return; }
                const button = event.submitter, f = this.form, editing = this.editingHub;
                this.busy(button, true);
                try {
                    const response = await fetch(editing ? this.updateUrl.replace('__hub__', editing.id) : this.createUrl, {
                        method: editing ? 'PATCH' : 'POST', headers: this.headers(),
                        body: JSON.stringify(editing ? { name: f.name, address: f.address, manager: f.manager, email: f.email, phone: f.phone } : f),
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        this.errors = this.firstErrors(payload.errors);
                        throw new Error(payload.errors ? 'Fix the highlighted fields and save again.' : (payload.message || 'Could not save the hub. Check the form and try again.'));
                    }
                    if (editing) Object.assign(editing, payload.hub);
                    else this.hubs.push(payload.hub);
                    this.clearFilters(); this.open = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not save the hub. Try again.') }); }
                finally { this.busy(button, false); }
            },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            async toggle(h, event) {
                if (h.status === 'Active') { this.target = h; this.confirmOpen = true; return; }
                await this.updateStatus(h, true, event.currentTarget);
            },
            async deactivate(event) {
                if (!this.target || this.target.open > 0) return;
                const changed = await this.updateStatus(this.target, false, event.currentTarget);
                if (changed) this.confirmOpen = false;
            },
            async updateStatus(h, active, button) {
                this.busy(button, true);
                try {
                    const response = await fetch(this.statusUrl.replace('__hub__', h.id), { method: 'PATCH', headers: this.headers(), body: JSON.stringify({ is_active: active }) });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not update the hub. Try again.');
                    Object.assign(h, payload.hub);
                    this.$dispatch('lg-toast', { type: 'success', message: h.name + (active ? ' is active.' : ' is inactive.') });
                    return true;
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not update the hub. Try again.') }); return false; }
                finally { this.busy(button, false); }
            },
            async deleteHub(event) {
                if (!this.target) return;
                const button = event.currentTarget;
                this.busy(button, true);
                try {
                    const response = await fetch(this.deleteUrl.replace('__hub__', this.target.id), { method: 'DELETE', headers: this.headers() });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not delete the hub. Try again.');
                    this.hubs = this.hubs.filter(h => h.id !== payload.id);
                    this.confirmDeleteOpen = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not delete the hub. Try again.') }); }
                finally { this.busy(button, false); }
            }
         }">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Province Hubs</h1>
                <p>One hub per province. Each hub manages its own Station Hubs, drivers and vehicles.</p>
            </div>
            <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()">
                <x-logistics.icon name="plus" :size="16" /> Create Province Hub
            </button>
        </header>

        {{-- Status chips with counts, then search --}}
        <div class="lgh-chips" role="group" aria-label="Filter hubs by status" x-show="hubs.length" x-cloak>
            @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $text)
                <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                    {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgh-chip lgh-chip--clear" x-show="filtered" x-cloak @click="clearFilters()">
                <x-logistics.icon name="x" :size="14" /> Clear filters
            </button>
        </div>

        <div class="lgh-toolbar" x-show="hubs.length" x-cloak>
            <label class="lgh-search">
                <span class="lgh-sr">Search Province Hubs</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search hub, province or manager">
            </label>
            <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' hub' : ' hubs')"></span>
        </div>

        <section class="lgh-card lgh-table-card" aria-label="Province Hubs">
            <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                <table class="lgh-table">
                    <thead>
                        <tr>
                            <th scope="col">Hub</th>
                            <th scope="col">Manager</th>
                            <th scope="col" class="is-num">Stations</th>
                            <th scope="col" class="is-num">Drivers</th>
                            <th scope="col" class="is-num">Vehicles</th>
                            <th scope="col" class="is-num">Parcels</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgh-sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="h in shown" :key="h.slug">
                            <tr class="lgh-row" @click="go(h, $event)">
                                <td>
                                    <a :href="url(h)" class="lgh-name" x-text="h.name"></a>
                                    <small x-text="h.province + ' · ' + h.address"></small>
                                </td>
                                <td>
                                    <span x-text="h.manager"></span>
                                    <small x-text="h.pending_email || h.email"></small>
                                    <small class="lgh-pending" x-show="h.pending_email" x-cloak>New email not confirmed</small>
                                </td>
                                <td class="is-num" data-label="Stations" x-text="h.stations.length"></td>
                                <td class="is-num" data-label="Drivers" x-text="h.drivers"></td>
                                <td class="is-num" data-label="Vehicles" x-text="h.vehicles"></td>
                                <td class="is-num" data-label="Parcels" x-text="h.parcels.toLocaleString()"></td>
                                <td data-label="Status"><span class="lgh-pill" :class="h.status === 'Active' ? 'lgh-pill--mint' : 'lgh-pill--lilac'" x-text="h.status"></span></td>
                                <td class="lgh-actions">
                                    <div class="lgh-actions__row">
                                        <a :href="url(h)" class="lgh-btn lgh-btn--soft lgh-btn--small" :aria-label="'View details for ' + h.name">
                                            View details <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                        </a>
                                        <button type="button" class="lgh-btn lgh-btn--ghost lgh-btn--small" @click="openEdit(h)" :aria-label="'Edit ' + h.name">
                                            <x-logistics.icon name="edit" :size="14" /> Edit
                                        </button>
                                        <button type="button" class="lgh-btn lgh-btn--small" :class="h.status === 'Active' ? 'lgh-btn--danger' : 'lgh-btn--soft'" @click="toggle(h, $event)"
                                            :aria-label="(h.status === 'Active' ? 'Deactivate ' : 'Activate ') + h.name" x-text="h.status === 'Active' ? 'Deactivate' : 'Activate'"></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- No hubs at all --}}
            <div class="lgh-empty" x-show="!hubs.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="map-pin" :size="26" /></span>
                <strong>Create your first Province Hub</strong>
                <p>Add one hub per province you deliver to. Each hub then adds its own Station Hubs.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()"><x-logistics.icon name="plus" :size="16" /> Create Province Hub</button>
            </div>

            {{-- Hubs exist, none match --}}
            <div class="lgh-empty" x-show="hubs.length && !shown.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                <strong>No hubs match your filters</strong>
                <p>Check the spelling, search by province or manager, or pick another status.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
            </div>
        </section>

        {{-- Create or edit Province Hub --}}
        <div class="lgh-layer" x-show="open" x-cloak @keydown.escape.window="open = false">
            <div class="lgh-backdrop" @click="open = false"></div>
            <div class="lgh-dialog" role="dialog" aria-modal="true" aria-labelledby="hubDialogTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="hubDialogTitle" x-text="editingHub ? 'Edit Province Hub' : 'Create Province Hub'"></h2>
                        <p x-text="editingHub ? 'A new manager email takes effect after the manager confirms it.' : 'We email the manager a link to set a password.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form @submit.prevent="save($event)" novalidate data-hub-form class="lgh-dialog__body lgh-form">
                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="hubProvince">Province <span aria-hidden="true">*</span></label>
                            <select id="hubProvince" x-model="form.province" @change="suggestName()" :disabled="!!editingHub" :aria-invalid="!!errors.province" aria-describedby="hubProvinceErr" :class="{ 'has-error': errors.province }">
                                <option value="">Choose a province</option>
                                <template x-for="p in provinces" :key="p">
                                    <option :value="p" :disabled="activeProvinces.includes(p)" x-text="p + (activeProvinces.includes(p) ? ' (has a hub)' : '')"></option>
                                </template>
                            </select>
                            <p class="lgh-err" id="hubProvinceErr" x-show="errors.province" x-cloak x-text="errors.province" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="hubName">Hub name <span aria-hidden="true">*</span></label>
                            <input id="hubName" x-model="form.name" @input="nameEdited = true" placeholder="Laguna Hub" autocomplete="off" maxlength="80" :aria-invalid="!!errors.name" aria-describedby="hubNameErr" :class="{ 'has-error': errors.name }">
                            <p class="lgh-err" id="hubNameErr" x-show="errors.name" x-cloak x-text="errors.name" role="alert"></p>
                        </div>
                    </div>

                    <div class="lgh-field">
                        <label for="hubAddress">Street address <span aria-hidden="true">*</span></label>
                        <textarea id="hubAddress" rows="2" x-model="form.address" maxlength="200" placeholder="Unit or building, street, barangay, city" :aria-invalid="!!errors.address" aria-describedby="hubAddressErr" :class="{ 'has-error': errors.address }"></textarea>
                        <p class="lgh-err" id="hubAddressErr" x-show="errors.address" x-cloak x-text="errors.address" role="alert"></p>
                    </div>

                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="hubMgr">Manager name <span aria-hidden="true">*</span></label>
                            <input id="hubMgr" x-model="form.manager" placeholder="Full name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.manager" aria-describedby="hubMgrErr" :class="{ 'has-error': errors.manager }">
                            <p class="lgh-err" id="hubMgrErr" x-show="errors.manager" x-cloak x-text="errors.manager" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="hubPhone">Manager phone <span aria-hidden="true">*</span></label>
                            <input id="hubPhone" type="tel" x-model="form.phone" placeholder="0917 123 4567" autocomplete="off" maxlength="16" :aria-invalid="!!errors.phone" aria-describedby="hubPhoneErr" :class="{ 'has-error': errors.phone }">
                            <p class="lgh-err" id="hubPhoneErr" x-show="errors.phone" x-cloak x-text="errors.phone" role="alert"></p>
                        </div>
                    </div>

                    <div class="lgh-field">
                        <label for="hubEmail">Manager email <span aria-hidden="true">*</span></label>
                        <input id="hubEmail" type="email" x-model="form.email" placeholder="manager@company.com" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="hubEmailErr" :class="{ 'has-error': errors.email }">
                        <p class="lgh-err" id="hubEmailErr" x-show="errors.email" x-cloak x-text="errors.email" role="alert"></p>
                    </div>

                    <footer class="lgh-dialog__foot">
                        <button type="button" class="lgh-btn lgh-btn--danger" x-show="editingHub" x-cloak @click="askDelete()">Delete hub</button>
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="open = false">Cancel</button>
                        <button type="submit" class="lgh-btn lgh-btn--primary" x-text="editingHub ? 'Save changes' : 'Create hub'"></button>
                    </footer>
                </form>
            </div>
        </div>

        {{-- Confirm deactivate. Blocked while unfinished parcels still use the hub (spec 3.3). --}}
        <div class="lgh-layer" x-show="confirmOpen" x-cloak @keydown.escape.window="confirmOpen = false">
            <div class="lgh-backdrop" @click="confirmOpen = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="hubDeactivateTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="hubDeactivateTitle" x-text="'Deactivate ' + (target ? target.name : 'hub') + '?'"></h2>
                        <p x-text="(target ? target.name : 'This hub') + ' stops receiving new parcels and trips.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirmOpen = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-callout" x-show="target && target.open > 0" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p>
                            <strong x-text="target ? target.open : 0"></strong>
                            <span x-text="target && target.open === 1 ? 'parcel is' : 'parcels are'"></span> still in progress here. Deactivate the hub after they are delivered or moved.
                            <a href="{{ route('lgp.company.tracking') }}">Open Parcel Tracking</a>
                        </p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirmOpen = false">Cancel</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" :disabled="!target || target.open > 0" @click="deactivate($event)">Deactivate hub</button>
                    </footer>
                </div>
            </div>
        </div>

        {{-- Delete only empty hubs with no operational history. --}}
        <div class="lgh-layer" x-show="confirmDeleteOpen" x-cloak @keydown.escape.window="confirmDeleteOpen = false">
            <div class="lgh-backdrop" @click="confirmDeleteOpen = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="hubDeleteTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="hubDeleteTitle" x-text="'Delete ' + (target ? target.name : 'Province Hub') + '?'"></h2>
                        <p>Only empty hubs can be deleted. A hub with Station Hubs, drivers, vehicles, or parcel, trip, scan, settlement or message history is protected. Its manager account is deactivated.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirmDeleteOpen = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirmDeleteOpen = false">Cancel</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" @click="deleteHub($event)">Delete Province Hub</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
