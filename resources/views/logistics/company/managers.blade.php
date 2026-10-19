<x-logistics.shell title="Hub Managers" role="company">
    <div class="lg-page"
         x-data="{
            managers: @js($managers),
            hubNames: @js($hubNames),
            q: '',
            hub: 'all',
            // add dialog
            addOpen: false,
            form: { name: '', email: '', hub: '', role: 'Hub manager' },
            errors: {},
            // deactivate dialog
            offOpen: false,
            target: null,
            tone: { Active: 'green', Invited: 'amber', Inactive: 'gray' },
            get shown() {
                const q = this.q.trim().toLowerCase();
                return this.managers.filter(m => (this.hub === 'all' || m.hub === this.hub) && (!q || m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q)));
            },
            // true when this is the only active hub manager of their hub
            soleManager(m) {
                return m.role === 'Hub manager' && !this.managers.some(x => x !== m && x.hub === m.hub && x.role === 'Hub manager' && x.status === 'Active');
            },
            openAdd() {
                this.form = { name: '', email: '', hub: this.hubNames[0] || '', role: 'Hub manager' };
                this.errors = {}; this.addOpen = true;
                this.$nextTick(() => this.$refs.addName && this.$refs.addName.focus());
            },
            saveAdd() {
                const e = {}, f = this.form;
                if (!f.name.trim()) e.name = 'Enter the full name.';
                if (!/^\S+@\S+\.\S+$/.test(f.email.trim())) e.email = 'Enter a valid email address.';
                else if (this.managers.some(m => m.email.toLowerCase() === f.email.trim().toLowerCase())) e.email = 'Someone already uses this email.';
                if (!f.hub) e.hub = 'Choose a hub.';
                this.errors = e;
                if (Object.keys(e).length) { this.$nextTick(() => { const el = this.$root.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                this.managers.push({ name: f.name.trim(), email: f.email.trim(), hub: f.hub, role: f.role, last_login: 'Never', status: 'Invited' });
                this.addOpen = false;
                this.$dispatch('lg-toast', { type: 'success', message: 'Invite sent to ' + f.email.trim() + ' with a temporary password.' });
            },
            askOff(m) { this.target = m; this.offOpen = true; },
            confirmOff() {
                this.target.status = 'Inactive'; this.offOpen = false;
                this.$dispatch('lg-toast', { type: 'success', message: this.target.name + ' deactivated. They can no longer sign in.' });
            },
            reactivate(m) { m.status = 'Active'; this.$dispatch('lg-toast', { type: 'success', message: m.name + ' reactivated.' }); },
            resend(m) { this.$dispatch('lg-toast', { type: 'info', message: 'Invite sent again to ' + m.email + '.' }); }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Hub Managers</h1>
                <p>Each manager can only see and act on their own hub.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openAdd()"><x-logistics.icon name="plus" :size="18" /> Add manager</button>
            </div>
        </div>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search name or email" aria-label="Search managers">
            </div>
            <select class="lg-select" x-model="hub" aria-label="Filter by hub">
                <option value="all">All hubs</option>
                @foreach ($hubNames as $h)<option>{{ $h }}</option>@endforeach
            </select>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' person' : ' people')"></span>
        </div>

        <section class="lg-card" aria-label="Hub managers">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Manager</th><th>Hub</th><th>Role</th><th>Last login</th><th>Status</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="m in shown" :key="m.email">
                            <tr>
                                <td data-label="Manager">
                                    <div class="lg-person">
                                        <span class="lg-avatar lg-avatar--sm" aria-hidden="true" x-text="m.name.charAt(0)"></span>
                                        <span><strong x-text="m.name"></strong><small x-text="m.email"></small></span>
                                    </div>
                                </td>
                                <td data-label="Hub" x-text="m.hub"></td>
                                <td data-label="Role"><span class="lg-pill" :class="m.role === 'Hub manager' ? 'lg-pill--brand' : 'lg-pill--gray'" x-text="m.role"></span></td>
                                <td data-label="Last login" x-text="m.last_login"></td>
                                <td data-label="Status"><span class="lg-pill" :class="'lg-pill--' + tone[m.status]" x-text="m.status"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" x-show="m.status === 'Invited'" @click="resend(m)">Resend invite</button>
                                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" x-show="m.status === 'Active'" @click="askOff(m)">Deactivate</button>
                                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" x-show="m.status === 'Inactive'" @click="reactivate(m)">Reactivate</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!shown.length" x-cloak icon="user-cog" title="No managers match" text="Try a different hub or search, or invite a new manager.">
                <div class="lg-actions" style="justify-content:center">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; hub = 'all'">Clear filters</button>
                    <button type="button" class="lg-btn lg-btn--sm" @click="openAdd()">Add manager</button>
                </div>
            </x-logistics.empty>
        </section>

        {{-- Add manager --}}
        <div class="lg-overlay" x-show="addOpen" x-cloak @click.self="addOpen = false" @keydown.escape.window="addOpen = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="addMgrTitle">
                <form @submit.prevent="saveAdd()" novalidate>
                    <div class="lg-modal__head">
                        <div><h3 id="addMgrTitle">Add manager</h3><p>They receive a temporary password by email.</p></div>
                        <button type="button" class="lg-toast__close" @click="addOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="mName">Full name <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="mName" class="lg-input" x-ref="addName" x-model="form.name" autocomplete="off" maxlength="80" :aria-invalid="!!errors.name" aria-describedby="mNameErr">
                            <p class="lg-error" id="mNameErr" x-show="errors.name" x-text="errors.name" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="mEmail">Email <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="mEmail" type="email" class="lg-input" x-model="form.email" autocomplete="off" maxlength="120" :aria-invalid="!!errors.email" aria-describedby="mEmailErr">
                            <p class="lg-error" id="mEmailErr" x-show="errors.email" x-text="errors.email" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="mHub">Hub <span class="lg-req" aria-hidden="true">*</span></label>
                            <select id="mHub" class="lg-select" x-model="form.hub" :aria-invalid="!!errors.hub">
                                @foreach ($hubNames as $h)<option>{{ $h }}</option>@endforeach
                            </select>
                        </div>
                        <div class="lg-field">
                            <label for="mRole">Role</label>
                            <select id="mRole" class="lg-select" x-model="form.role"><option>Hub manager</option><option>Hub staff (sorter)</option></select>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="addOpen = false">Cancel</button>
                        <button type="submit" class="lg-btn">Send invite</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Deactivate confirmation --}}
        <div class="lg-overlay" x-show="offOpen" x-cloak @click.self="offOpen = false" @keydown.escape.window="offOpen = false">
            <div class="lg-modal" role="alertdialog" aria-modal="true" aria-labelledby="offTitle" aria-describedby="offText">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="offTitle" x-text="'Deactivate ' + (target ? target.name : '') + '?'"></h3>
                        <p id="offText">They will no longer be able to sign in. You can reactivate them later.</p>
                    </div>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="target && soleManager(target)">
                    <div class="lg-note lg-note--warn">
                        <x-logistics.icon name="alert" :size="18" />
                        <span><strong x-text="target ? target.hub : ''"></strong> will have no active hub manager. Add another before you deactivate if parcels are moving there.</span>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="offOpen = false">Keep active</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" @click="confirmOff()">Deactivate</button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
