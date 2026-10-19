@php
    $isCompany = $role === 'company';
    $name = ['company' => 'JNT Express Philippines', 'province' => 'JNT Laguna Hub', 'station' => 'JNT Santa Cruz Station'][$role];
    $roleLabel = ['company' => 'Company', 'province' => 'Province hub', 'station' => 'Municipality station'][$role];
    // Where this account sits in the network (company > province hub > station)
    $path = match ($role) {
        'company' => ['JNT Express Philippines'],
        'province' => ['JNT Express Philippines', 'Laguna Hub'],
        default => ['JNT Express Philippines', 'Laguna Hub', 'Santa Cruz Station'],
    };
@endphp
<x-logistics.shell title="Account Management" :role="$role">
    <div class="lg-page"
         x-data="{
            d: { name: @js($name), email: 'contact@jnt-demo.ph', phone: '0917 123 4567', extra: @js($isCompany ? 'BP-2026-004418' : ($role === 'province' ? 'Santa Rosa, Laguna' : 'Santa Cruz, Laguna')) },
            de: {},
            pw: { current: '', next: '', again: '' },
            pe: {},
            show: { current: false, next: false, again: false },
            get hasLen() { return this.pw.next.length >= 8; },
            get hasNum() { return /[0-9]/.test(this.pw.next); },
            get hasLetter() { return /[A-Za-z]/.test(this.pw.next); },
            saveDetails() {
                const e = {}, d = this.d;
                if (!d.name.trim()) e.name = 'Enter a name.';
                if (!/^\S+@\S+\.\S+$/.test(d.email.trim())) e.email = 'Enter a valid email address.';
                if (!/^[0-9+()\s-]{7,20}$/.test(d.phone.trim())) e.phone = 'Enter a phone number with 7 to 20 digits.';
                if (!d.extra.trim()) e.extra = 'This field is required.';
                this.de = e;
                if (Object.keys(e).length) { this.$nextTick(() => { const el = this.$refs.detailsForm.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                this.$dispatch('lg-toast', { type: 'success', message: 'Details saved (preview only, nothing is stored).' });
            },
            savePassword() {
                const e = {}, p = this.pw;
                if (!p.current) e.current = 'Enter your current password.';
                if (!(this.hasLen && this.hasNum && this.hasLetter)) e.next = 'The new password does not meet all the rules below.';
                if (p.again !== p.next) e.again = 'The two passwords do not match.';
                this.pe = e;
                if (Object.keys(e).length) { this.$nextTick(() => { const el = this.$refs.pwForm.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                this.pw = { current: '', next: '', again: '' };
                this.show = { current: false, next: false, again: false };
                this.$dispatch('lg-toast', { type: 'success', message: 'Password updated (preview only, nothing is stored).' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Account Management</h1>
                <p>{{ $isCompany ? 'Company details shown to Vendo and to your hubs.' : 'Hub details and your login.' }}</p>
            </div>
        </div>

        <section class="lg-card" aria-label="Account summary">
            <div class="lg-identity">
                <span class="lg-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                <div>
                    <p class="lg-identity__name">{{ $name }}</p>
                    <div class="lg-crumb-chips" aria-label="Where this account sits in your network">
                        <span class="lg-pill lg-pill--violet">{{ $roleLabel }}</span>
                        @foreach ($path as $step)
                            @if (! $loop->first)<x-logistics.icon name="arrow-right" :size="14" />@endif
                            <span>{{ $step }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="lg-card" aria-labelledby="detTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="detTitle">{{ $isCompany ? 'Company details' : 'Hub details' }}</h2></div>
            <form @submit.prevent="saveDetails()" class="lg-card__body lg-form-grid" x-ref="detailsForm" novalidate>
                <div class="lg-field">
                    <label for="a1">{{ $isCompany ? 'Company name' : 'Hub name' }} <span class="lg-req" aria-hidden="true">*</span></label>
                    <input id="a1" class="lg-input" x-model="d.name" autocomplete="organization" maxlength="100" :aria-invalid="!!de.name" aria-describedby="a1e">
                    <p class="lg-error" id="a1e" x-show="de.name" x-text="de.name" role="alert"></p>
                </div>
                <div class="lg-field">
                    <label for="a2">Contact email <span class="lg-req" aria-hidden="true">*</span></label>
                    <input id="a2" type="email" class="lg-input" x-model="d.email" autocomplete="email" maxlength="120" :aria-invalid="!!de.email" aria-describedby="a2e">
                    <p class="lg-error" id="a2e" x-show="de.email" x-text="de.email" role="alert"></p>
                </div>
                <div class="lg-field">
                    <label for="a3">Phone <span class="lg-req" aria-hidden="true">*</span></label>
                    <input id="a3" type="tel" class="lg-input" x-model="d.phone" autocomplete="tel" maxlength="20" :aria-invalid="!!de.phone" aria-describedby="a3e">
                    <p class="lg-error" id="a3e" x-show="de.phone" x-text="de.phone" role="alert"></p>
                </div>
                <div class="lg-field">
                    <label for="a4">{{ $isCompany ? 'Business permit no.' : 'Address' }} <span class="lg-req" aria-hidden="true">*</span></label>
                    <input id="a4" class="lg-input" x-model="d.extra" autocomplete="off" maxlength="120" :aria-invalid="!!de.extra" aria-describedby="a4e">
                    <p class="lg-error" id="a4e" x-show="de.extra" x-text="de.extra" role="alert"></p>
                </div>
                @unless ($isCompany)
                    <div class="lg-field">
                        <label for="a5">Company</label>
                        <input id="a5" class="lg-input" value="JNT Express Philippines" disabled aria-describedby="a5h">
                        <p class="lg-hint" id="a5h">Set by your company. Contact them to change it.</p>
                    </div>
                @endunless
                <div class="is-wide"><button type="submit" class="lg-btn">Save changes</button></div>
            </form>
        </section>

        <section class="lg-card" aria-labelledby="pwTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="pwTitle">Change password</h2></div>
            <form @submit.prevent="savePassword()" class="lg-card__body lg-form-grid" x-ref="pwForm" novalidate>
                @foreach ([['current', 'Current password', 'current-password', 'p1'], ['next', 'New password', 'new-password', 'p2'], ['again', 'Confirm new password', 'new-password', 'p3']] as [$key, $label, $auto, $id])
                    <div class="lg-field">
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
                <div class="lg-field">
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
</x-logistics.shell>
