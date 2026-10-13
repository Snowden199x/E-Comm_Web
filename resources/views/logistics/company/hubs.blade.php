<x-logistics.shell title="Hubs" role="company">
    <div class="lg-page"
         x-data="{
            provinces: @js($provinces),
            open: false,
            mode: 'add',
            editing: null,
            form: { type: 'province', parent: '', province: '', municipality: '', manager: '', email: '' },
            errors: {},
            riders(p) { return p.stations.reduce((n, s) => n + s.riders, 0); },
            openAdd(type, parent) {
                this.mode = 'add'; this.editing = null; this.errors = {};
                this.form = { type: type || 'province', parent: parent || (this.provinces[0] ? this.provinces[0].name : ''), province: '', municipality: '', manager: '', email: '' };
                this.open = true;
                this.$nextTick(() => this.$refs.dlg && this.$refs.dlg.focus());
            },
            openEdit(p, s) {
                this.mode = 'edit'; this.editing = { p: p, s: s }; this.errors = {};
                this.form = { type: s ? 'station' : 'province', parent: p.name, province: '', municipality: s ? s.municipality : '', manager: s ? s.manager : p.manager, email: '' };
                this.open = true;
                this.$nextTick(() => this.$refs.dlg && this.$refs.dlg.focus());
            },
            validate() {
                const e = {}, f = this.form, add = this.mode === 'add', mail = /^\S+@\S+\.\S+$/;
                if (add && f.type === 'province') {
                    if (!f.province.trim()) e.province = 'Enter the province.';
                    else if (this.provinces.some(p => p.name.toLowerCase() === f.province.trim().toLowerCase() + ' hub')) e.province = 'You already have a hub for this province.';
                }
                if (f.type === 'station') {
                    if (!f.municipality.trim()) e.municipality = 'Enter the municipality.';
                    else {
                        const parent = this.provinces.find(p => p.name === f.parent);
                        const clash = parent && parent.stations.some(s => s.municipality.toLowerCase() === f.municipality.trim().toLowerCase() && (!this.editing || s !== this.editing.s));
                        if (clash) e.municipality = 'This province hub already has a station in that municipality.';
                    }
                    if (add && !f.parent) e.parent = 'Choose the province hub it reports to.';
                }
                if (!f.manager.trim()) e.manager = 'Enter the manager name.';
                if (add ? !mail.test(f.email.trim()) : (f.email.trim() && !mail.test(f.email.trim()))) e.email = 'Enter a valid email address.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            save() {
                if (!this.validate()) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                const f = this.form;
                let name = '';
                if (this.mode === 'add') {
                    if (f.type === 'province') {
                        name = f.province.trim() + ' Hub';
                        this.provinces.push({ name: name, place: f.province.trim(), manager: f.manager.trim(), stations: [] });
                    } else {
                        const parent = this.provinces.find(p => p.name === f.parent);
                        name = f.municipality.trim() + ' Station';
                        parent.stations.push({ name: name, municipality: f.municipality.trim(), manager: f.manager.trim(), riders: 0, status: 'Setup' });
                    }
                    this.$dispatch('lg-toast', { type: 'success', message: name + ' created. An invite with a temporary password goes to ' + f.email.trim() + '.' });
                } else {
                    const t = this.editing;
                    if (t.s) { t.s.municipality = f.municipality.trim(); t.s.manager = f.manager.trim(); name = t.s.name; }
                    else { t.p.manager = f.manager.trim(); name = t.p.name; }
                    this.$dispatch('lg-toast', { type: 'success', message: name + ' updated.' });
                }
                this.open = false;
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Hubs</h1>
                <p>A province hub sorts and ships between provinces. Stations deliver to buyers in one municipality.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openAdd('province')"><x-logistics.icon name="plus" :size="18" /> Add hub</button>
            </div>
        </div>

        <template x-for="p in provinces" :key="p.name">
            <section class="lg-card" :aria-label="p.name">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title"><span x-text="p.name"></span> <span class="lg-pill lg-pill--violet">Province hub</span></h2>
                        <p class="lg-section-sub">
                            <span x-text="p.place"></span> &middot; Manager: <span x-text="p.manager"></span>
                            &middot; <span x-text="p.stations.length + (p.stations.length === 1 ? ' station' : ' stations')"></span>
                            &middot; <span x-text="riders(p) + ' riders'"></span>
                        </p>
                    </div>
                    <div class="lg-actions">
                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openAdd('station', p.name)"><x-logistics.icon name="plus" :size="16" /> Add station</button>
                        <button type="button" class="lg-btn lg-btn--ghost lg-btn--sm" :aria-label="'Edit ' + p.name" @click="openEdit(p, null)"><x-logistics.icon name="edit" :size="16" /> Edit</button>
                        <span class="lg-pill lg-pill--green">Active</span>
                    </div>
                </div>
                <div class="lg-table-wrap" x-show="p.stations.length">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Station</th><th>Manager</th><th class="is-num">Riders</th><th>Status</th><th class="is-actions">Actions</th></tr></thead>
                        <tbody>
                            <template x-for="s in p.stations" :key="s.name">
                                <tr>
                                    <td data-label="Station"><div class="lg-cell-title" x-text="s.name"></div><div class="lg-cell-sub" x-text="s.municipality"></div></td>
                                    <td data-label="Manager">
                                        <span class="lg-pill lg-pill--amber" x-show="s.manager === 'Pending invite'">Pending invite</span>
                                        <span x-show="s.manager !== 'Pending invite'" x-text="s.manager"></span>
                                    </td>
                                    <td data-label="Riders" class="is-num" x-text="s.riders"></td>
                                    <td data-label="Status"><span class="lg-pill" :class="s.status === 'Active' ? 'lg-pill--green' : 'lg-pill--amber'" x-text="s.status"></span></td>
                                    <td data-label="Actions" class="is-actions"><button type="button" class="lg-btn lg-btn--outline lg-btn--sm" :aria-label="'Edit ' + s.name" @click="openEdit(p, s)"><x-logistics.icon name="edit" :size="16" /> Edit</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-logistics.empty x-show="!p.stations.length" x-cloak icon="map-pin" title="No stations yet" text="Add a station for each municipality this hub delivers to.">
                    <button type="button" class="lg-btn lg-btn--sm" @click="openAdd('station', p.name)">Add station</button>
                </x-logistics.empty>
            </section>
        </template>

        <x-logistics.empty x-show="!provinces.length" x-cloak icon="map-pin" title="No hubs yet" text="Start with a province hub, then add stations under it.">
            <button type="button" class="lg-btn lg-btn--sm" @click="openAdd('province')">Add hub</button>
        </x-logistics.empty>

        {{-- Add / edit hub --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="hubDlgTitle" tabindex="-1" x-ref="dlg">
                <form @submit.prevent="save()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="hubDlgTitle" x-text="mode === 'add' ? 'Add hub' : (editing && editing.s ? 'Edit ' + editing.s.name : 'Edit ' + (editing ? editing.p.name : 'hub'))"></h3>
                            <p x-show="mode === 'add'">The hub manager gets a temporary password and must change it on first login.</p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field" x-show="mode === 'add'">
                            <label for="hubType">Hub type</label>
                            <select id="hubType" class="lg-select" x-model="form.type">
                                <option value="province">Province hub</option>
                                <option value="station">Municipality station</option>
                            </select>
                        </div>
                        <div class="lg-field" x-show="mode === 'add' && form.type === 'station'">
                            <label for="hubParent">Reports to province hub <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="hubParent" class="lg-select" x-model="form.parent" :aria-invalid="!!errors.parent">
                                <template x-for="p in provinces" :key="p.name"><option :value="p.name" x-text="p.name"></option></template>
                            </select>
                            <p class="lg-error" x-show="errors.parent" x-text="errors.parent" role="alert"></p>
                        </div>
                        <div class="lg-field" x-show="mode === 'add' && form.type === 'province'">
                            <label for="hubProv">Province <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubProv" class="lg-input" x-model="form.province" placeholder="Laguna" autocomplete="off" maxlength="60" :aria-invalid="!!errors.province" aria-describedby="hubProvErr">
                            <p class="lg-error" id="hubProvErr" x-show="errors.province" x-text="errors.province" role="alert"></p>
                        </div>
                        <div class="lg-field" x-show="form.type === 'station'">
                            <label for="hubMun">Municipality <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="hubMun" class="lg-input" x-model="form.municipality" placeholder="Santa Cruz" autocomplete="off" maxlength="60" :aria-invalid="!!errors.municipality" aria-describedby="hubMunErr">
                            <p class="lg-error" id="hubMunErr" x-show="errors.municipality" x-text="errors.municipality" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="mgrName">Manager name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="mgrName" class="lg-input" x-model="form.manager" placeholder="Full name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.manager" aria-describedby="mgrNameErr">
                            <p class="lg-error" id="mgrNameErr" x-show="errors.manager" x-text="errors.manager" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="mgrEmail"><span x-text="mode === 'add' ? 'Manager email' : 'New manager email (optional)'"></span> <span class="lg-req" aria-hidden="true" x-show="mode === 'add'">*</span></label>
                            <input id="mgrEmail" type="email" class="lg-input" x-model="form.email" placeholder="manager@company.com" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="mgrEmailErr">
                            <p class="lg-error" id="mgrEmailErr" x-show="errors.email" x-text="errors.email" role="alert"></p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn" x-text="mode === 'add' ? 'Create hub' : 'Save changes'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
