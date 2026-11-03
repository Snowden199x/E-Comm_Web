<x-admin.layout title="Account management">
    {{-- Table loading bar and row animation shared with Registrations and User Management (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $isSuper = (bool) $currentAdmin->is_super_admin;

        // After a failed form the page comes back with old input: reopen the popup that sent it.
        $sentFrom = old('form');
        $hasErrors = $errors->any();
        $reopen = fn (string $form) => $hasErrors && $sentFrom === $form;

        $startTab = $reopen('create') ? 'admin-accounts' : request('tab', 'my-account');
        $startTab = ($startTab === 'admin-accounts' && $isSuper) ? 'admin-accounts' : 'my-account';

        $initialStatus = request()->string('status', 'all')->toString();
        $initialStatus = in_array($initialStatus, ['all', 'active', 'suspended', 'archived'], true) ? $initialStatus : 'all';

        $statusOptions = [
            'all' => ['All status', 'users'],
            'active' => ['Active', 'check-circle'],
            'suspended' => ['Suspended', 'ban'],
            'archived' => ['Archived', 'archive'],
        ];

        $tabs = [['my-account', 'My Account', 'user']];
        if ($isSuper) {
            $tabs[] = ['admin-accounts', 'Admin Accounts', 'shield-check'];
        }

        // Shared look for this page.
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $card = 'rounded-2xl border border-vd-line bg-white p-5';
        $label = 'mb-1.5 block text-xs font-medium text-vd-muted';
        $input = 'block h-10 w-full rounded-lg border border-vd-line-strong bg-white px-3 text-sm text-vd-ink placeholder:text-vd-muted transition-colors duration-200 hover:border-vd-plum-soft focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20';
        // Errors and old input belong to the form that sent them (the popups share one error bag).
        $bad = fn (string $name, string $form) => $reopen($form) && ($errors->has($name) || ($name === 'email_username' && $errors->has('email')));
        $field = fn (string $name, string $form) => $input . ($bad($name, $form) ? ' !border-vd-danger-ink' : '');
        $msg = fn (string $name, string $form) => $reopen($form) ? ($errors->first($name) ?: ($name === 'email_username' ? $errors->first('email') : '')) : '';
        $val = fn (string $name, string $form, $default = '') => $reopen($form) ? old($name, $default) : $default;
        $primary = "inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-vd-plum px-5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover focus-visible:ring-offset-2 {$focus}";
        $secondary = "inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-vd-line-strong bg-white px-3.5 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95 {$focus}";
        $cancel = "inline-flex h-10 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-4 text-sm font-medium text-vd-ink transition-colors duration-200 hover:bg-vd-tint {$focus}";
    @endphp

    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        tab: @js($startTab),
        showCreate: @js($reopen('create')),
        editProfile: @js($reopen('profile')),
        changePass: @js($reopen('password')),

        // Admin list filters
        q: '',
        status: @js($initialStatus),
        loading: false,
        failed: false,
        timer: null,
        ctrl: null,
        tableUrl: @js(route('admin.account-management.table')),

        get filtered() { return this.q.trim() !== '' || this.status !== 'all'; },

        onSearch() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(), 300); },
        apply() { clearTimeout(this.timer); this.load(); },
        clearAll() { this.q = ''; this.status = 'all'; this.apply(); },

        async load() {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;

            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.status !== 'all') p.set('status', this.status);
            const qs = p.toString();

            try {
                const res = await fetch(this.tableUrl + (qs ? '?' + qs : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const html = await res.text();
                if (ctrl.signal.aborted) return;

                const region = this.$refs.adminsRegion;
                region.innerHTML = html;
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');
            } catch (e) {
                if (e.name !== 'AbortError') this.failed = true;
            } finally {
                if (this.ctrl === ctrl) this.loading = false;
            }
        },
    }" x-init="$watch('tab', value => {
        const url = new URL(window.location);
        url.searchParams.set('tab', value);
        window.history.replaceState({}, '', url);
    })">

        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Account Management</h1>
            <p class="mt-1 text-sm text-vd-muted">Manage your profile, security and admin accounts.</p>
        </div>

        @include('admin.account-management.partials.notice')
        @include('admin.account-management.partials.credentials-dialog')

        {{-- Tabs --}}
        @if (count($tabs) > 1)
            <nav aria-label="Account sections" class="mb-5 max-w-full overflow-x-auto thin-scroll">
                <div role="tablist" class="inline-flex min-w-max gap-1 rounded-2xl border border-vd-line-strong bg-white p-1.5">
                    @foreach ($tabs as [$key, $name, $icon])
                        <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-vd-plum-soft bg-vd-lavender text-vd-plum' : 'border-transparent text-vd-ink hover:bg-vd-tint hover:text-vd-plum'"
                            class="flex items-center gap-2 whitespace-nowrap rounded-xl border px-4 py-2 text-sm font-medium transition-colors duration-200 {{ $focus }}">
                            <x-admin.icon name="{{ $icon }}" class="h-[18px] w-[18px]" />
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
            </nav>
        @endif

        {{-- MY ACCOUNT --}}
        <div x-show="tab === 'my-account'" role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-2">

            {{-- Profile --}}
            <section class="{{ $card }}" aria-labelledby="profile-title">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="profile-title" class="font-display text-base font-semibold text-vd-ink">Profile</h2>
                        <p class="mt-0.5 text-[13px] text-vd-muted">Your name and contact details.</p>
                    </div>
                    <button type="button" @click="editProfile = true" aria-haspopup="dialog" class="{{ $secondary }}">
                        <x-admin.icon name="pencil" class="h-3.5 w-3.5" /> Edit profile
                    </button>
                </div>

                <div class="flex items-start gap-4">
                    <x-admin.avatar :user="$currentAdmin" size="h-16 w-16" text="text-xl" />

                    <dl class="grid min-w-0 flex-1 grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        @foreach ([
                            ['First name', $currentAdmin->first_name],
                            ['Last name', $currentAdmin->last_name],
                            ['Middle initial', $currentAdmin->middle_initial],
                            ['Contact number', $currentAdmin->phone_number],
                            ['Primary email', $currentAdmin->email],
                            ['Secondary email', $currentAdmin->recovery_email],
                        ] as [$name, $value])
                            <div class="min-w-0">
                                <dt class="text-xs text-vd-muted">{{ $name }}</dt>
                                <dd class="mt-0.5 break-words font-medium text-vd-ink">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="mt-5 flex flex-wrap gap-8 border-t border-vd-line pt-4">
                    <div>
                        <p class="mb-1.5 text-xs text-vd-muted">Role</p>
                        <x-admin.status-pill :tone="$isSuper ? 'plum' : 'neutral'">{{ $isSuper ? 'Super Admin' : 'Admin' }}</x-admin.status-pill>
                    </div>
                    <div>
                        <p class="mb-1.5 text-xs text-vd-muted">Status</p>
                        <x-admin.status-pill :tone="$currentAdmin->account_status === 'active' ? 'success' : 'danger'">{{ ucfirst($currentAdmin->account_status) }}</x-admin.status-pill>
                    </div>
                </div>
            </section>

            {{-- Sign-in and security --}}
            <section class="{{ $card }}" aria-labelledby="security-title">
                <h2 id="security-title" class="font-display text-base font-semibold text-vd-ink">Sign-in and security</h2>
                <p class="mt-0.5 text-[13px] text-vd-muted">Keep your account safe.</p>

                <div class="mt-5 flex items-center justify-between gap-3 rounded-xl border border-vd-line p-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white">
                            <x-admin.icon name="key" class="h-[18px] w-[18px]" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-vd-ink">Password</p>
                            <p class="text-xs text-vd-muted">Change it if you think someone else knows it.</p>
                        </div>
                    </div>
                    <button type="button" @click="changePass = true" aria-haspopup="dialog" class="{{ $primary }} !h-9 flex-shrink-0 !px-4 !text-[13px]">
                        Change password
                    </button>
                </div>

                <div class="mt-4 rounded-xl border border-vd-line">
                    <div class="flex items-center justify-between border-b border-vd-line px-4 py-3">
                        <p class="text-sm font-medium text-vd-ink">Sign-in history</p>
                        <span class="text-xs text-vd-muted">Latest 100</span>
                    </div>

                    @if ($loginSessions->isEmpty())
                        <x-admin.empty-state size="sm" icon="monitor" title="No sign-ins recorded" text="Your sign-ins appear here." />
                    @else
                        <ul class="thin-scroll max-h-[260px] divide-y divide-vd-line overflow-y-auto">
                            @foreach ($loginSessions as $session)
                                <li class="flex items-center gap-3 px-4 py-3 transition-colors duration-200 hover:bg-vd-tint">
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-vd-lavender text-vd-plum">
                                        <x-admin.icon name="monitor" class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-medium text-vd-ink">{{ $session->login_at->format('M d, Y \a\t g:i A') }}</p>
                                        <p class="truncate text-xs text-vd-muted">{{ $session->browser }} · {{ $session->device }} · IP {{ $session->ip_address ?? 'unknown' }}</p>
                                    </div>
                                    @if ($loop->first)
                                        <x-admin.status-pill tone="success" class="ml-auto flex-shrink-0">Latest</x-admin.status-pill>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>

        {{-- ADMIN ACCOUNTS --}}
        @if ($isSuper)
            <div x-show="tab === 'admin-accounts'" x-cloak role="tabpanel">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-base font-semibold text-vd-ink">Administrator accounts</h2>
                        <p class="mt-0.5 text-[13px] text-vd-muted">Admins who can sign in to the Vendo Admin Panel.</p>
                    </div>
                    <button type="button" @click="showCreate = true" aria-haspopup="dialog" class="{{ $primary }}">
                        <x-admin.icon name="plus" class="h-4 w-4" /> Create admin account
                    </button>
                </div>

                @include('admin.partials.filter-toolbar', [
                    'searchLabel' => 'Search admins',
                    'searchPlaceholder' => 'Search name or email…',
                    'hideDate' => true,
                    'dropdownModel' => 'status',
                    'dropdownOptions' => $statusOptions,
                    'dropdownAll' => 'All status',
                    'dropdownLabel' => 'Filter by status',
                ])

                {{-- The table could not refresh --}}
                <div x-show="failed" x-cloak role="alert"
                    class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
                    <span class="flex items-center gap-3">
                        <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                        Couldn't load admins. Check your connection, then try again.
                    </span>
                    <button type="button" @click="load()"
                        class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                        Try again
                    </button>
                </div>

                <div class="relative">
                    <div x-show="loading" x-cloak class="rg-bar" role="progressbar" aria-label="Loading admins"></div>
                    <div id="admins-table-wrap" x-ref="adminsRegion" class="rg-table-wrap" aria-live="polite" :aria-busy="loading">
                        @include('admin.account-management.partials.admins-table')
                    </div>
                </div>
            </div>

            {{-- Create admin --}}
            <x-admin.dialog model="showCreate" title="Create admin account" text="We generate a temporary password and show it once." size="lg">
                <form method="POST" action="{{ route('admin.account-management.store') }}" data-draft-key="admin-{{ auth('admin')->id() }}-create-admin" class="space-y-4">
                    @csrf
                    <input type="hidden" name="form" value="create">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="{{ $label }}">First name</label>
                            <input id="first_name" name="first_name" type="text" required autocomplete="off" value="{{ $val('first_name', 'create') }}" class="{{ $field('first_name', 'create') }}">
                            @if ($m = $msg('first_name', 'create')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                        </div>
                        <div>
                            <label for="last_name" class="{{ $label }}">Last name</label>
                            <input id="last_name" name="last_name" type="text" required autocomplete="off" value="{{ $val('last_name', 'create') }}" class="{{ $field('last_name', 'create') }}">
                            @if ($m = $msg('last_name', 'create')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                        </div>
                        <div>
                            <label for="middle_initial" class="{{ $label }}">Middle initial <span class="font-normal">(optional)</span></label>
                            <input id="middle_initial" name="middle_initial" type="text" maxlength="5" autocomplete="off" value="{{ $val('middle_initial', 'create') }}" class="{{ $field('middle_initial', 'create') }}">
                            @if ($m = $msg('middle_initial', 'create')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                        </div>
                        <div>
                            <label for="phone_number" class="{{ $label }}">Contact number <span class="font-normal">(optional)</span></label>
                            <input id="phone_number" name="phone_number" type="text" autocomplete="off" value="{{ $val('phone_number', 'create') }}" class="{{ $field('phone_number', 'create') }}">
                            @if ($m = $msg('phone_number', 'create')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                        </div>
                    </div>

                    <div>
                        <label for="email_username" class="{{ $label }}">Email address</label>
                        <div class="flex">
                            <input id="email_username" name="email_username" type="text" required autocomplete="off" placeholder="juan.delacruz" value="{{ $val('email_username', 'create') }}"
                                class="{{ $field('email_username', 'create') }} !rounded-r-none">
                            <span class="inline-flex items-center whitespace-nowrap rounded-r-lg border border-l-0 border-vd-line-strong bg-vd-tint px-3 text-sm text-vd-muted">@vendo-ph.app</span>
                        </div>
                        @if ($m = $msg('email_username', 'create')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                        <p class="mt-1.5 text-xs text-vd-muted">The admin sets their own password at first sign-in.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" @click="showCreate = false" class="{{ $cancel }}">Cancel</button>
                        <button type="submit" class="{{ $primary }}">Create account</button>
                    </div>
                </form>
            </x-admin.dialog>
        @endif

        {{-- Edit profile --}}
        <x-admin.dialog model="editProfile" title="Edit profile" text="Changes show in the top bar right away." size="lg">
            <form method="POST" action="{{ route('admin.account-management.profile.update') }}" enctype="multipart/form-data" x-target.push="main-content" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="profile">

                <div>
                    <label for="profile_picture" class="{{ $label }}">Profile picture</label>
                    <input id="profile_picture" name="profile_picture" type="file" accept="image/*"
                        class="block w-full text-sm text-vd-muted file:mr-3 file:h-9 file:cursor-pointer file:rounded-lg file:border file:border-vd-line-strong file:bg-white file:px-3 file:text-[13px] file:font-medium file:text-vd-plum file:transition-colors hover:file:bg-vd-tint">
                    <p class="mt-1.5 text-xs text-vd-muted">JPG or PNG, up to 2 MB.</p>
                    @if ($m = $msg('profile_picture', 'profile')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="edit_first_name" class="{{ $label }}">First name</label>
                        <input id="edit_first_name" name="first_name" type="text" required value="{{ $val('first_name', 'profile', $currentAdmin->first_name) }}" class="{{ $field('first_name', 'profile') }}">
                        @if ($m = $msg('first_name', 'profile')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                    </div>
                    <div>
                        <label for="edit_last_name" class="{{ $label }}">Last name</label>
                        <input id="edit_last_name" name="last_name" type="text" required value="{{ $val('last_name', 'profile', $currentAdmin->last_name) }}" class="{{ $field('last_name', 'profile') }}">
                        @if ($m = $msg('last_name', 'profile')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                    </div>
                    <div>
                        <label for="edit_middle_initial" class="{{ $label }}">Middle initial</label>
                        <input id="edit_middle_initial" name="middle_initial" type="text" maxlength="5" value="{{ $val('middle_initial', 'profile', $currentAdmin->middle_initial) }}" class="{{ $field('middle_initial', 'profile') }}">
                    </div>
                    <div>
                        <label for="edit_phone_number" class="{{ $label }}">Contact number</label>
                        <input id="edit_phone_number" name="phone_number" type="text" value="{{ $val('phone_number', 'profile', $currentAdmin->phone_number) }}" class="{{ $field('phone_number', 'profile') }}">
                    </div>
                </div>

                @unless ($isSuper)
                    <div>
                        <label for="edit_recovery_email" class="{{ $label }}">Secondary email <span class="font-normal">(for password recovery)</span></label>
                        <input id="edit_recovery_email" name="recovery_email" type="email" value="{{ $val('recovery_email', 'profile', $currentAdmin->recovery_email) }}" class="{{ $field('recovery_email', 'profile') }}">
                        @if ($m = $msg('recovery_email', 'profile')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                    </div>
                @endunless

                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" @click="editProfile = false" class="{{ $cancel }}">Cancel</button>
                    <button type="submit" class="{{ $primary }}">Save profile</button>
                </div>
            </form>
        </x-admin.dialog>

        {{-- Change password --}}
        <x-admin.dialog model="changePass" title="Change password" text="Use at least 8 characters." size="md">
            <form method="POST" action="{{ route('admin.account-management.profile.password') }}" x-target.push="main-content" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="password">

                @foreach ([['current_password', 'current_password', 'Current password', 'current-password'], ['new_password', 'password', 'New password', 'new-password'], ['new_password_confirmation', 'password_confirmation', 'Confirm new password', 'new-password']] as [$id, $name, $text, $auto])
                    <div x-data="{ show: false }">
                        <label for="{{ $id }}" class="{{ $label }}">{{ $text }}</label>
                        <div class="relative">
                            <input id="{{ $id }}" name="{{ $name }}" required autocomplete="{{ $auto }}" :type="show ? 'text' : 'password'" class="{{ $field($name, 'password') }} pr-16">
                            <button type="button" @click="show = !show" :aria-pressed="show" data-no-loading
                                class="absolute right-1.5 top-1/2 h-7 -translate-y-1/2 rounded-md px-2 text-xs font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">
                                <span x-text="show ? 'Hide' : 'Show'">Show</span>
                            </button>
                        </div>
                        @if ($m = $msg($name, 'password')) <p class="mt-1 text-xs text-vd-danger-ink">{{ $m }}</p> @endif
                    </div>
                @endforeach

                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" @click="changePass = false" class="{{ $cancel }}">Cancel</button>
                    <button type="submit" class="{{ $primary }}">Change password</button>
                </div>
            </form>
        </x-admin.dialog>
    </div>
</x-admin.layout>
