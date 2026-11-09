@php
    $detail = $seller->sellerDetail;
    $avatarUrl = $seller->profile_picture ? \Illuminate\Support\Facades\Storage::url($seller->profile_picture) : null;
    $bannerUrl = $detail?->shop_banner_path ? \Illuminate\Support\Facades\Storage::url($detail->shop_banner_path) : null;
    $initialTab = ($errors->has('current_password') || $errors->has('password')) ? 'security' : 'profile';
    $fieldError = fn ($key) => $errors->has($key) ? ' aria-invalid="true"' : '';
@endphp
<x-seller.layout title="Account Management">
    @vite(['resources/css/seller/delivered-orders.css', 'resources/css/seller/account.css', 'resources/css/seller/map-tailwind.css', 'resources/js/seller/account.js', 'resources/js/seller/map-alpine.js'])
    <section class="do-page ac-page" data-initial-tab="{{ $initialTab }}">
        <header class="do-head">
            <div>
                <h1>Account</h1>
                <p>Manage your shop profile, business details, and security.</p>
            </div>
        </header>

        @if(session('success'))<p hidden data-flash="{{ session('success') }}"></p>@endif
        @if($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.500v.01"/></svg>
                <div>@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
            </div>
        @endif

        <nav class="ac-tabs" role="tablist" aria-label="Account sections">
            <button type="button" role="tab" class="ac-tab" id="tab-profile" aria-controls="panel-profile" data-ac-tab="profile">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9l1.500-5h15L21 9M4 9v11h16V9M9 20v-6h6v6"/></svg>Shop profile
            </button>
            <button type="button" role="tab" class="ac-tab" id="tab-business" aria-controls="panel-business" data-ac-tab="business">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M3 13h18"/></svg>Business
            </button>
            <button type="button" role="tab" class="ac-tab" id="tab-policies" aria-controls="panel-policies" data-ac-tab="policies">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H6a1 1 0 00-1 1v16a1 1 0 001 1h12a1 1 0 001-1V8z"/><path d="M14 3v5h5M8 13h8M8 17h5"/></svg>Policies
            </button>
            <button type="button" role="tab" class="ac-tab" id="tab-security" aria-controls="panel-security" data-ac-tab="security">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>Security
            </button>
        </nav>

        {{-- ============ Shop profile ============ --}}
        <div role="tabpanel" id="panel-profile" aria-labelledby="tab-profile" data-ac-panel="profile">
            <section class="do-card ac-shop">
                <div class="ac-banner-area">
                    <div class="ac-banner" id="swBannerPreview" @if($bannerUrl) style="background-image:url('{{ $bannerUrl }}')" @endif></div>
                    <button type="button" class="ac-edit" data-menu="menuBanner" aria-haspopup="menu" aria-expanded="false" aria-controls="menuBanner">
                        <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.500"/></svg><span>Edit banner</span>
                    </button>
                    <div class="ac-menu ac-menu--right" id="menuBanner" role="menu" hidden>
                        <button type="button" role="menuitem" class="ac-menu__item" data-pick="banner">
                            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>Upload banner
                        </button>
                        @if($bannerUrl)
                            <button type="button" role="menuitem" class="ac-menu__item" data-adjust="banner">
                                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9 2 12l3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/></svg>Reposition banner
                            </button>
                            <button type="submit" form="removeBannerForm" role="menuitem" class="ac-menu__item ac-menu__item--danger">
                                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>Remove banner
                            </button>
                        @endif
                    </div>
                </div>

                <div class="ac-identity">
                    <div class="ac-avatar-wrap">
                        <button type="button" class="ac-avatar-btn" data-menu="menuAvatar" aria-haspopup="menu" aria-expanded="false" aria-controls="menuAvatar" aria-label="Edit profile photo">
                            @if($avatarUrl)<img id="swAvatarPreview" class="ac-avatar" src="{{ $avatarUrl }}" alt="">
                            @else<span id="swAvatarPreview" class="ac-avatar ac-avatar--fallback">{{ strtoupper(mb_substr($seller->name, 0, 1)) }}</span>@endif
                            <span class="ac-avatar-badge" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.500"/></svg></span>
                        </button>
                        <div class="ac-menu" id="menuAvatar" role="menu" hidden>
                            <button type="button" role="menuitem" class="ac-menu__item" data-pick="avatar">
                                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>Upload photo
                            </button>
                            @if($avatarUrl)
                                <button type="button" role="menuitem" class="ac-menu__item" data-adjust="avatar">
                                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9 2 12l3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/></svg>Reposition photo
                                </button>
                                <button type="submit" form="removeAvatarForm" role="menuitem" class="ac-menu__item ac-menu__item--danger">
                                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>Remove photo
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="ac-identity__text">
                        <h2>{{ $detail?->business_name ?? $seller->name }}</h2>
                        <p>{{ collect([$detail?->municipality, $detail?->province])->filter()->implode(', ') ?: 'No pickup location yet' }}</p>
                    </div>
                    <span class="do-badge {{ $seller->status === 'active' ? 'do-badge--mint' : 'do-badge--butter' }}">{{ ucfirst($seller->status) }} seller</span>
                </div>

                {{-- Upload forms. Submitted automatically after the crop is applied. Only shown if the file is rejected. --}}
                <form method="POST" action="{{ route('seller.account.avatar') }}" enctype="multipart/form-data" class="ac-pending" id="avatarForm" data-upload="avatar" data-max-mb="10" data-current="{{ $avatarUrl }}" hidden>
                    @csrf
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-upload-input hidden>
                    <p class="ac-err" data-upload-error role="alert" hidden></p>
                </form>
                <form method="POST" action="{{ route('seller.account.banner') }}" enctype="multipart/form-data" class="ac-pending" id="bannerForm" data-upload="banner" data-max-mb="10" data-current="{{ $bannerUrl }}" hidden>
                    @csrf
                    <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" data-upload-input hidden>
                    <p class="ac-err" data-upload-error role="alert" hidden></p>
                </form>
            </section>
            <form id="removeAvatarForm" method="POST" action="{{ route('seller.account.avatar.remove') }}" hidden>@csrf @method('DELETE')</form>
            <form id="removeBannerForm" method="POST" action="{{ route('seller.account.banner.remove') }}" hidden>@csrf @method('DELETE')</form>

            <form class="ac-form" method="POST" action="{{ route('seller.account.update') }}" data-draft-key="seller-{{ auth()->id() }}-account" data-ac-form>
                @csrf @method('PATCH')
                <section class="do-card ac-section">
                    <header class="do-card__head"><h2>Shop details</h2><p>Your description shows on your public shop profile. Your phone number stays private.</p></header>
                    <label class="ac-field">Contact number
                        <input type="tel" name="phone_number" inputmode="numeric" pattern="09[0-9]{9}" minlength="11" maxlength="11" placeholder="09171234567" value="{{ old('phone_number', $seller->phone_number) }}"{!! $fieldError('phone_number') !!}>
                        <small>11 digits, starting with 09</small>
                        @error('phone_number')<small class="ac-err">{{ $message }}</small>@enderror
                    </label>
                    <label class="ac-field">Shop description
                        <textarea name="shop_description" rows="5" maxlength="1000" placeholder="Tell buyers what you sell and what makes your shop different" data-count-field{!! $fieldError('shop_description') !!}>{{ old('shop_description', $detail?->shop_description) }}</textarea>
                        <span class="ac-counter"><span></span><span data-count>0 / 1000</span></span>
                        @error('shop_description')<small class="ac-err">{{ $message }}</small>@enderror
                    </label>
                </section>

                <section class="do-card ac-section">
                    <header class="do-card__head"><h2>Pickup address</h2><p>Couriers pick up your orders here. Search the address or move the pin on the map, then check the fields below.</p></header>
                    <div class="ac-grid">
                        <label class="ac-field">Province<input type="text" name="province" value="{{ old('province', $detail?->province) }}" required{!! $fieldError('province') !!}>@error('province')<small class="ac-err">{{ $message }}</small>@enderror</label>
                        <label class="ac-field">Municipality or city<input type="text" name="municipality" value="{{ old('municipality', $detail?->municipality) }}" required{!! $fieldError('municipality') !!}>@error('municipality')<small class="ac-err">{{ $message }}</small>@enderror</label>
                        <label class="ac-field">Barangay<input type="text" name="barangay" value="{{ old('barangay', $detail?->barangay) }}" required{!! $fieldError('barangay') !!}>@error('barangay')<small class="ac-err">{{ $message }}</small>@enderror</label>
                        <label class="ac-field">Street<input type="text" name="street" value="{{ old('street', $detail?->street) }}" maxlength="255" required{!! $fieldError('street') !!}>@error('street')<small class="ac-err">{{ $message }}</small>@enderror</label>
                        <label class="ac-field">ZIP code <em>(optional)</em><input type="text" name="zip_code" value="{{ old('zip_code', $detail?->zip_code) }}" maxlength="20"{!! $fieldError('zip_code') !!}>@error('zip_code')<small class="ac-err">{{ $message }}</small>@enderror</label>
                    </div>
                    <div class="ac-map">
                    <x-address-map :lat="$detail?->latitude" :lng="$detail?->longitude"
                        :address="trim(implode(', ', array_filter([$detail?->street, $detail?->barangay, $detail?->municipality, $detail?->province])))"
                        title="Pin your pickup address" description="Search the address or move the pin. The written address fills in for you." />
                    </div>
                    @error('latitude')<small class="ac-err">{{ $message }}</small>@enderror
                    @error('longitude')<small class="ac-err">{{ $message }}</small>@enderror
                </section>

                <div class="ac-savebar">
                    <span>Review the details above before you save.</span>
                    <button class="do-btn do-btn--primary" type="submit" data-ac-submit>
                        <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.500 10 17.500 19 7"/></svg>
                        Save changes
                    </button>
                </div>
            </form>
        </div>

        {{-- ============ Business ============ --}}
        <div role="tabpanel" id="panel-business" aria-labelledby="tab-business" data-ac-panel="business">
            <section class="do-card ac-section">
                <header class="do-card__head"><h2>Registered business</h2><p>Your legal details are locked. Ask Vendo Support if something is wrong.</p></header>
                <dl class="do-facts ac-facts">
                    <div><dt>Business name</dt><dd>{{ $detail?->business_name ?? '—' }}</dd></div>
                    <div><dt>Legal name</dt><dd>{{ $detail?->full_name ?? $seller->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $seller->email }}</dd></div>
                    <div><dt>Status</dt><dd>{{ ucfirst($seller->status) }} seller</dd></div>
                </dl>
                <a class="do-btn do-btn--soft" href="{{ route('seller.messages.index') }}">
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 01-11.600 7.100L4 20l1-4.600A8 8 0 1121 12z"/></svg>
                    Contact support
                </a>
            </section>
            @include('seller.account.partials.categories')
        </div>

        {{-- ============ Policies ============ --}}
        <div role="tabpanel" id="panel-policies" aria-labelledby="tab-policies" data-ac-panel="policies">
            <x-account-policies :policies="$policies" />
        </div>

        {{-- ============ Security ============ --}}
        <div role="tabpanel" id="panel-security" aria-labelledby="tab-security" data-ac-panel="security">
            <section class="do-card ac-section ac-security">
                <header class="do-card__head"><h2>Change password</h2><p>Pick a password you don't use anywhere else.</p></header>
                <form class="ac-form ac-form--narrow" method="POST" action="{{ route('seller.account.password') }}" data-ac-password>
                    @csrf @method('PATCH')
                    <label class="ac-field">Current password
                        <span class="ac-pass"><input type="password" name="current_password" autocomplete="current-password" required{!! $fieldError('current_password') !!}><button type="button" class="ac-eye" data-toggle-pass aria-label="Show password"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
                        @error('current_password')<small class="ac-err">{{ $message }}</small>@enderror
                    </label>
                    <label class="ac-field">New password
                        <span class="ac-pass"><input type="password" name="password" autocomplete="new-password" required data-new-pass{!! $fieldError('password') !!}><button type="button" class="ac-eye" data-toggle-pass aria-label="Show password"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
                    </label>
                    <ul class="ac-rules" aria-label="Password rules">
                        <li data-rule="length">8 or more characters</li>
                        <li data-rule="case">Upper and lower case letters</li>
                        <li data-rule="number">A number</li>
                        <li data-rule="symbol">A symbol, like ! or @</li>
                    </ul>
                    <label class="ac-field">Confirm new password
                        <span class="ac-pass"><input type="password" name="password_confirmation" autocomplete="new-password" required data-confirm-pass><button type="button" class="ac-eye" data-toggle-pass aria-label="Show password"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
                        <small class="ac-err" data-match-error hidden>Passwords do not match. Retype the confirmation.</small>
                    </label>
                    <button class="do-btn do-btn--primary" type="submit" data-ac-submit>
                        <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                        Update password
                    </button>
                </form>
            </section>
        </div>
    </section>
    {{-- Crop and reposition editor for the profile photo and banner --}}
    <dialog class="ac-crop" id="acCrop" aria-labelledby="acCropTitle">
        <header class="ac-crop__head">
            <h2 id="acCropTitle">Adjust photo</h2>
            <button type="button" class="cr-x" data-crop-cancel aria-label="Close editor">×</button>
        </header>
        <div class="ac-crop__body">
            <div class="ac-crop__stage" id="acCropStage" tabindex="0" role="application" aria-label="Image editor. Drag to move. Use the arrow keys to move and plus or minus to zoom.">
                <img id="acCropImg" alt="" draggable="false">
                <div class="ac-crop__mask" aria-hidden="true"></div>
            </div>
            <div class="ac-crop__zoom">
                <button type="button" class="ac-crop__step" data-zoom-out aria-label="Zoom out">−</button>
                <input type="range" id="acCropZoom" min="1" max="4" step="0.01" value="1" aria-label="Zoom">
                <button type="button" class="ac-crop__step" data-zoom-in aria-label="Zoom in">+</button>
            </div>
            <p class="ac-crop__hint">Drag to reposition. Scroll or use the slider to zoom. Saving updates your shop right away.</p>
        </div>
        <footer class="ac-crop__foot">
            <button type="button" class="do-btn do-btn--soft" data-crop-reset>Reset</button>
            <span class="ac-crop__spacer"></span>
            <button type="button" class="do-btn" data-crop-cancel>Cancel</button>
            <button type="button" class="do-btn do-btn--primary" data-crop-apply>
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7"/></svg>
                <span data-crop-apply-label>Save</span>
            </button>
        </footer>
    </dialog>
</x-seller.layout>
