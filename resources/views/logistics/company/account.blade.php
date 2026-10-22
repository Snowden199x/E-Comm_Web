{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php, nothing is saved. Spec section 8 (Company Admin > Account Management):
     company profile, head office, business documents, manager profile, change password. --}}
<x-logistics.shell title="Account Management" role="company">
    <div class="lg-page"
         x-data="{
            c: @js($company), ce: {},
            o: @js($office), oe: {},
            m: @js($manager), me: {},
            docs: @js($documents), docNote: '',
            pw: { current: '', next: '', again: '' }, pe: {}, show: { current: false, next: false, again: false },
            get hasLen() { return this.pw.next.length >= 8; },
            get hasNum() { return /[0-9]/.test(this.pw.next); },
            get hasLetter() { return /[A-Za-z]/.test(this.pw.next); },
            toast(type, message) { this.$dispatch('lg-toast', { type, message }); },
            focusFirst(ref) { this.$nextTick(() => { const el = this.$refs[ref].querySelector('[aria-invalid=true]'); if (el) el.focus(); }); },
            email(v) { return /^\S+@\S+\.\S+$/.test(v.trim()); },
            phone(v) { return /^[0-9+()\s-]{7,20}$/.test(v.trim()); },
            saveCompany() {
                const e = {}, c = this.c;
                if (!c.name.trim()) e.name = 'Enter the company name.';
                if (!this.email(c.email)) e.email = 'Enter a valid email address.';
                if (!this.phone(c.phone)) e.phone = 'Enter a phone number with 7 to 20 digits.';
                if (!c.permit.trim()) e.permit = 'Enter the business permit number.';
                this.ce = e;
                if (Object.keys(e).length) return this.focusFirst('companyForm');
                this.toast('success', 'Company profile saved (preview only, nothing is stored).');
            },
            saveOffice() {
                const e = {}, o = this.o;
                if (!o.street.trim()) e.street = 'Enter the street address.';
                if (!o.barangay.trim()) e.barangay = 'Enter the barangay.';
                if (!o.city.trim()) e.city = 'Enter the city or municipality.';
                if (!o.province.trim()) e.province = 'Enter the province.';
                if (!/^[0-9]{4}$/.test(o.postal.trim())) e.postal = 'Enter a 4-digit postal code.';
                this.oe = e;
                if (Object.keys(e).length) return this.focusFirst('officeForm');
                this.toast('success', 'Head office address saved (preview only, nothing is stored).');
            },
            saveManager() {
                const e = {}, m = this.m;
                if (!m.name.trim()) e.name = 'Enter your name.';
                if (!this.email(m.email)) e.email = 'Enter a valid email address.';
                if (!this.phone(m.phone)) e.phone = 'Enter a phone number with 7 to 20 digits.';
                this.me = e;
                if (Object.keys(e).length) return this.focusFirst('managerForm');
                this.toast('success', 'Your profile saved (preview only, nothing is stored).');
            },
            replaceDoc(d, ev) {
                const f = ev.target.files[0];
                ev.target.value = '';
                if (!f) return;
                if (!/\.(pdf|jpe?g|png)$/i.test(f.name)) return this.toast('error', 'Upload a PDF, JPG or PNG file.');
                if (f.size > 5 * 1024 * 1024) return this.toast('error', 'The file is larger than 5 MB.');
                d.file = f.name; d.size = (f.size / 1024 < 1024 ? Math.max(1, Math.round(f.size / 1024)) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB');
                d.uploaded = new Date().toISOString().slice(0, 10); d.status = 'Under review';
                this.docNote = d.name + ' was replaced. Vendo reviews the new file before it counts as verified. Your account stays active meanwhile.';
                this.toast('success', d.name + ' uploaded for review (preview only, nothing is stored).');
            },
            fmtDate(s) { return new Date(s + 'T00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); },
            savePassword() {
                const e = {}, p = this.pw;
                if (!p.current) e.current = 'Enter your current password.';
                if (!(this.hasLen && this.hasNum && this.hasLetter)) e.next = 'The new password does not meet all the rules below.';
                if (p.again !== p.next) e.again = 'The two passwords do not match.';
                this.pe = e;
                if (Object.keys(e).length) return this.focusFirst('pwForm');
                this.pw = { current: '', next: '', again: '' };
                this.show = { current: false, next: false, again: false };
                this.toast('success', 'Password updated (preview only, nothing is stored).');
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Account Management</h1>
                <p>Company details shown to Vendo and to your hubs, your head office, documents and login.</p>
            </div>
        </div>

        <section class="lg-card" aria-label="Account summary">
            <div class="lg-identity">
                <span class="lg-identity__avatar" aria-hidden="true" x-text="c.name.trim().charAt(0).toUpperCase()"></span>
                <div>
                    <p class="lg-identity__name" x-text="c.name"></p>
                    <div class="lg-crumb-chips">
                        <span class="lg-pill lg-pill--violet">Company</span>
                        <span>Approved by Vendo on <span x-text="fmtDate(@js($approved))"></span></span>
                    </div>
                </div>
            </div>
        </section>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));align-items:start">
            <section class="lg-card" aria-labelledby="coTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="coTitle">Company profile</h2></div>
                <form @submit.prevent="saveCompany()" class="lg-card__body lg-form-grid" x-ref="companyForm" novalidate>
                    @foreach ([['name', 'Company name', 'organization', 100, 'text', 'cp1'], ['email', 'Contact email', 'email', 120, 'email', 'cp2'], ['phone', 'Phone', 'tel', 20, 'tel', 'cp3'], ['permit', 'Business permit no.', 'off', 60, 'text', 'cp4']] as [$k, $label, $auto, $max, $type, $id])
                        <div class="lg-field">
                            <label for="{{ $id }}">{{ $label }} <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="{{ $id }}" type="{{ $type }}" class="lg-input" x-model="c.{{ $k }}" autocomplete="{{ $auto }}" maxlength="{{ $max }}" :aria-invalid="!!ce.{{ $k }}" aria-describedby="{{ $id }}e">
                            <p class="lg-error" id="{{ $id }}e" x-show="ce.{{ $k }}" x-text="ce.{{ $k }}" role="alert"></p>
                        </div>
                    @endforeach
                    <div class="is-wide"><button type="submit" class="lg-btn">Save company profile</button></div>
                </form>
            </section>

            <section class="lg-card" aria-labelledby="hoTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="hoTitle">Head office</h2>
                        <p class="lg-section-sub">Your company address. Hubs have their own addresses under Province Hubs.</p>
                    </div>
                </div>
                <form @submit.prevent="saveOffice()" class="lg-card__body lg-form-grid" x-ref="officeForm" novalidate>
                    <div class="lg-field is-wide">
                        <label for="ho1">Street address <span class="lg-req" aria-hidden="true">*</span></label>
                        <input id="ho1" class="lg-input" x-model="o.street" autocomplete="address-line1" maxlength="150" :aria-invalid="!!oe.street" aria-describedby="ho1e">
                        <p class="lg-error" id="ho1e" x-show="oe.street" x-text="oe.street" role="alert"></p>
                    </div>
                    @foreach ([['barangay', 'Barangay', 'ho2', 'address-level3'], ['city', 'City or municipality', 'ho3', 'address-level2'], ['province', 'Province', 'ho4', 'address-level1'], ['postal', 'Postal code', 'ho5', 'postal-code']] as [$k, $label, $id, $auto])
                        <div class="lg-field">
                            <label for="{{ $id }}">{{ $label }} <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="{{ $id }}" class="lg-input" x-model="o.{{ $k }}" autocomplete="{{ $auto }}" maxlength="80" :aria-invalid="!!oe.{{ $k }}" aria-describedby="{{ $id }}e">
                            <p class="lg-error" id="{{ $id }}e" x-show="oe.{{ $k }}" x-text="oe.{{ $k }}" role="alert"></p>
                        </div>
                    @endforeach
                    <div class="is-wide"><button type="submit" class="lg-btn">Save head office</button></div>
                </form>
            </section>
        </div>

        <section class="lg-card" aria-labelledby="docTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="docTitle">Business documents</h2>
                    <p class="lg-section-sub">PDF, JPG or PNG, up to 5 MB. A replaced document is checked by Vendo again.</p>
                </div>
            </div>
            <div class="lg-note" style="padding:0 24px 12px" x-show="docNote" x-cloak role="status">
                <x-logistics.icon name="info" :size="18" />
                <span x-text="docNote"></span>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Document</th><th>File</th><th>Uploaded</th><th>Status</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="d in docs" :key="d.id">
                            <tr>
                                <td data-label="Document"><span class="lg-cell-title" x-text="d.name"></span></td>
                                <td data-label="File"><div x-text="d.file"></div><div class="lg-cell-sub" x-text="d.size"></div></td>
                                <td data-label="Uploaded" x-text="fmtDate(d.uploaded)"></td>
                                <td data-label="Status"><span class="lg-pill" :class="d.status === 'Verified' ? 'lg-pill--green' : 'lg-pill--amber'" x-text="d.status"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <input type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" :id="'doc' + d.id" @change="replaceDoc(d, $event)">
                                        <label :for="'doc' + d.id" class="lg-btn lg-btn--outline lg-btn--sm" tabindex="0" role="button" @keydown.enter.prevent="$el.click()" @keydown.space.prevent="$el.click()" :aria-label="'Replace ' + d.name">Replace</label>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));align-items:start">
            <section class="lg-card" aria-labelledby="mgTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="mgTitle">Your profile</h2>
                        <p class="lg-section-sub">Last signed in <span x-text="new Date(@js($manager['last_login'])).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })"></span>.</p>
                    </div>
                </div>
                <form @submit.prevent="saveManager()" class="lg-card__body lg-form-grid" x-ref="managerForm" novalidate>
                    @foreach ([['name', 'Full name', 'name', 'text', 'mg1'], ['email', 'Email', 'email', 'email', 'mg2'], ['phone', 'Phone', 'tel', 'tel', 'mg3']] as [$k, $label, $auto, $type, $id])
                        <div class="lg-field">
                            <label for="{{ $id }}">{{ $label }} <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="{{ $id }}" type="{{ $type }}" class="lg-input" x-model="m.{{ $k }}" autocomplete="{{ $auto }}" maxlength="120" :aria-invalid="!!me.{{ $k }}" aria-describedby="{{ $id }}e">
                            <p class="lg-error" id="{{ $id }}e" x-show="me.{{ $k }}" x-text="me.{{ $k }}" role="alert"></p>
                        </div>
                    @endforeach
                    <div class="lg-field">
                        <label for="mg4">Role</label>
                        <input id="mg4" class="lg-input" :value="m.role" disabled aria-describedby="mg4h">
                        <p class="lg-hint" id="mg4h">Set by Vendo when the company was approved.</p>
                    </div>
                    <div class="is-wide"><button type="submit" class="lg-btn">Save my profile</button></div>
                </form>
            </section>

            <section class="lg-card" aria-labelledby="pwTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="pwTitle">Change password</h2></div>
                <form @submit.prevent="savePassword()" class="lg-card__body lg-form-grid" x-ref="pwForm" novalidate>
                    @foreach ([['current', 'Current password', 'current-password', 'p1'], ['next', 'New password', 'new-password', 'p2'], ['again', 'Confirm new password', 'new-password', 'p3']] as [$key, $label, $auto, $id])
                        <div class="lg-field is-wide">
                            <label for="{{ $id }}">{{ $label }} <span class="lg-req" aria-hidden="true">*</span></label>
                            <div class="lg-input-group">
                                <input id="{{ $id }}" :type="show.{{ $key }} ? 'text' : 'password'" class="lg-input" x-model="pw.{{ $key }}" autocomplete="{{ $auto }}" :aria-invalid="!!pe.{{ $key }}" aria-describedby="{{ $id }}e">
                                <button type="button" class="lg-input-group__btn" @click="show.{{ $key }} = !show.{{ $key }}" :aria-label="show.{{ $key }} ? 'Hide {{ strtolower($label) }}' : 'Show {{ strtolower($label) }}'" :aria-pressed="show.{{ $key }}">
                                    <x-logistics.icon name="eye" :size="18" x-show="!show.{{ $key }}" />
                                    <x-logistics.icon name="eye-off" :size="18" x-show="show.{{ $key }}" x-cloak />
                                </button>
                            </div>
                            <p class="lg-error" id="{{ $id }}e" x-show="pe.{{ $key }}" x-text="pe.{{ $key }}" role="alert"></p>
                        </div>
                    @endforeach
                    <div class="lg-field is-wide">
                        <p class="lg-label" id="rulesLabel">New password rules</p>
                        <ul class="lg-rules" aria-labelledby="rulesLabel">
                            <li :class="{ 'is-ok': hasLen }"><x-logistics.icon name="check-circle" :size="14" x-show="hasLen" /><x-logistics.icon name="clock" :size="14" x-show="!hasLen" x-cloak /> At least 8 characters</li>
                            <li :class="{ 'is-ok': hasLetter }"><x-logistics.icon name="check-circle" :size="14" x-show="hasLetter" /><x-logistics.icon name="clock" :size="14" x-show="!hasLetter" x-cloak /> A letter</li>
                            <li :class="{ 'is-ok': hasNum }"><x-logistics.icon name="check-circle" :size="14" x-show="hasNum" /><x-logistics.icon name="clock" :size="14" x-show="!hasNum" x-cloak /> A number</li>
                        </ul>
                    </div>
                    <div class="is-wide"><button type="submit" class="lg-btn lg-btn--outline">Update password</button></div>
                </form>
            </section>
        </div>
    </div>
</x-logistics.shell>
