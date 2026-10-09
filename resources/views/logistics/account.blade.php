<x-logistics.layout title="Account Management">
    <div class="lg-page" style="max-width: 880px;">

        <div class="lg-page-head">
            <div>
                <h1>Account Management</h1>
                <p>Your registered details and platform policies.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="#accountPolicies" class="lg-btn lg-btn--outline">
                    <x-logistics.icon name="shield" :size="18" /> Policies
                </a>
            </div>
        </div>

        <section class="lg-card lg-profile" aria-labelledby="logisticsProfileTitle">
            <div class="lg-profile__banner" aria-hidden="true"></div>

            <div class="lg-profile__head">
                @if ($user->profile_picture)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_picture) }}" alt="" class="lg-profile__avatar">
                @else
                    <span class="lg-profile__avatar lg-profile__avatar--text" aria-hidden="true">{{ mb_strtoupper(mb_substr($center?->business_name ?: $user->name, 0, 1)) }}</span>
                @endif
                <div style="min-width: 0; padding-bottom: 4px;">
                    <h2 id="logisticsProfileTitle">{{ $center?->business_name ?: $user->name }}</h2>
                    <p class="lg-muted" style="margin-top: 2px;">Logistics center</p>
                </div>
                <span class="lg-pill lg-pill--green" style="margin-left: auto; margin-bottom: 6px;">{{ ucfirst($user->status) }}</span>
            </div>

            <div class="lg-profile__controls">
                <form method="POST" action="{{ route('logistics.account.avatar') }}" enctype="multipart/form-data" class="lg-profile__upload">
                    @csrf
                    <label for="logistics-avatar">Profile photo</label>
                    <input id="logistics-avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
                    <button type="submit" class="lg-btn lg-btn--outline lg-btn--sm">Upload photo</button>
                    @error('avatar')<p class="lg-profile__error" role="alert">{{ $message }}</p>@enderror
                </form>
                @if ($user->profile_picture)
                    <form method="POST" action="{{ route('logistics.account.avatar.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="lg-btn lg-btn--ghost lg-btn--sm">Remove photo</button>
                    </form>
                @endif
                <p class="lg-muted">JPG, PNG or WebP. Up to 2 MB.</p>
            </div>

            <dl class="lg-facts">
                <div>
                    <dt>Registered name</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div>
                    <dt>Contact number</dt>
                    <dd>{{ $user->phone_number ?: 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Account status</dt>
                    <dd>{{ ucfirst($user->status) }}</dd>
                </div>
                <div class="is-wide">
                    <dt>Registered address</dt>
                    <dd>{{ collect([$center?->house_no, $center?->street, $center?->barangay, $center?->municipality, $center?->province, $center?->zip_code])->filter()->implode(', ') ?: 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <x-account-policies :policies="$policies" />
    </div>
</x-logistics.layout>
