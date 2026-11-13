{{--
    Province Hub > Station Hubs. One active Station Hub per municipality (spec 3.3). Fed by PortalController::provinceStations
    (stations, municipalities for this province, and the JSON urls). The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as Company > Province Hubs. Styles: resources/css/logistics/hubs-theme.css
    plus stations-theme.css (loaded last; dialogs sit in .lgh-layer and carry their own copy of the tokens).
    Status colors: Active = mint, Setup = butter (waiting), Inactive = lilac, blocked or failed = rose.
    Search runs as you type; status chips show counts; one click on a row opens the station. Each row has a visible View details button.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no stations at all, and no stations match the filters. Errors: message under each field in the form, top banner for the rest.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/stations-theme.css'])
@endpush

<x-logistics.shell title="Station Hubs" role="province">
    <div class="lgh"
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
            init() {
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.open || this.confirmOpen, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            firstErrors(errors) { return Object.fromEntries(Object.entries(errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])); },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            // One active Station Hub per municipality. A station still in setup also holds its municipality.
            get takenTowns() { return this.stations.filter(s => s.status !== 'Inactive').map(s => s.municipality); },
            get filtered() { return this.q.trim() !== '' || this.tab !== 'all'; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.stations
                    .filter(x => (this.tab === 'all' || x.status.toLowerCase() === this.tab)
                        && (!s || (x.name + ' ' + x.municipality + ' ' + x.manager + ' ' + x.email).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return k === 'all' ? this.stations.length : this.stations.filter(x => x.status.toLowerCase() === k).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            url(s) { return this.showUrl.replace('__station__', s.slug); },
            go(s, event) {
                if (event.target.closest('a, button')) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey) { window.open(this.url(s), '_blank', 'noopener'); return; }
                window.location.assign(this.url(s));
            },
            pill(status) { return status === 'Active' ? 'lgh-pill--mint' : (status === 'Setup' ? 'lgh-pill--butter' : 'lgh-pill--lilac'); },
            openAdd() {
                this.errors = {}; this.nameEdited = false;
                this.form = { municipality: '', name: '', address: '', manager: '', email: '', phone: '' };
                this.open = true;
                this.$nextTick(() => document.getElementById('stTown')?.focus());
            },
            suggestName() { if (!this.nameEdited) this.form.name = this.form.municipality ? this.form.municipality + ' Station' : ''; },
            // The server wants 09XXXXXXXXX. Spaces, dashes and +63 are fine to type, so tidy them before sending.
            phoneValue() { return this.form.phone.replace(/[\s-]/g, '').replace(/^\+63/, '0'); },
            validate() {
                const e = {}, f = this.form, mail = /^\S+@\S+\.\S+$/;
                if (!f.municipality) e.municipality = 'Municipality is not chosen. Pick one from the list.';
                else if (this.takenTowns.includes(f.municipality)) e.municipality = 'This municipality already has an active Station Hub. Pick another, or deactivate that station first.';
                if (!f.name.trim()) e.name = 'Station name is empty. Enter a name, like Santa Cruz Station.';
                else if (this.stations.some(x => x.name.toLowerCase() === f.name.trim().toLowerCase())) e.name = 'Another station has this name. Use a different name.';
                if (f.address.trim().length < 10) e.address = 'Address is too short. Add the unit, street, barangay and municipality so riders can find the station.';
                if (!f.manager.trim()) e.manager = 'Manager name is empty. Enter the manager’s full name.';
                if (!mail.test(f.email.trim())) e.email = 'That doesn’t look like an email address. Use the format name@company.com.';
                else if (this.stations.some(x => x.email.toLowerCase() === f.email.trim().toLowerCase())) e.email = 'This email is already in use. Enter a different email.';
                if (!/^09\d{9}$/.test(this.phoneValue())) e.phone = 'Phone number isn’t a mobile number. Enter 11 digits starting with 09, like 0917 123 4567.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async save(event) {
                if (!this.validate()) { this.$nextTick(() => document.querySelector('[data-station-form] [aria-invalid=true]')?.focus()); return; }
                const button = event.submitter;
                this.busy(button, true);
                try {
                    const response = await fetch(this.createUrl, { method: 'POST', headers: this.headers(), body: JSON.stringify({ ...this.form, phone: this.phoneValue() }) });
                    const payload = await response.json();
                    if (!response.ok) {
                        this.errors = this.firstErrors(payload.errors);
                        throw new Error(payload.errors ? 'Fix the highlighted fields and create the station again.' : (payload.message || 'Could not create the station. Check the form and try again.'));
                    }
                    this.stations.push(payload.station);
                    this.clearFilters(); this.open = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not create the station. Try again.') }); }
                finally { this.busy(button, false); }
            },
            async toggle(s, event) {
                if (s.status === 'Active') { this.target = s; this.confirmOpen = true; return; }
                await this.updateStatus(s, true, event.currentTarget);
            },
            async deactivate(event) {
                if (!this.target || this.target.open > 0) return;
                const changed = await this.updateStatus(this.target, false, event.currentTarget);
                if (changed) this.confirmOpen = false;
            },
            async updateStatus(s, active, button) {
                this.busy(button, true);
                try {
                    const response = await fetch(this.statusUrl.replace('__station__', s.id), { method: 'PATCH', headers: this.headers(), body: JSON.stringify({ is_active: active }) });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not update the station. Try again.');
                    Object.assign(s, payload.station);
                    this.$dispatch('lg-toast', { type: 'success', message: s.name + (active ? ' is active.' : ' is inactive.') });
                    return true;
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not update the station. Try again.') }); return false; }
                finally { this.busy(button, false); }
            }
         }">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Station Hubs</h1>
                <p>One Station Hub per municipality in {{ $hub['province'] }}. Each one receives, sorts and hands parcels to riders.</p>
            </div>
            <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()">
                <x-logistics.icon name="plus" :size="16" /> Create Station Hub
            </button>
        </header>

        {{-- Status chips with counts, then search --}}
        <div class="lgh-chips" role="group" aria-label="Filter stations by status" x-show="stations.length" x-cloak>
            @foreach (['all' => 'All', 'active' => 'Active', 'setup' => 'Setup', 'inactive' => 'Inactive'] as $key => $text)
                <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'"
                        @if ($key === 'setup') x-show="count('setup') > 0 || tab === 'setup'" x-cloak @endif @click="tab = '{{ $key }}'">
                    {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgh-chip lgh-chip--clear" x-show="filtered" x-cloak @click="clearFilters()">
                <x-logistics.icon name="x" :size="14" /> Clear filters
            </button>
        </div>

        <div class="lgh-toolbar" x-show="stations.length" x-cloak>
            <label class="lgh-search">
                <span class="lgh-sr">Search Station Hubs</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search station, municipality or manager">
            </label>
            <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' station' : ' stations')"></span>
        </div>

        <section class="lgh-card lgh-table-card" aria-label="Station Hubs">
            <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                <table class="lgh-table lgh-table--stations">
                    <thead>
                        <tr>
                            <th scope="col">Station</th>
                            <th scope="col">Manager</th>
                            <th scope="col" class="is-num">Riders</th>
                            <th scope="col" class="is-num">Parcels</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="lgh-sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in shown" :key="s.id">
                            <tr class="lgh-row" @click="go(s, $event)">
                                <td>
                                    <a :href="url(s)" class="lgh-name" x-text="s.name"></a>
                                    <small x-text="s.municipality + ' · ' + s.address"></small>
                                </td>
                                <td>
                                    <span x-text="s.manager"></span>
                                    <small x-text="s.email"></small>
                                </td>
                                <td class="is-num" data-label="Riders" x-text="s.riders"></td>
                                <td class="is-num" data-label="Parcels" x-text="s.parcels.toLocaleString()"></td>
                                <td data-label="Status"><span class="lgh-pill" :class="pill(s.status)" x-text="s.status"></span></td>
                                <td class="lgh-actions">
                                    <div class="lgh-actions__row">
                                        <a :href="url(s)" class="lgh-btn lgh-btn--soft lgh-btn--small" :aria-label="'View details for ' + s.name">
                                            View details <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                        </a>
                                        <button type="button" class="lgh-btn lgh-btn--small" :class="s.status === 'Active' ? 'lgh-btn--danger' : 'lgh-btn--soft'" @click="toggle(s, $event)"
                                                :aria-label="(s.status === 'Active' ? 'Deactivate ' : 'Activate ') + s.name" x-text="s.status === 'Active' ? 'Deactivate' : 'Activate'"></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- No stations at all --}}
            <div class="lgh-empty" x-show="!stations.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="building" :size="26" /></span>
                <strong>Create your first Station Hub</strong>
                <p>Add one for each municipality you serve. Riders then register under their station.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="openAdd()"><x-logistics.icon name="plus" :size="16" /> Create Station Hub</button>
            </div>

            {{-- Stations exist, none match --}}
            <div class="lgh-empty" x-show="stations.length && !shown.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                <strong>No stations match your filters</strong>
                <p>Check the spelling, search by municipality or manager, or pick another status.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
            </div>
        </section>

        {{-- Create Station Hub. The municipality list is limited to this Province Hub's province (spec 3.3). --}}
        <div class="lgh-layer" x-show="open" x-cloak @keydown.escape.window="open = false">
            <div class="lgh-backdrop" @click="open = false"></div>
            <div class="lgh-dialog" role="dialog" aria-modal="true" aria-labelledby="stDialogTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="stDialogTitle">Create Station Hub</h2>
                        <p>We email the manager a link to set a password.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form @submit.prevent="save($event)" novalidate data-station-form class="lgh-dialog__body lgh-form">
                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="stTown">Municipality in {{ $hub['province'] }} <span aria-hidden="true">*</span></label>
                            <select id="stTown" x-model="form.municipality" @change="suggestName(); errors.municipality = ''" :aria-invalid="!!errors.municipality" aria-describedby="stTownErr" :class="{ 'has-error': errors.municipality }">
                                <option value="">Choose a municipality</option>
                                <template x-for="m in municipalities" :key="m">
                                    <option :value="m" :disabled="takenTowns.includes(m)" x-text="m + (takenTowns.includes(m) ? ' (has a station)' : '')"></option>
                                </template>
                            </select>
                            <p class="lgh-err" id="stTownErr" x-show="errors.municipality" x-cloak x-text="errors.municipality" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="stName">Station name <span aria-hidden="true">*</span></label>
                            <input id="stName" x-model="form.name" @input="nameEdited = true; errors.name = ''" placeholder="Santa Cruz Station" autocomplete="off" maxlength="80" :aria-invalid="!!errors.name" aria-describedby="stNameErr" :class="{ 'has-error': errors.name }">
                            <p class="lgh-err" id="stNameErr" x-show="errors.name" x-cloak x-text="errors.name" role="alert"></p>
                        </div>
                    </div>

                    <div class="lgh-field">
                        <label for="stAddress">Street address <span aria-hidden="true">*</span></label>
                        <textarea id="stAddress" rows="2" x-model="form.address" @input="errors.address = ''" maxlength="200" placeholder="Unit or building, street, barangay, municipality" :aria-invalid="!!errors.address" aria-describedby="stAddressErr" :class="{ 'has-error': errors.address }"></textarea>
                        <p class="lgh-err" id="stAddressErr" x-show="errors.address" x-cloak x-text="errors.address" role="alert"></p>
                    </div>

                    <div class="lgh-form__row">
                        <div class="lgh-field">
                            <label for="stMgr">Manager name <span aria-hidden="true">*</span></label>
                            <input id="stMgr" x-model="form.manager" @input="errors.manager = ''" placeholder="Full name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.manager" aria-describedby="stMgrErr" :class="{ 'has-error': errors.manager }">
                            <p class="lgh-err" id="stMgrErr" x-show="errors.manager" x-cloak x-text="errors.manager" role="alert"></p>
                        </div>
                        <div class="lgh-field">
                            <label for="stPhone">Manager phone <span aria-hidden="true">*</span></label>
                            <input id="stPhone" type="tel" x-model="form.phone" @input="errors.phone = ''" placeholder="0917 123 4567" autocomplete="off" maxlength="16" :aria-invalid="!!errors.phone" aria-describedby="stPhoneErr" :class="{ 'has-error': errors.phone }">
                            <p class="lgh-err" id="stPhoneErr" x-show="errors.phone" x-cloak x-text="errors.phone" role="alert"></p>
                        </div>
                    </div>

                    <div class="lgh-field">
                        <label for="stEmail">Manager email <span aria-hidden="true">*</span></label>
                        <input id="stEmail" type="email" x-model="form.email" @input="errors.email = ''" placeholder="manager@company.com" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="stEmailErr" :class="{ 'has-error': errors.email }">
                        <p class="lgh-err" id="stEmailErr" x-show="errors.email" x-cloak x-text="errors.email" role="alert"></p>
                    </div>

                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="open = false">Cancel</button>
                        <button type="submit" class="lgh-btn lgh-btn--primary">Create station</button>
                    </footer>
                </form>
            </div>
        </div>

        {{-- Confirm deactivate. Blocked while unfinished parcels still use the station (spec 3.3). --}}
        <div class="lgh-layer" x-show="confirmOpen" x-cloak @keydown.escape.window="confirmOpen = false">
            <div class="lgh-backdrop" @click="confirmOpen = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="stOffTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="stOffTitle" x-text="'Deactivate ' + (target ? target.name : 'station') + '?'"></h2>
                        <p x-text="(target ? target.name : 'This station') + ' stops receiving new parcels, pickups and transfers.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirmOpen = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-callout" x-show="target && target.open > 0" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p>
                            <strong x-text="target ? target.open : 0"></strong>
                            <span x-text="target && target.open === 1 ? 'parcel is' : 'parcels are'"></span> still in progress here. Deactivate the station after they are delivered or moved.
                            <a href="{{ route('lgp.province.parcels') }}">View parcels</a>
                        </p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirmOpen = false">Cancel</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" :disabled="!target || target.open > 0" @click="deactivate($event)">Deactivate station</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
