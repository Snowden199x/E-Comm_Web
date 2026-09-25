<x-seller.layout title="Account Management">
    @vite('resources/css/seller/workspace.css')
    <section class="sw-page">
        <header class="sw-heading"><div><h1>Account Management</h1><p>Keep your shop profile and contact information up to date.</p></div></header>
        @if(session('success'))<p class="sw-notice" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="sw-error" role="alert">{{ $errors->first() }}</p>@endif

        <section class="sw-card sw-shop-card">
            <div class="sw-shop-banner" id="swBannerPreview" @if($seller->sellerDetail?->shop_banner_path) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($seller->sellerDetail->shop_banner_path) }}')" @endif></div>
            <div class="sw-shop-card__identity">
                @if($seller->profile_picture)<img id="swAvatarPreview" class="sw-shop-avatar" src="{{ \Illuminate\Support\Facades\Storage::url($seller->profile_picture) }}" alt="Shop profile photo">
                @else<div id="swAvatarPreview" class="sw-shop-avatar sw-shop-avatar--fallback" aria-label="No profile photo">{{ strtoupper(substr($seller->name, 0, 1)) }}</div>@endif
                <div><h2>{{ $seller->sellerDetail?->business_name ?? $seller->name }}</h2><p>{{ $seller->sellerDetail?->municipality }}, {{ $seller->sellerDetail?->province }}</p><span class="sw-status">{{ ucfirst($seller->status) }} seller</span></div>
            </div>
            <div class="sw-shop-card__uploads">
                <form method="POST" action="{{ route('seller.account.avatar') }}" enctype="multipart/form-data">@csrf<label>Profile photo <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-preview="avatar" required></label><small>JPG, PNG or WebP · up to 2 MB</small><button class="sw-button sw-button--small" type="submit">Save photo</button></form>
                @if($seller->profile_picture)<form method="POST" action="{{ route('seller.account.avatar.remove') }}">@csrf @method('DELETE')<button class="sw-text-button" type="submit">Remove photo</button></form>@endif
                <form method="POST" action="{{ route('seller.account.banner') }}" enctype="multipart/form-data">@csrf<label>Shop banner <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" data-preview="banner" required></label><small>Wide image recommended · up to 4 MB</small><button class="sw-button sw-button--small" type="submit">Save banner</button></form>
                @if($seller->sellerDetail?->shop_banner_path)<form method="POST" action="{{ route('seller.account.banner.remove') }}">@csrf @method('DELETE')<button class="sw-text-button" type="submit">Remove banner</button></form>@endif
            </div>
        </section>

        <div class="sw-grid sw-grid--equal">
            <section class="sw-card"><h2>Shop and contact</h2><p class="sw-muted">Your description may appear on your public shop profile. Your phone number stays private.</p>
                <form class="sw-form" method="POST" action="{{ route('seller.account.update') }}">@csrf @method('PATCH')
                    <label>Contact number<input type="tel" name="phone_number" maxlength="20" value="{{ old('phone_number', $seller->phone_number) }}"></label>
                    <label>Shop description<textarea name="shop_description" rows="5" maxlength="1000" placeholder="Tell buyers about your shop">{{ old('shop_description', $seller->sellerDetail?->shop_description) }}</textarea></label>
                    <button class="sw-button" type="submit">Save changes</button>
                </form>
            </section>
            <section class="sw-card"><h2>Registered business</h2><p class="sw-muted">These approved details are read-only. For corrections, contact Vendo Support.</p>
                <dl class="sw-details"><div><dt>Business name</dt><dd>{{ $seller->sellerDetail?->business_name ?? '—' }}</dd></div><div><dt>Legal name</dt><dd>{{ $seller->sellerDetail?->full_name ?? $seller->name }}</dd></div><div><dt>Email</dt><dd>{{ $seller->email }}</dd></div><div><dt>Address</dt><dd>{{ collect([$seller->sellerDetail?->street, $seller->sellerDetail?->barangay, $seller->sellerDetail?->municipality, $seller->sellerDetail?->province, $seller->sellerDetail?->zip_code])->filter()->implode(', ') }}</dd></div><div><dt>Categories</dt><dd>{{ $seller->categories->pluck('name')->implode(', ') ?: '—' }}</dd></div></dl>
                <a class="sw-button sw-button--outline" href="{{ route('seller.messages.index') }}">Contact support</a>
            </section>
        </div>
        <section class="sw-card sw-security"><h2>Change password</h2><form class="sw-form" method="POST" action="{{ route('seller.account.password') }}">@csrf @method('PATCH')<label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New password<input type="password" name="password" autocomplete="new-password" required></label><label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" required></label><p class="sw-muted">Use at least 8 characters with upper and lower case letters, a number and a symbol.</p><button class="sw-button" type="submit">Update password</button></form></section>
    </section>
    <script>
    document.querySelectorAll('[data-preview]').forEach(input => input.addEventListener('change', () => {
        const file = input.files[0]; if (!file) return;
        const url = URL.createObjectURL(file);
        if (input.dataset.preview === 'banner') document.getElementById('swBannerPreview').style.backgroundImage = `url("${url}")`;
        else { const current = document.getElementById('swAvatarPreview'); if (current.tagName === 'IMG') current.src = url; else { current.textContent = ''; current.style.backgroundImage = `url("${url}")`; current.style.backgroundSize = 'cover'; } }
    }));
    </script>
</x-seller.layout>
