<x-logistics.shell title="Account Management" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Account Management</h1><p>Manage your {{ $role === 'company' ? 'company' : ($role === 'province' ? 'Province Hub' : 'Station Hub') }} login and contact details.</p></div>@if ($role === 'company')<div class="lg-page-head__actions"><a class="lg-btn lg-btn--outline" href="{{ route('lgp.company.managers') }}">Facility managers</a></div>@endif</div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note" role="alert">Please check the highlighted fields below.</div>@endif
        <section class="lg-card" aria-label="Account summary"><div class="lg-identity">
            <span class="lg-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div><p class="lg-identity__name">{{ $user->name }}</p><div class="lg-crumb-chips"><span class="lg-pill lg-pill--violet">{{ ucfirst($role) }}</span><span>{{ $company->business_name }}</span>@if ($facility)<span>{{ $facility->name }}</span>@endif</div></div>
        </div></section>
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));align-items:start">
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Profile</h2></div>
                <form method="POST" action="{{ route('lgp.'.$role.'.account.update') }}" class="lg-card__body lg-form-grid">
                    @csrf @method('PATCH')
                    <div class="lg-field"><label for="accountName">Name</label><input id="accountName" name="name" class="lg-input" value="{{ old('name', $user->name) }}" required maxlength="120">@error('name')<p class="lg-error">{{ $message }}</p>@enderror</div>
                    <div class="lg-field"><label for="accountPhone">Phone</label><input id="accountPhone" name="phone_number" class="lg-input" value="{{ old('phone_number', $user->phone_number) }}" required maxlength="24">@error('phone_number')<p class="lg-error">{{ $message }}</p>@enderror</div>
                    <div class="lg-field is-wide"><label for="accountEmail">Verified email</label><input id="accountEmail" class="lg-input" value="{{ $user->email }}" readonly></div>
                    @if ($role === 'company')
                        <div class="lg-field is-wide"><label for="businessName">Company name</label><input id="businessName" name="business_name" class="lg-input" value="{{ old('business_name', $company->business_name) }}" required maxlength="160">@error('business_name')<p class="lg-error">{{ $message }}</p>@enderror</div>
                        <div class="lg-field"><label for="officeNumber">Office number</label><input id="officeNumber" name="house_no" class="lg-input" value="{{ old('house_no', $center->house_no) }}" maxlength="80"></div>
                        <div class="lg-field"><label for="officeStreet">Office street</label><input id="officeStreet" name="street" class="lg-input" value="{{ old('street', $center->street) }}" maxlength="160"></div>
                        <div class="lg-field"><label for="officeProvince">Province</label><input id="officeProvince" name="province" class="lg-input" value="{{ old('province', $center->province) }}" required maxlength="255">@error('province')<p class="lg-error">{{ $message }}</p>@enderror</div>
                        <div class="lg-field"><label for="officeMunicipality">Municipality / city</label><input id="officeMunicipality" name="municipality" class="lg-input" value="{{ old('municipality', $center->municipality) }}" required maxlength="255">@error('municipality')<p class="lg-error">{{ $message }}</p>@enderror</div>
                        <div class="lg-field"><label for="officeBarangay">Barangay</label><input id="officeBarangay" name="barangay" class="lg-input" value="{{ old('barangay', $center->barangay) }}" required maxlength="255">@error('barangay')<p class="lg-error">{{ $message }}</p>@enderror</div>
                        <div class="lg-field"><label for="officeZip">ZIP code</label><input id="officeZip" name="zip_code" class="lg-input" value="{{ old('zip_code', $center->zip_code) }}" maxlength="20">@error('zip_code')<p class="lg-error">{{ $message }}</p>@enderror</div>
                        <div class="is-wide lg-address-map">
                            <p class="lg-section-sub">Search the company address or move the pin. Geoapify fills the written fields; confirm them before saving.</p>
                            <x-address-map :lat="$center->latitude" :lng="$center->longitude"
                                :address="trim(implode(', ', array_filter([$center->house_no, $center->street, $center->barangay, $center->municipality, $center->province])))"
                                title="Pin the company address" description="Search the address or move the pin. Review the filled address and save it to update the company profile." />
                            @error('latitude')<p class="lg-error">{{ $message }}</p>@enderror
                            @error('longitude')<p class="lg-error">{{ $message }}</p>@enderror
                        </div>
                        <p class="lg-section-sub is-wide">This updates the company office address only. Province Hubs and Station Hub facilities keep their own operating addresses; replacement verification documents can be submitted below.</p>
                    @else
                        <p class="lg-section-sub is-wide">Facility: {{ $facility->name }} · {{ $facility->address }}. Your parent network manages facility locations.</p>
                    @endif
                    <div class="is-wide"><button class="lg-btn" type="submit">Save profile</button></div>
                </form>
            </section>
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Change password</h2></div>
                <form method="POST" action="{{ route('lgp.'.$role.'.account.password') }}" class="lg-card__body lg-form-grid">
                    @csrf @method('PATCH')
                    <div class="lg-field is-wide"><label for="currentPassword">Current password</label><input id="currentPassword" type="password" name="current_password" class="lg-input" required autocomplete="current-password">@error('current_password')<p class="lg-error">{{ $message }}</p>@enderror</div>
                    <div class="lg-field"><label for="newPassword">New password</label><input id="newPassword" type="password" name="password" class="lg-input" required minlength="8" autocomplete="new-password">@error('password')<p class="lg-error">{{ $message }}</p>@enderror</div>
                    <div class="lg-field"><label for="confirmPassword">Confirm password</label><input id="confirmPassword" type="password" name="password_confirmation" class="lg-input" required minlength="8" autocomplete="new-password"></div>
                    <div class="is-wide"><button class="lg-btn" type="submit">Update password</button></div>
                </form>
            </section>
        </div>
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));align-items:start;margin-top:16px">
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Change email</h2></div>
                <form method="POST" action="{{ route('lgp.account.email.request') }}" class="lg-card__body lg-form-grid">@csrf
                    <div class="lg-field is-wide"><label for="newEmail">New email</label><input id="newEmail" type="email" name="new_email" class="lg-input" value="{{ old('new_email') }}" required autocomplete="email"></div>
                    <div class="lg-field is-wide"><label for="emailCurrentPassword">Current password</label><input id="emailCurrentPassword" type="password" name="current_password" class="lg-input" required autocomplete="current-password"></div>
                    @if ($pendingEmail)<p class="lg-section-sub is-wide">A link was sent to {{ $pendingEmail->new_email }}. It expires at {{ \Illuminate\Support\Carbon::parse($pendingEmail->expires_at)->format('M j, g:i A') }}. Sign in with your current email to confirm.</p>@endif
                    <div class="is-wide"><button type="submit" class="lg-btn">Send verification link</button></div>
                </form>
            </section>
            @if ($role === 'company')
                <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Verification documents</h2></div>
                    <form method="POST" action="{{ route('lgp.company.account.documents') }}" enctype="multipart/form-data" class="lg-card__body lg-form-grid">@csrf
                        <div class="lg-field"><label for="replacementType">Replace</label><select id="replacementType" name="document_type" class="lg-select" required><option value="">Choose document</option><option value="valid_id">Valid ID</option><option value="business_permit">Business permit</option></select></div>
                        <div class="lg-field"><label for="replacementFile">New file</label><input id="replacementFile" type="file" name="document" class="lg-input" accept=".pdf,.jpg,.jpeg,.png" required><p class="lg-section-sub">PDF, JPG or PNG, up to 5 MB. Stored privately until Admin review.</p></div>
                        <div class="is-wide"><button type="submit" class="lg-btn">Submit for review</button></div>
                    </form>
                    <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Document</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
                        @forelse ($documentRequests as $item)<tr><td>{{ ucfirst(str_replace('_', ' ', $item->document_type)) }}</td><td>{{ ucfirst($item->status) }}</td><td>{{ $item->created_at?->format('M j, Y') }}</td></tr>@empty<tr><td colspan="3">No replacement requests.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
            @endif
        </div>
    </div>
</x-logistics.shell>
