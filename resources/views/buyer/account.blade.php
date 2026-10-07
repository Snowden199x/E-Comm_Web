{{--
    Account Management + Settings (one page, two groups in the side menu).

    Data from BuyerAccountController@index (unchanged): $buyerDetail, $policies.
    Deep links: ?tab=profile | security (Account) and ?tab=settings (= preferences) | notifications | saved | privacy (Settings).
    Forms and field names are unchanged: account.update (name, phone_number, house_no, street, zip_code),
    account.password (current_password, password, password_confirmation), banner and profile-picture upload/remove.

    Saved items and preferences are stored with the signed-in Buyer account.
--}}
@php
    $user = auth()->user();
    $aliases = ['settings' => 'preferences', 'policies' => 'privacy'];
    $requested = $aliases[request('tab', 'profile')] ?? request('tab', 'profile');
    $tab = in_array($requested, ['profile', 'security', 'preferences', 'notifications', 'saved', 'privacy'], true) ? $requested : 'profile';
    if ($errors->has('current_password') || $errors->has('password') || session('password_success')) {
        $tab = 'security';
    } elseif ($errors->any()) {
        $tab = 'profile';
    }
    $memberSince = $user->created_at?->format('F Y');
    $avatar = $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('images/logo/vendo-icon.png');

    $groups = [
        'Account' => [
            'profile' => ['Profile', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 20c0-4 3.6-6 8-6s8 2 8 6'],
            'security' => ['Password and security', 'M6 11V8a6 6 0 1 1 12 0v3M5 11h14v9H5zM12 15v2'],
        ],
        'Settings' => [
            'preferences' => ['Appearance and motion', 'M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
            'notifications' => ['Notifications', 'M18 16v-5a6 6 0 1 0-12 0v5l-2 3h16zM10 21a2 2 0 0 0 4 0'],
            'saved' => ['Saved items', 'M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z'],
            'privacy' => ['Privacy and policies', 'M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6zM9 12l2 2 4-4'],
        ],
    ];

    $field = 'h-10 w-full rounded-md border-[#e5dce7] bg-white px-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:border-[#805487] focus:ring-[#805487]';
    $label = 'mb-1 block text-[12px] font-medium text-[#5b4a60]';
    $primary = 'inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-6 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]';
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
@endphp

<x-buyer.layout title="Account — Vendo">
    <div class="mx-auto max-w-[1040px] px-3 pb-10 pt-4 sm:px-4"
        x-data="{
            tab: @js($tab),
            prefs: window.vendoPrefs.get(),
            confirmReset: false,
            go(t) {
                this.tab = t;
                const url = new URL(location.href);
                url.searchParams.set('tab', t);
                history.replaceState(null, '', url);
            },
            async setPref(key, value) {
                try {
                    this.prefs = await window.vendoPrefs.set(key, value);
                    $store.ui.say('Settings saved');
                } catch (error) { $store.ui.say(error.message); }
            },
            async resetPrefs() {
                try {
                    this.prefs = await window.vendoPrefs.reset();
                    this.confirmReset = false;
                    $store.ui.say('Settings reset');
                } catch (error) { $store.ui.say(error.message); }
            },
        }">

        <div class="vb-reveal">
            <h1 class="text-[20px] font-semibold text-[#2b1730]" x-text="['profile','security'].includes(tab) ? 'Account Management' : 'Settings'">Account Management</h1>
            <p class="mt-0.5 text-[13px] text-[#7a6a7e]" x-text="['profile','security'].includes(tab) ? 'Your profile, delivery details and password.' : 'Make Vendo comfortable for you, and read the policies that apply to your account.'">Your profile, delivery details and password.</p>
        </div>

        @if (session('success'))
            <p class="mt-3 rounded-md border border-[#c9e5d3] bg-[#eaf5ee] px-3 py-2.5 text-[13px] text-[#2e6b46]" role="status">{{ session('success') }}</p>
        @endif

        <div class="mt-4 grid items-start gap-4 lg:grid-cols-[250px_1fr]">

            <!-- ===================== Side menu ===================== -->
            <aside class="vb-reveal lg:sticky lg:top-[132px]" style="--i: 1">
                <div class="{{ $card }} hidden items-center gap-3 p-4 lg:flex">
                    <img src="{{ $avatar }}" alt="" class="h-12 w-12 flex-shrink-0 rounded-full border border-[#eee6ef] bg-white object-cover">
                    <div class="min-w-0">
                        <p class="truncate text-[14px] font-semibold text-[#2b1730]">{{ $user->name }}</p>
                        <p class="truncate text-[12px] text-[#8a7a8e]">{{ $user->email }}</p>
                    </div>
                </div>

                <nav aria-label="Account sections" class="vb-no-scrollbar -mx-1 flex gap-1 overflow-x-auto px-1 lg:mx-0 lg:mt-3 lg:block lg:space-y-4 lg:overflow-visible lg:px-0">
                    @foreach ($groups as $groupName => $items)
                        <div class="flex flex-shrink-0 gap-1 lg:block lg:rounded-lg lg:border lg:border-[#eee6ef] lg:bg-white lg:p-2">
                            <p class="hidden px-3 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#9a8a9d] lg:block">{{ $groupName }}</p>
                            @foreach ($items as $key => [$itemLabel, $iconPath])
                                <button type="button" @click="go('{{ $key }}')" :aria-current="tab === '{{ $key }}' ? 'page' : null"
                                    :class="tab === '{{ $key }}' ? 'bg-[#402143] text-white' : 'bg-white text-[#3d2a42] hover:bg-[#f5ecf6] lg:bg-transparent'"
                                    class="flex h-10 flex-shrink-0 items-center gap-2.5 whitespace-nowrap rounded-md border border-[#eee6ef] px-3 text-[13px] font-medium transition-colors duration-200 ease-vendo lg:w-full lg:border-0">
                                    <svg class="h-[18px] w-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                                    {{ $itemLabel }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 space-y-4">

                <!-- ===================== Profile ===================== -->
                <section x-show="tab === 'profile'" @if ($tab !== 'profile') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="space-y-4" aria-labelledby="acc-profile-title">

                    <div class="{{ $card }} overflow-hidden" x-data="{ bannerMenu: false, avatarMenu: false }">
                        <form id="banner-form" action="{{ route('buyer.account.banner.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="banner" accept="image/*" x-ref="bannerInput" class="absolute h-px w-px overflow-hidden opacity-0" tabindex="-1" aria-label="Upload banner" @change="$el.form.submit()">
                        </form>

                        <div class="relative h-36 bg-[#ece4ed] sm:h-44"
                            style="{{ $buyerDetail?->banner_path ? 'background-image:url(' . asset('storage/' . $buyerDetail->banner_path) . ');background-size:cover;background-position:center;' : '' }}">
                            <div class="absolute bottom-3 right-3">
                                <button type="button" @click="bannerMenu = !bannerMenu" :aria-expanded="bannerMenu"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-md bg-white/95 px-3 text-[12px] font-medium text-[#402143] shadow-[0_6px_14px_-8px_rgba(43,23,48,0.6)] transition-colors duration-200 hover:bg-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3v12H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></svg>
                                    Edit banner
                                </button>
                                <div x-show="bannerMenu" x-cloak @click.outside="bannerMenu = false"
                                    x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute right-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-[#eee6ef] bg-white py-1 text-[13px] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                                    <button type="button" @click="$refs.bannerInput.click(); bannerMenu = false" class="block w-full px-4 py-2 text-left transition-colors duration-150 hover:bg-[#f7eff8]">Upload new</button>
                                    @if ($buyerDetail?->banner_path)
                                        <form action="{{ route('buyer.account.banner.remove') }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="block w-full px-4 py-2 text-left text-[#a32b43] transition-colors duration-150 hover:bg-[#fdf1f3]">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <form id="avatar-form" action="{{ route('buyer.account.profile-picture.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="profile_picture" accept="image/*" x-ref="avatarInput" class="absolute h-px w-px overflow-hidden opacity-0" tabindex="-1" aria-label="Upload profile picture" @change="$el.form.submit()">
                        </form>

                        <div class="flex flex-wrap items-end gap-4 px-5 pb-5">
                            <div class="relative -mt-10">
                                <img src="{{ $avatar }}" alt="" class="h-20 w-20 rounded-full border-4 border-white bg-white object-cover shadow-[0_8px_18px_-10px_rgba(43,23,48,0.6)]">
                                <button type="button" @click="avatarMenu = !avatarMenu" :aria-expanded="avatarMenu" aria-label="Change profile picture"
                                    class="absolute bottom-0 right-0 grid h-7 w-7 place-items-center rounded-full border-2 border-white bg-[#402143] text-white transition-colors duration-200 hover:bg-[#52245b]">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                </button>
                                <div x-show="avatarMenu" x-cloak @click.outside="avatarMenu = false"
                                    x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute left-0 top-full z-10 mt-1 w-40 overflow-hidden rounded-lg border border-[#eee6ef] bg-white py-1 text-[13px] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                                    <button type="button" @click="$refs.avatarInput.click(); avatarMenu = false" class="block w-full px-4 py-2 text-left transition-colors duration-150 hover:bg-[#f7eff8]">Upload new</button>
                                    @if ($user->profile_picture)
                                        <form action="{{ route('buyer.account.profile-picture.remove') }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="block w-full px-4 py-2 text-left text-[#a32b43] transition-colors duration-150 hover:bg-[#fdf1f3]">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <div class="min-w-0 pb-1">
                                <p class="truncate text-[17px] font-semibold text-[#2b1730]">{{ $user->name }}</p>
                                <p class="truncate text-[12px] text-[#7a6a7e]">{{ $user->email }}@if ($memberSince) &middot; Buyer since {{ $memberSince }}@endif</p>
                            </div>
                            <div class="ml-auto flex flex-wrap gap-2 pb-1">
                                <a href="{{ route('buyer.orders.index') }}" class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3.5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">My Orders</a>
                                <a href="{{ route('buyer.cart.index') }}" class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3.5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">My Cart</a>
                            </div>
                        </div>
                    </div>

                    <div class="{{ $card }} p-5">
                        <h2 id="acc-profile-title" class="text-[15px] font-semibold text-[#402143]">Profile information</h2>
                        <p class="mt-0.5 text-[12px] text-[#7a6a7e]">This is what sellers and riders use to reach you and deliver your orders.</p>

                        <form action="{{ route('buyer.account.update') }}" method="POST" class="mt-4 space-y-4" data-draft-key="buyer-{{ auth()->id() }}-profile">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="acc-name" class="{{ $label }}">Full name</label>
                                    <input id="acc-name" type="text" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" class="{{ $field }}">
                                    @error('name')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="acc-phone" class="{{ $label }}">Phone number</label>
                                    <input id="acc-phone" type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel" class="{{ $field }}">
                                    @error('phone_number')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="acc-email" class="{{ $label }}">Email</label>
                                <input id="acc-email" type="email" value="{{ $user->email }}" disabled class="{{ $field }} bg-[#f7f3f8] text-[#8a7a8e]">
                                <p class="mt-1 text-[12px] text-[#9a8a9d]">Your email is your sign-in and cannot be changed here.</p>
                            </div>

                            @if ($buyerDetail)
                                <div class="border-t border-[#f1e8f2] pt-4">
                                    <h3 class="text-[13px] font-semibold text-[#402143]">Delivery address</h3>
                                    <div class="mt-3 grid gap-4 sm:grid-cols-[120px_1fr_140px]">
                                        <div>
                                            <label for="acc-house" class="{{ $label }}">House no.</label>
                                            <input id="acc-house" type="text" name="house_no" value="{{ old('house_no', $buyerDetail->house_no) }}" class="{{ $field }}">
                                            @error('house_no')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-street" class="{{ $label }}">Street</label>
                                            <input id="acc-street" type="text" name="street" value="{{ old('street', $buyerDetail->street) }}" autocomplete="address-line1" class="{{ $field }}">
                                            @error('street')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-zip" class="{{ $label }}">ZIP code</label>
                                            <input id="acc-zip" type="text" name="zip_code" value="{{ old('zip_code', $buyerDetail->zip_code) }}" autocomplete="postal-code" class="{{ $field }}">
                                            @error('zip_code')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <dl class="mt-4 grid gap-3 rounded-md bg-[#faf6fb] p-3.5 text-[13px] sm:grid-cols-3">
                                        <div><dt class="text-[11px] font-medium uppercase tracking-wide text-[#9a8a9d]">Barangay</dt><dd class="mt-0.5 text-[#2b1730]">{{ $buyerDetail->barangay ?: 'Not set' }}</dd></div>
                                        <div><dt class="text-[11px] font-medium uppercase tracking-wide text-[#9a8a9d]">Municipality</dt><dd class="mt-0.5 text-[#2b1730]">{{ $buyerDetail->municipality ?: 'Not set' }}</dd></div>
                                        <div><dt class="text-[11px] font-medium uppercase tracking-wide text-[#9a8a9d]">Province</dt><dd class="mt-0.5 text-[#2b1730]">{{ $buyerDetail->province ?: 'Not set' }}</dd></div>
                                    </dl>
                                    <p class="mt-2 text-[12px] text-[#9a8a9d]">Barangay, municipality and province come from your registration and are used to route your parcels.</p>
                                </div>
                            @endif

                            <div class="flex justify-end border-t border-[#f1e8f2] pt-4">
                                <button type="submit" class="{{ $primary }}">Save changes</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- ===================== Password and security ===================== -->
                <section x-show="tab === 'security'" @if ($tab !== 'security') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }} p-5" aria-labelledby="acc-security-title">
                    <h2 id="acc-security-title" class="text-[15px] font-semibold text-[#402143]">Change password</h2>
                    <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Use a password you do not use on other sites.</p>

                    @if (session('password_success'))
                        <p class="mt-3 rounded-md border border-[#c9e5d3] bg-[#eaf5ee] px-3 py-2.5 text-[13px] text-[#2e6b46]" role="status">{{ session('password_success') }}</p>
                    @endif

                    <form action="{{ route('buyer.account.password') }}" method="POST" class="mt-4 max-w-[420px] space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="acc-current" class="{{ $label }}">Current password</label>
                            <input id="acc-current" type="password" name="current_password" autocomplete="current-password" class="{{ $field }}">
                            @error('current_password')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="acc-new" class="{{ $label }}">New password</label>
                            <input id="acc-new" type="password" name="password" autocomplete="new-password" class="{{ $field }}">
                            @error('password')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="acc-confirm" class="{{ $label }}">Confirm new password</label>
                            <input id="acc-confirm" type="password" name="password_confirmation" autocomplete="new-password" class="{{ $field }}">
                            @error('password_confirmation')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="{{ $primary }}">Update password</button>
                    </form>
                </section>

                <!-- ===================== Settings: appearance and motion ===================== -->
                <section x-show="tab === 'preferences'" @if ($tab !== 'preferences') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-pref-title">
                    <div class="border-b border-[#f1e8f2] p-5">
                        <h2 id="acc-pref-title" class="text-[15px] font-semibold text-[#402143]">Appearance and motion</h2>
                        <p class="mt-0.5 text-[12px] text-[#7a6a7e]">These preferences follow your Vendo account.</p>
                    </div>

                    <div class="divide-y divide-[#f3ecf4]">
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p class="text-[13px] font-medium text-[#2b1730]">Text size</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Make the storefront larger if text is hard to read.</p>
                            </div>
                            <div class="flex rounded-md border border-[#e5dce7] bg-white p-0.5" role="group" aria-label="Text size">
                                @foreach (['default' => 'Default', 'lg' => 'Large', 'xl' => 'Extra large'] as $size => $sizeLabel)
                                    <button type="button" @click="setPref('text', '{{ $size }}')" :aria-pressed="prefs.text === '{{ $size }}'"
                                        :class="prefs.text === '{{ $size }}' ? 'bg-[#402143] text-white' : 'text-[#5b4a60] hover:bg-[#f5ecf6]'"
                                        class="h-8 rounded px-3 text-[12px] font-medium transition-colors duration-200">{{ $sizeLabel }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p id="pref-calm" class="text-[13px] font-medium text-[#2b1730]">Reduce animations</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Turns off the entrance movement, fading and the carousel's automatic movement. Vendo also follows your device's reduce-motion setting.</p>
                            </div>
                            <button type="button" role="switch" :aria-checked="prefs.calm" aria-labelledby="pref-calm" @click="setPref('calm', !prefs.calm)"
                                :class="prefs.calm ? 'bg-[#805487]' : 'bg-[#d9cfdc]'" class="relative h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-300 ease-vendo">
                                <span :class="prefs.calm ? 'translate-x-5' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 ease-vendo"></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#f1e8f2] px-5 py-4">
                        <p class="text-[12px] text-[#9a8a9d]">Reset text size, animations and notification sound to their defaults.</p>
                        <button type="button" @click="if (confirmReset) resetPrefs(); else { confirmReset = true; setTimeout(() => confirmReset = false, 3000); }"
                            :class="confirmReset ? 'bg-[#a32b43] text-white' : 'border border-[#e5dce7] text-[#3d2a42] hover:bg-[#faf5fa]'"
                            class="inline-flex h-9 items-center rounded-md px-4 text-[13px] font-medium transition-colors duration-200"
                            x-text="confirmReset ? 'Click again to reset' : 'Reset to defaults'"></button>
                    </div>
                </section>

                <!-- ===================== Settings: notifications ===================== -->
                <section x-show="tab === 'notifications'" @if ($tab !== 'notifications') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-notif-title">
                    <div class="border-b border-[#f1e8f2] p-5">
                        <h2 id="acc-notif-title" class="text-[15px] font-semibold text-[#402143]">Notifications</h2>
                        <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Order updates and seller replies appear in the bell at the top of every page and on the Notifications page.</p>
                    </div>
                    <div class="divide-y divide-[#f3ecf4]">
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p id="pref-sound" class="text-[13px] font-medium text-[#2b1730]">Notification sound</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Play a short chime when a new notification arrives while you are on Vendo.</p>
                            </div>
                            <button type="button" role="switch" :aria-checked="prefs.sound" aria-labelledby="pref-sound" @click="setPref('sound', !prefs.sound)"
                                :class="prefs.sound ? 'bg-[#805487]' : 'bg-[#d9cfdc]'" class="relative h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-300 ease-vendo">
                                <span :class="prefs.sound ? 'translate-x-5' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 ease-vendo"></span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <p class="text-[12px] leading-5 text-[#7a6a7e]">Choosing which updates to receive, and email notifications, are not available yet.</p>
                            <a href="{{ route('buyer.notifications.index') }}" class="inline-flex h-9 items-center rounded-md border border-[#805487] px-4 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Open notifications</a>
                        </div>
                    </div>
                </section>

                <!-- ===================== Settings: saved items ===================== -->
                <section x-show="tab === 'saved'" @if ($tab !== 'saved') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-saved-title"
                    x-data="{ confirmClear: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[#f1e8f2] p-5">
                        <div>
                            <h2 id="acc-saved-title" class="text-[15px] font-semibold text-[#402143]">Saved items</h2>
                            <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Products you saved with the heart, so you can find them again. Saving does not add to your cart. They follow your Vendo account.</p>
                        </div>
                        <button type="button" x-show="$store.fav.items.length" x-cloak
                            @click="if (confirmClear) { $store.fav.clear(); confirmClear = false; } else { confirmClear = true; setTimeout(() => confirmClear = false, 3000); }"
                            :class="confirmClear ? 'bg-[#a32b43] text-white' : 'border border-[#e5dce7] text-[#a32b43] hover:bg-[#fdf1f3]'"
                            class="inline-flex h-9 items-center rounded-md px-4 text-[13px] font-medium transition-colors duration-200"
                            x-text="confirmClear ? 'Click again to clear all' : 'Clear all'"></button>
                    </div>

                    <div x-show="!$store.fav.items.length" class="px-5 py-10 text-center">
                        <p class="text-[14px] font-semibold text-[#2b1730]">No saved items yet</p>
                        <p class="mx-auto mt-1 max-w-[320px] text-[13px] leading-5 text-[#7a6a7e]">Tap the heart on any product to keep it here.</p>
                        <a href="{{ route('buyer.products.index') }}" class="{{ $primary }} mt-4">Browse products</a>
                    </div>

                    <ul x-show="$store.fav.items.length" class="divide-y divide-[#f3ecf4]">
                        <template x-for="item in $store.fav.items" :key="item.id">
                            <li class="flex items-center gap-3 px-5 py-3">
                                <a :href="$store.fav.href(item)" class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-md border border-[#eee6ef] bg-[#faf7fb]" tabindex="-1" aria-hidden="true">
                                    <img :src="item.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                </a>
                                <div class="min-w-0 flex-1">
                                    <a :href="$store.fav.href(item)" x-text="item.name" class="line-clamp-1 text-[13px] text-[#2b1730] transition-colors duration-200 hover:text-[#805487]"></a>
                                    <p class="text-[12px] text-[#8a7a8e]"><span x-text="item.price" class="font-semibold text-[#52245b]"></span><span x-show="item.shop"> &middot; <span x-text="item.shop"></span></span></p>
                                </div>
                                <a :href="$store.fav.href(item)" class="hidden h-8 items-center rounded-md border border-[#805487] px-3 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6] sm:inline-flex">View</a>
                                <button type="button" @click="$store.fav.remove(item.id)" :aria-label="'Remove ' + item.name + ' from saved items'"
                                    class="grid h-8 w-8 place-items-center rounded-md text-[#8a7a8e] transition-colors duration-200 hover:bg-[#fdf1f3] hover:text-[#a32b43]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3" /></svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </section>

                <!-- ===================== Settings: privacy and policies ===================== -->
                <section x-show="tab === 'privacy'" @if ($tab !== 'privacy') x-cloak @endif
                    x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    aria-label="Privacy and policies">
                    <div class="{{ $card }} flex flex-wrap items-center justify-between gap-3 p-5">
                        <div class="max-w-[520px]">
                            <h2 class="text-[15px] font-semibold text-[#402143]">Terms and privacy</h2>
                            <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">The public Terms and Conditions and Privacy Policy that apply to every Vendo account.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('legal.terms') }}" class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-4 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Terms and Conditions</a>
                            <a href="{{ route('legal.privacy') }}" class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-4 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Privacy Policy</a>
                        </div>
                    </div>

                    {{-- Policies published by the admin for buyers (opens in a dialog) --}}
                    <x-account-policies :policies="$policies" />
                </section>
            </div>
        </div>
    </div>
</x-buyer.layout>
