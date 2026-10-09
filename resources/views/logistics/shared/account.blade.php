@php
    $isCompany = $role === 'company';
    $name = ['company' => 'JNT Express Philippines', 'province' => 'JNT Laguna Hub', 'station' => 'JNT Santa Cruz Station'][$role];
@endphp
<x-logistics.shell title="Account Management" :role="$role">
    <div class="lg-page" x-data="{ saved: false }">
        <div class="lg-page-head"><div><h1>Account Management</h1><p>{{ $isCompany ? 'Company details shown to Vendo and to your hubs.' : 'Hub details and your login.' }}</p></div></div>
        <section class="lg-card" aria-label="Details">
            <form @submit.prevent="saved = true" class="lg-card__body" style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                <div class="lg-field"><label for="a1">{{ $isCompany ? 'Company name' : 'Hub name' }}</label><input id="a1" class="lg-input" value="{{ $name }}"></div>
                <div class="lg-field"><label for="a2">Contact email</label><input id="a2" type="email" class="lg-input" value="contact@jnt-demo.ph"></div>
                <div class="lg-field"><label for="a3">Phone</label><input id="a3" class="lg-input" value="0917 123 4567"></div>
                @if ($isCompany)
                    <div class="lg-field"><label for="a4">Business permit no.</label><input id="a4" class="lg-input" value="BP-2026-004418"></div>
                @else
                    <div class="lg-field"><label for="a4">Address</label><input id="a4" class="lg-input" value="{{ $role === 'province' ? 'Santa Rosa, Laguna' : 'Santa Cruz, Laguna' }}"></div>
                    <div class="lg-field"><label for="a5">Company</label><input id="a5" class="lg-input" value="JNT Express Philippines" disabled></div>
                @endif
                <div style="grid-column:1/-1;display:flex;gap:12px;align-items:center">
                    <button type="submit" class="lg-btn">Save changes</button>
                    <span class="lg-pill lg-pill--green" x-show="saved" x-cloak>Saved (preview only)</span>
                </div>
            </form>
        </section>
        <section class="lg-card" aria-label="Password">
            <div class="lg-card__head"><h2 class="lg-section-title">Change password</h2></div>
            <form @submit.prevent class="lg-card__body" style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                <div class="lg-field"><label for="p1">Current password</label><input id="p1" type="password" class="lg-input"></div>
                <div class="lg-field"><label for="p2">New password</label><input id="p2" type="password" class="lg-input"></div>
                <div style="grid-column:1/-1"><button type="submit" class="lg-btn lg-btn--outline">Update password</button></div>
            </form>
        </section>
    </div>
</x-logistics.shell>