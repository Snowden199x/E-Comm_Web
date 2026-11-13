{{--
    Account management for all three logistics roles: Company (role=company), Province Hub (province) and Station Hub (station).
    Fed by PortalAccountController::index (role, user, facility, company, center, documentRequests, pendingEmail).
    Posts go to ::update (profile; the company also saves its office address), ::password, ::requestEmailChange and, for the company only,
    ::requestDocumentChange. Routes, fields and rules are unchanged.
    Look: the seller pastel lilac system, the same .lgh layer as the Province Hub pages. Styles: hubs-theme.css, drivers-theme.css
    (callouts, pills, dialogs) and account-theme.css, loaded last. The address map is the shared x-address-map component; the logistics layout
    already loads Alpine and the full Tailwind build it needs, so no extra build is added. Dialogs sit in .lgh-layer and carry their own tokens.
    Company: profile, office address with map, password, verification documents. Province Hub and Station Hub: profile and password;
    the facility name, address and company are read-only because the parent network manages them.
    Errors: every message says what happened and how to fix it, under the field and in the top banner; the form is checked in the browser first.
    Loading: shell skeleton on open; spinner on each save button (admin button-loading.js).
--}}
@php
    $roleName = ['company' => 'Company', 'province' => 'Province Hub', 'station' => 'Station Hub'][$role] ?? ucfirst($role);
    $isCompany = $role === 'company';
    $err = fn ($key) => $errors->has($key) ? 'true' : 'false';
    $form = old('_form');
    $expires = $pendingEmail ? \Illuminate\Support\Carbon::parse($pendingEmail->expires_at)->format('M j, g:i A') : null;
    $docTone = ['pending' => 'butter', 'approved' => 'mint', 'rejected' => 'rose'];
    $docLabel = ['pending' => 'Awaiting review', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $officeAddress = $isCompany ? trim(implode(', ', array_filter([$center->house_no, $center->street, $center->barangay, $center->municipality, $center->province]))) : '';
@endphp
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/account-theme.css'])
@endpush

<x-logistics.shell title="Account Management" :role="$role">
    <div class="lgh lga"
         x-data="{
            dialog: @js($form === 'email' ? 'email' : null),
            pw: { current: '', next: '', again: '' },
            show: { current: false, next: false, again: false },
            errors: {},
            doc: { type: @js((string) old('document_type', '')), name: '' },
            get hasLen() { return this.pw.next.length >= 8; },
            get hasLetter() { return /[A-Za-z]/.test(this.pw.next); },
            get hasNum() { return /[0-9]/.test(this.pw.next); },
            get matches() { return this.pw.again !== '' && this.pw.again === this.pw.next; },
            init() {
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            // Shown when the page opens after a failed save: the same message as the server, under the field.
            toast(message) { this.$dispatch('lg-toast', { type: 'error', message: message }); },
            // Browser check before sending. A failed check stops the form and focuses the first field with a problem.
            check(event, form) {
                const f = event.target, e = {};
                const v = (name) => (f.elements[name] ? f.elements[name].value.trim() : '');
                if (form === 'profile') {
                    if (!v('name')) e.name = 'Name is empty. Enter your full name.';
                    if (!v('phone_number')) e.phone_number = 'Phone number is empty. Enter a number we can reach you on.';
                    @if ($isCompany)
                    if (!v('business_name')) e.business_name = 'Company name is empty. Enter the company name.';
                    if (!v('province')) e.province = 'Province is empty. Enter the province, or search the address on the map.';
                    if (!v('municipality')) e.municipality = 'Municipality or city is empty. Enter it, or search the address on the map.';
                    if (!v('barangay')) e.barangay = 'Barangay is empty. Enter the barangay, or search the address on the map.';
                    @endif
                } else if (form === 'password') {
                    if (!this.pw.current) e.current_password = 'Current password is empty. Enter the password you sign in with.';
                    if (!this.pw.next) e.password = 'New password is empty. Enter a new password.';
                    else if (!this.hasLen) e.password = 'New password is too short. Use at least 8 characters.';
                    else if (!this.hasLetter) e.password = 'New password needs at least one letter. Add a letter and try again.';
                    else if (!this.hasNum) e.password = 'New password needs at least one number. Add a number and try again.';
                    else if (this.pw.again !== this.pw.next) e.password_confirmation = 'The two new passwords don’t match. Type the same password in both fields.';
                } else if (form === 'email') {
                    const email = v('new_email');
                    if (!email) e.new_email = 'New email is empty. Enter the email you want to use.';
                    else if (!/^\S+@\S+\.\S+$/.test(email)) e.new_email = 'That doesn’t look like an email address. Check it, for example name@company.com.';
                    else if (email !== email.toLowerCase()) e.new_email = 'Email must be in lowercase. Retype it without capital letters.';
                    if (!v('current_password')) e.current_password = 'Current password is empty. Enter the password you sign in with.';
                } else if (form === 'document') {
                    const file = f.elements.document.files[0];
                    if (!this.doc.type) e.document_type = 'Document type is not chosen. Pick Valid ID or Business permit.';
                    if (!file) e.document = 'No file is chosen. Pick a PDF, JPG or PNG file.';
                    else if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) e.document = 'This file type isn’t accepted. Upload a PDF, JPG or PNG.';
                    else if (file.size > 5 * 1024 * 1024) e.document = 'This file is too large. Upload a file up to 5 MB.';
                }
                this.errors = e;
                if (Object.keys(e).length) {
                    event.preventDefault();
                    // The admin button spinner starts on submit, so stop it for a form that did not send.
                    f.querySelectorAll('button[type=submit]').forEach((b) => window.vdButtonLoading && window.vdButtonLoading(b, false));
                    this.$nextTick(() => f.querySelector('[aria-invalid=true]')?.focus());
                }
            }
         }"
         @keydown.escape.window="dialog = null">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Account management</h1>
                <p>Manage your {{ strtolower($roleName) }} login and contact details.</p>
            </div>
            @if ($isCompany)
                <a href="{{ route('lgp.company.managers') }}" class="lgh-btn lgh-btn--soft"><x-logistics.icon name="users" :size="16" /> Manage facility managers</a>
            @endif
        </header>

        {{-- Who you are signed in as --}}
        <section class="lgh-card lga-identity" aria-label="Account summary">
            <span class="lga-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div class="lga-identity__text">
                <p class="lga-identity__name">{{ $user->name }}</p>
                <p class="lga-identity__meta">
                    <span class="lgh-pill lgh-pill--lilac">{{ $roleName }}</span>
                    <span>{{ $company->business_name }}</span>
                    @if ($facility)<span>{{ $facility->name }}</span>@endif
                </p>
            </div>
            @if ($facility)
                <p class="lga-identity__note"><x-logistics.icon name="info" :size="16" /> The facility name and address are managed by your {{ $role === 'station' ? 'Province Hub' : 'company' }}. Ask them to change these.</p>
            @endif
        </section>

        <div class="lga-grid">
            {{-- Left: profile (and, for the company, the office address). One form, one Save changes button. --}}
            <form method="POST" action="{{ route('lgp.'.$role.'.account.update') }}" class="lga-stack" novalidate @submit="check($event, 'profile')">
                @csrf @method('PATCH')
                <input type="hidden" name="_form" value="profile">

                <section class="lgh-card lga-panel" aria-labelledby="profileTitle">
                    <header class="lga-panel__head">
                        <span class="lga-tile lgh-tone-lilac"><x-logistics.icon name="user-cog" :size="20" /></span>
                        <div><h2 id="profileTitle">Profile</h2><p>Your name and how we reach you.</p></div>
                    </header>
                    <div class="lga-panel__body lga-fields">
                        <div class="lgh-field">
                            <label for="accountName">Name <span aria-hidden="true">*</span></label>
                            <input id="accountName" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required
                                   :aria-invalid="errors.name ? 'true' : '{{ $err('name') }}'" @input="errors.name = ''" aria-describedby="accountNameErr" :class="{ 'has-error': errors.name || @js($errors->has('name')) }">
                            <p class="lgh-err" id="accountNameErr" x-show="errors.name" x-cloak x-text="errors.name" role="alert"></p>
                            @error('name')<p class="lgh-err" x-show="!errors.name">{{ $message }}</p>@enderror
                        </div>
                        <div class="lgh-field">
                            <label for="accountPhone">Phone number <span aria-hidden="true">*</span></label>
                            <input id="accountPhone" name="phone_number" type="tel" inputmode="tel" value="{{ old('phone_number', $user->phone_number) }}" maxlength="24" autocomplete="tel" required
                                   :aria-invalid="errors.phone_number ? 'true' : '{{ $err('phone_number') }}'" @input="errors.phone_number = ''" aria-describedby="accountPhoneErr" :class="{ 'has-error': errors.phone_number || @js($errors->has('phone_number')) }">
                            <p class="lgh-err" id="accountPhoneErr" x-show="errors.phone_number" x-cloak x-text="errors.phone_number" role="alert"></p>
                            @error('phone_number')<p class="lgh-err" x-show="!errors.phone_number">{{ $message }}</p>@enderror
                        </div>
                        <div class="lgh-field lga-wide">
                            <label for="accountEmail">Verified email</label>
                            <div class="lga-inline">
                                <input id="accountEmail" value="{{ $user->email }}" readonly aria-describedby="accountEmailHint">
                                <button type="button" class="lgh-btn lgh-btn--soft" @click="dialog = 'email'"><x-logistics.icon name="edit" :size="16" /> Change email</button>
                            </div>
                            <p class="lgh-hint" id="accountEmailHint">You sign in with this email. Changing it needs your password and a link sent to the new address.</p>
                        </div>
                        @if ($pendingEmail)
                            <div class="lgh-callout lga-wide" role="status">
                                <x-logistics.icon name="clock" :size="18" />
                                <p>We sent a link to <strong>{{ $pendingEmail->new_email }}</strong>. It expires at {{ $expires }}. Sign in with your current email, then open the link to finish.</p>
                            </div>
                        @endif
                        @if ($facility)
                            <dl class="lga-facts lga-wide">
                                <div><dt>Facility</dt><dd>{{ $facility->name }}</dd></div>
                                <div><dt>Address</dt><dd>{{ $facility->address ?: 'Not set' }}</dd></div>
                                <div><dt>Company</dt><dd>{{ $company->business_name }}</dd></div>
                            </dl>
                        @endif
                    </div>
                </section>

                @if ($isCompany)
                    <section class="lgh-card lga-panel" aria-labelledby="officeTitle">
                        <header class="lga-panel__head">
                            <span class="lga-tile lgh-tone-lilac"><x-logistics.icon name="building" :size="20" /></span>
                            <div><h2 id="officeTitle">Company and office address</h2><p>This updates the company office only. Province Hubs and Station Hubs keep their own addresses.</p></div>
                        </header>
                        <div class="lga-panel__body lga-fields">
                            <div class="lgh-field lga-wide">
                                <label for="businessName">Company name <span aria-hidden="true">*</span></label>
                                <input id="businessName" name="business_name" value="{{ old('business_name', $company->business_name) }}" maxlength="160" required
                                       :aria-invalid="errors.business_name ? 'true' : '{{ $err('business_name') }}'" @input="errors.business_name = ''" :class="{ 'has-error': errors.business_name || @js($errors->has('business_name')) }">
                                <p class="lgh-err" x-show="errors.business_name" x-cloak x-text="errors.business_name" role="alert"></p>
                                @error('business_name')<p class="lgh-err" x-show="!errors.business_name">{{ $message }}</p>@enderror
                            </div>
                            <div class="lgh-field">
                                <label for="officeNumber">Office number</label>
                                <input id="officeNumber" name="house_no" value="{{ old('house_no', $center->house_no) }}" maxlength="80" autocomplete="off">
                                @error('house_no')<p class="lgh-err">{{ $message }}</p>@enderror
                            </div>
                            <div class="lgh-field">
                                <label for="officeStreet">Office street</label>
                                <input id="officeStreet" name="street" value="{{ old('street', $center->street) }}" maxlength="160" autocomplete="off">
                                @error('street')<p class="lgh-err">{{ $message }}</p>@enderror
                            </div>
                            @foreach ([['officeProvince', 'province', 'Province', $center->province], ['officeMunicipality', 'municipality', 'Municipality or city', $center->municipality], ['officeBarangay', 'barangay', 'Barangay', $center->barangay]] as [$id, $name, $label, $current])
                                <div class="lgh-field">
                                    <label for="{{ $id }}">{{ $label }} <span aria-hidden="true">*</span></label>
                                    <input id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $current) }}" maxlength="255" required
                                           :aria-invalid="errors.{{ $name }} ? 'true' : '{{ $err($name) }}'" @input="errors.{{ $name }} = ''" :class="{ 'has-error': errors.{{ $name }} || @js($errors->has($name)) }">
                                    <p class="lgh-err" x-show="errors.{{ $name }}" x-cloak x-text="errors.{{ $name }}" role="alert"></p>
                                    @error($name)<p class="lgh-err" x-show="!errors.{{ $name }}">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <div class="lgh-field">
                                <label for="officeZip">ZIP code</label>
                                <input id="officeZip" name="zip_code" value="{{ old('zip_code', $center->zip_code) }}" maxlength="20" inputmode="numeric" autocomplete="postal-code">
                                @error('zip_code')<p class="lgh-err">{{ $message }}</p>@enderror
                            </div>
                            <div class="lga-wide lga-map">
                                <p class="lgh-hint">Search the address or move the pin. The written address fills in for you, so check it before you save.</p>
                                <x-address-map :lat="$center->latitude" :lng="$center->longitude" :address="$officeAddress"
                                    title="Pin the company address" description="Search the address or move the pin. Review the filled address, then save to update the company profile." />
                                @error('latitude')<p class="lgh-err">{{ $message }}</p>@enderror
                                @error('longitude')<p class="lgh-err">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>
                @endif

                <div class="lga-save">
                    <button type="submit" class="lgh-btn lgh-btn--primary"><x-logistics.icon name="check" :size="16" /> Save changes</button>
                </div>
            </form>

            {{-- Right: password, and for the company the verification documents --}}
            <div class="lga-stack">
                <form method="POST" action="{{ route('lgp.'.$role.'.account.password') }}" class="lgh-card lga-panel" novalidate @submit="check($event, 'password')" aria-labelledby="passwordTitle">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_form" value="password">
                    <header class="lga-panel__head">
                        <span class="lga-tile lgh-tone-lilac"><x-logistics.icon name="shield" :size="20" /></span>
                        <div><h2 id="passwordTitle">Change password</h2><p>Use a password you don’t use anywhere else.</p></div>
                    </header>
                    <div class="lga-panel__body lga-fields lga-fields--one">
                        @foreach ([['current', 'currentPassword', 'current_password', 'Current password', 'current-password'], ['next', 'newPassword', 'password', 'New password', 'new-password'], ['again', 'confirmPassword', 'password_confirmation', 'Confirm new password', 'new-password']] as [$key, $id, $name, $label, $auto])
                            <div class="lgh-field">
                                <label for="{{ $id }}">{{ $label }} <span aria-hidden="true">*</span></label>
                                <div class="lga-pass">
                                    <input id="{{ $id }}" name="{{ $name }}" :type="show.{{ $key }} ? 'text' : 'password'" x-model="pw.{{ $key }}" autocomplete="{{ $auto }}" required
                                           @input="errors.{{ $name }} = ''" :aria-invalid="errors.{{ $name }} ? 'true' : '{{ $err($name) }}'" :class="{ 'has-error': errors.{{ $name }} || @js($errors->has($name)) }">
                                    <button type="button" class="lga-eye" @click="show.{{ $key }} = !show.{{ $key }}" :aria-label="(show.{{ $key }} ? 'Hide ' : 'Show ') + '{{ strtolower($label) }}'" :aria-pressed="show.{{ $key }}">
                                        <span x-show="!show.{{ $key }}"><x-logistics.icon name="eye" :size="18" /></span>
                                        <span x-show="show.{{ $key }}" x-cloak><x-logistics.icon name="eye-off" :size="18" /></span>
                                    </button>
                                </div>
                                <p class="lgh-err" x-show="errors.{{ $name }}" x-cloak x-text="errors.{{ $name }}" role="alert"></p>
                                @error($name)<p class="lgh-err" x-show="!errors.{{ $name }}">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <ul class="lga-rules" aria-label="Password rules">
                            <li :class="{ 'is-met': hasLen }">8 or more characters</li>
                            <li :class="{ 'is-met': hasLetter }">A letter</li>
                            <li :class="{ 'is-met': hasNum }">A number</li>
                            <li :class="{ 'is-met': matches }">Both passwords match</li>
                        </ul>
                        <div class="lga-save lga-save--inner">
                            <button type="submit" class="lgh-btn lgh-btn--primary">Update password</button>
                        </div>
                    </div>
                </form>

                @if ($isCompany)
                    <section class="lgh-card lga-panel" aria-labelledby="docsTitle">
                        <header class="lga-panel__head">
                            <span class="lga-tile lgh-tone-lilac"><x-logistics.icon name="file" :size="20" /></span>
                            <div><h2 id="docsTitle">Verification documents</h2><p>Replace your Valid ID or business permit. Admin reviews it before it replaces the current one.</p></div>
                        </header>
                        <form method="POST" action="{{ route('lgp.company.account.documents') }}" enctype="multipart/form-data" class="lga-panel__body lga-fields lga-fields--one" novalidate @submit="check($event, 'document')">
                            @csrf
                            <input type="hidden" name="_form" value="document">
                            <div class="lgh-field">
                                <label for="replacementType">Document <span aria-hidden="true">*</span></label>
                                <select id="replacementType" name="document_type" x-model="doc.type" @change="errors.document_type = ''" :aria-invalid="errors.document_type ? 'true' : '{{ $err('document_type') }}'" :class="{ 'has-error': errors.document_type }">
                                    <option value="">Choose a document</option>
                                    <option value="valid_id">Valid ID</option>
                                    <option value="business_permit">Business permit</option>
                                </select>
                                <p class="lgh-err" x-show="errors.document_type" x-cloak x-text="errors.document_type" role="alert"></p>
                                @error('document_type')<p class="lgh-err" x-show="!errors.document_type">{{ $message }}</p>@enderror
                            </div>
                            <div class="lgh-field">
                                <label for="replacementFile">New file <span aria-hidden="true">*</span></label>
                                <input id="replacementFile" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" @change="errors.document = ''" :aria-invalid="errors.document ? 'true' : '{{ $err('document') }}'" :class="{ 'has-error': errors.document }">
                                <p class="lgh-hint">PDF, JPG or PNG, up to 5 MB. Stored privately until Admin reviews it.</p>
                                <p class="lgh-err" x-show="errors.document" x-cloak x-text="errors.document" role="alert"></p>
                                @error('document')<p class="lgh-err" x-show="!errors.document">{{ $message }}</p>@enderror
                            </div>
                            <div class="lga-save lga-save--inner">
                                <button type="submit" class="lgh-btn lgh-btn--primary">Submit for review</button>
                            </div>
                        </form>

                        @if ($documentRequests->count())
                            <div class="lgh-table-wrap lga-docs">
                                <table class="lgh-table lga-table">
                                    <thead><tr><th scope="col">Document</th><th scope="col">Status</th><th scope="col">Submitted</th></tr></thead>
                                    <tbody>
                                        @foreach ($documentRequests as $item)
                                            <tr>
                                                <td>{{ ucfirst(str_replace('_', ' ', $item->document_type)) }}</td>
                                                <td data-label="Status"><span class="lgh-pill lgh-pill--{{ $docTone[$item->status] ?? 'lilac' }}">{{ $docLabel[$item->status] ?? ucfirst($item->status) }}</span></td>
                                                <td data-label="Submitted">{{ $item->created_at?->format('M j, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="lgh-empty lga-empty" role="status">
                                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="file" :size="26" /></span>
                                <strong>No replacement requests yet</strong>
                                <p>Documents you submit show here with their review status.</p>
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </div>

        {{-- Change email: needs your password; a link goes to the new address --}}
        <div class="lgh-layer" x-show="dialog === 'email'" x-cloak>
            <div class="lgh-backdrop" @click="dialog = null"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="emailTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="emailTitle">Change email</h2>
                        <p>We send a link to the new address. Your current email stays active until you open it.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="dialog = null" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form method="POST" action="{{ route('lgp.account.email.request') }}" class="lgh-dialog__body lgh-form" novalidate @submit="check($event, 'email')">
                    @csrf
                    <input type="hidden" name="_form" value="email">
                    <div class="lgh-field">
                        <label for="newEmail">New email <span aria-hidden="true">*</span></label>
                        <input id="newEmail" type="email" name="new_email" value="{{ old('new_email') }}" maxlength="255" autocomplete="email" required
                               @input="errors.new_email = ''" :aria-invalid="errors.new_email ? 'true' : '{{ $form === 'email' ? $err('new_email') : 'false' }}'" :class="{ 'has-error': errors.new_email || @js($form === 'email' && $errors->has('new_email')) }">
                        <p class="lgh-err" x-show="errors.new_email" x-cloak x-text="errors.new_email" role="alert"></p>
                        @if ($form === 'email')@error('new_email')<p class="lgh-err" x-show="!errors.new_email">{{ $message }}</p>@enderror @endif
                    </div>
                    <div class="lgh-field">
                        <label for="emailCurrentPassword">Current password <span aria-hidden="true">*</span></label>
                        <input id="emailCurrentPassword" type="password" name="current_password" autocomplete="current-password" required
                               @input="errors.current_password = ''" :aria-invalid="errors.current_password ? 'true' : '{{ $form === 'email' ? $err('current_password') : 'false' }}'" :class="{ 'has-error': errors.current_password || @js($form === 'email' && $errors->has('current_password')) }">
                        <p class="lgh-err" x-show="errors.current_password" x-cloak x-text="errors.current_password" role="alert"></p>
                        @if ($form === 'email')@error('current_password')<p class="lgh-err" x-show="!errors.current_password">{{ $message }}</p>@enderror @endif
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="dialog = null">Cancel</button>
                        <button type="submit" class="lgh-btn lgh-btn--primary">Send verification link</button>
                    </footer>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
