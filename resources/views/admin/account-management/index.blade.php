<x-admin.layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ tab: '{{ $errors->any() && old('email_username') !== null ? 'admin-accounts' : request('tab', 'my-account') }}', showCreateModal: {{ $errors->any() && old('email_username') !== null ? 'true' : 'false' }}, editProfile: false, changePass: false, actionId: null }" x-init="$watch('tab', value => {
        const url = new URL(window.location);
        url.searchParams.set('tab', value);
        window.history.replaceState({}, '', url);
    })">

        @if (session('generated_password'))
            <div x-data="{ show: true }" x-show="show" x-cloak
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">

                <div class="bg-white rounded-2xl p-6 w-full max-w-md">

                    <h3 class="font-bold text-lg text-gray-900 mb-2">
                        Admin Credentials
                    </h3>

                    <p class="text-sm text-gray-500 mb-4">
                        Copy these now and give to the admin. This is shown only once
                        unless viewed again from their profile before they set their own password.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-2 mb-4">
                        <p class="text-sm text-gray-700">
                            Email:
                            <span class="font-mono">{{ session('generated_email') }}</span>
                        </p>

                        <p class="text-sm text-gray-700">
                            Temporary Password:
                            <span class="font-mono font-bold">{{ session('generated_password') }}</span>
                        </p>
                    </div>

                    <button type="button" @click="show = false"
                        class="w-full bg-[#3b1735] text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-[#4d1f45]">
                        Got it, close
                    </button>

                </div>
            </div>
        @endif

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                Account Management
            </h2>

            <p class="text-gray-500">
                Manage your administrator profile, security settings, and authorized admin accounts.
            </p>
        </div>

        @if (session('confirmation'))
            <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 5000)"
                x-transition:enter="transform ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="fixed top-5 right-5 z-[9999] w-[360px] bg-white border border-green-200 shadow-xl rounded-xl p-4">

                @php
                    $messages = [
                        'created' => 'Admin account created. An invite has been sent to their email.',
                        'admin-updated' => 'Admin information updated.',
                        'suspended' => 'Admin account suspended.',
                        'reactivated' => 'Admin account reactivated.',
                        'deactivated' => 'Admin account deactivated.',
                        'reset-link-sent' => 'Password reset link sent to the admin.',
                        'profile-updated' => 'Profile updated successfully.',
                        'password-updated' => 'Password updated successfully.',
                        'admin-deleted' => 'Admin account deleted successfully.',
                        'admin-archived' => 'Admin account archived.',
                        'admin-restored' => 'Admin account restored.',
                    ];
                @endphp

                <div class="flex items-start gap-3">

                    <!-- Success Icon -->
                    <div class="flex-shrink-0 w-9 h-9 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-green-600 text-lg font-bold">
                            ✓
                        </span>
                    </div>

                    <!-- Message -->
                    <div class="flex-1 pt-0.5">

                        <p class="text-sm font-semibold text-gray-900">
                            Success
                        </p>

                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $messages[session('confirmation')] ?? 'Done.' }}
                        </p>

                    </div>

                    <!-- Close -->
                    <button type="button" @click="show = false"
                        class="text-gray-400 hover:text-gray-600 text-lg leading-none">
                        &times;
                    </button>

                </div>

            </div>
        @endif

        <!-- Tabs -->
        <div class="flex items-center gap-2 mb-6 bg-white rounded-xl p-1 w-fit shadow-sm">

            <button type="button" @click="tab = 'my-account'"
                :class="tab === 'my-account' ? 'bg-[#3b1735] text-white' : 'text-gray-600'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
                My Account
            </button>

            @if ($currentAdmin->is_super_admin)
                <button type="button" @click="tab = 'admin-accounts'"
                    :class="tab === 'admin-accounts' ? 'bg-[#3b1735] text-white' : 'text-gray-600'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Admin Accounts
                </button>
            @endif

        </div>

        <!-- MY ACCOUNT TAB -->
        <div x-show="tab === 'my-account'">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- Profile Information -->
                <div class="bg-white rounded-2xl p-5 shadow-sm">

                    <div class="flex items-center justify-between mb-4">

                        <div>
                            <h3 class="font-bold text-gray-900">
                                Profile Information
                            </h3>

                            <p class="text-sm text-gray-500">
                                View and manage your personal information
                            </p>
                        </div>

                        <button type="button" @click="editProfile = true"
                            class="text-sm font-medium text-[#3b1735] border border-[#3b1735] px-3 py-1.5 rounded-lg hover:bg-[#3b1735] hover:text-white transition">
                            Edit Profile
                        </button>

                    </div>

                    <div class="flex items-start gap-4">

                        <div
                            class="w-16 h-16 rounded-full bg-[#3b1735] text-white flex items-center justify-center text-xl font-bold overflow-hidden flex-shrink-0">

                            @if ($currentAdmin->profile_picture)
                                <img src="{{ asset('storage/' . $currentAdmin->profile_picture) }}"
                                    class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($currentAdmin->first_name ?? $currentAdmin->name, 0, 1)) }}{{ strtoupper(substr($currentAdmin->last_name ?? '', 0, 1)) }}
                            @endif

                        </div>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm flex-1">

                            <div>
                                <p class="text-gray-400">First Name</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->first_name ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Last Name</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->last_name ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Middle Initial</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->middle_initial ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Contact Number</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->phone_number ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Primary Email</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->email }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Secondary Email</p>
                                <p class="font-medium text-gray-900">
                                    {{ $currentAdmin->recovery_email ?? '—' }}
                                </p>
                            </div>

                        </div>
                    </div>

                    <div class="flex gap-6 mt-4 pt-4 border-t border-gray-100">

                        <div>
                            <p class="text-xs text-gray-400 mb-1">
                                Role
                            </p>

                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                {{ $currentAdmin->is_super_admin ? 'Super Admin' : 'Admin' }}
                            </span>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400 mb-1">
                                Status
                            </p>

                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                {{ ucfirst($currentAdmin->account_status) }}
                            </span>
                        </div>

                    </div>

                </div>

                <!-- Login & Security -->
                <div class="bg-white rounded-2xl p-5 shadow-sm">

                    <h3 class="font-bold text-gray-900 mb-4">
                        Login & Security
                    </h3>

                    <div class="flex items-center justify-between p-3 border border-gray-100 rounded-lg mb-3">

                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                Change Password
                            </p>

                            <p class="text-xs text-gray-500">
                                Update your password to keep your account secure
                            </p>
                        </div>

                        <button type="button" @click="changePass = true"
                            class="text-sm font-medium text-white bg-[#3b1735] px-3 py-1.5 rounded-lg hover:bg-[#4d1f45]">
                            Change
                        </button>

                    </div>

                    <!-- Login Session History -->
                    <div class="mt-3 p-3 border border-gray-100 rounded-lg">

                        <div class="flex items-center justify-between mb-3">

                            <p class="text-sm font-medium text-gray-900">
                                Login Session History
                            </p>

                            <span class="text-xs text-gray-400">
                                Latest 100
                            </span>

                        </div>

                        <div class="h-[180px] overflow-y-auto space-y-2 pr-1">

                            @forelse ($loginSessions as $session)
                                <div class="p-3 bg-gray-50 rounded-lg">

                                    <div class="flex items-start justify-between gap-3">

                                        <div>

                                            <p class="text-xs font-medium text-gray-900">
                                                {{ $session->login_at->format('M d, Y \a\t g:i A') }}
                                            </p>

                                            <p class="text-xs text-gray-500 mt-1">
                                                IP: {{ $session->ip_address ?? 'Unknown' }}
                                            </p>

                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $session->browser }} · {{ $session->device }}
                                            </p>

                                        </div>

                                        <span class="w-2 h-2 mt-1.5 rounded-full bg-green-500 flex-shrink-0"></span>

                                    </div>

                                </div>

                            @empty

                                <p class="text-xs text-gray-500 text-center py-3">
                                    No login sessions recorded yet.
                                </p>
                            @endforelse

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ADMIN ACCOUNTS TAB -->
        @if ($currentAdmin->is_super_admin)
            <div x-show="tab === 'admin-accounts'" x-cloak>

                <div class="bg-white rounded-2xl p-5 shadow-sm">

                    <div class="flex items-center justify-between mb-4">

                        <div>
                            <h3 class="font-bold text-gray-900">
                                Administrator Accounts
                            </h3>

                            <p class="text-sm text-gray-500">
                                Manage authorized administrators who can access the Vendo Admin Panel.
                            </p>
                        </div>

                        <button type="button" @click="showCreateModal = true"
                            class="bg-[#3b1735] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#4d1f45]">
                            + Create Admin Account
                        </button>

                    </div>

                    <div x-data="{
                        q: '',
                        statusVal: '{{ request('status', 'all') }}',
                        timer: null,
                        modalOpen: false,
                        abortController: null,
                    
                        fetchTable() {
                            if (this.abortController) {
                                this.abortController.abort();
                            }
                            this.abortController = new AbortController();
                    
                            const params = new URLSearchParams({ search: this.q, status: this.statusVal });
                            fetch('{{ route('admin.account-management.table') }}?' + params, { signal: this.abortController.signal })
                                .then(r => r.text())
                                .then(html => {
                                    const el = document.getElementById('admins-table-wrap');
                                    el.innerHTML = html;
                                    window.Alpine.initTree(el);
                                })
                                .catch(err => { if (err.name !== 'AbortError') console.error(err); });
                        },
                    
                        search() {
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => this.fetchTable(), 250);
                        },
                    
                        init() {}
                    }" x-on:modal-toggle.window="modalOpen = $event.detail">
                        <div class="flex flex-wrap gap-3 mb-4">

                            <input type="text" x-model="q" @input="search" autocomplete="off"
                                placeholder="Search administrator..."
                                class="w-64 rounded-lg border border-gray-200 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30">

                            <select x-model="statusVal" @change="search"
                                class="w-40 rounded-lg border border-gray-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30 focus:border-[#3b1735]">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>

                            <button type="button"
                                @click="statusVal = (statusVal === 'archived' ? 'all' : 'archived'); search()"
                                :class="statusVal === 'archived' ? 'bg-[#3b1735] text-white border-[#3b1735]' :
                                    'border-gray-200 text-gray-700'"
                                class="flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium whitespace-nowrap">
                                📁 Archived
                            </button>

                        </div>

                        <div id="admins-table-wrap">
                            @include('admin.account-management.partials.admins-table')
                        </div>

                    </div>

                </div>

            </div>

            <!-- Create Admin Modal -->
            <div x-show="showCreateModal" x-cloak
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                @click.self="showCreateModal = false">

                <div class="bg-white rounded-2xl p-6 w-full max-w-lg" @click.stop>

                    <div class="flex items-center justify-between mb-4">

                        <h3 class="font-bold text-lg text-gray-900">
                            Create Administrator
                        </h3>

                        <button type="button" @click="showCreateModal = false"
                            class="text-gray-400 hover:text-gray-600 text-xl leading-none">
                            &times;
                        </button>

                    </div>

                    <form method="POST" action="{{ route('admin.account-management.store') }}">

                        @csrf

                        <div class="grid grid-cols-2 gap-3 mb-3">

                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />

                                <x-text-input id="first_name" class="block mt-1 w-full" type="text"
                                    name="first_name" required />

                                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="last_name" :value="__('Last Name')" />

                                <x-text-input id="last_name" class="block mt-1 w-full" type="text"
                                    name="last_name" required />

                                <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">

                            <div>
                                <x-input-label for="middle_initial" :value="__('Middle Initial')" />

                                <x-text-input id="middle_initial" class="block mt-1 w-full" type="text"
                                    name="middle_initial" maxlength="5" />
                            </div>

                            <div>
                                <x-input-label for="phone_number" :value="__('Contact Number')" />

                                <x-text-input id="phone_number" class="block mt-1 w-full" type="text"
                                    name="phone_number" />
                            </div>

                        </div>

                        <div class="mb-3">

                            <x-input-label for="email_username" :value="__('Email Address')" />

                            <div class="flex mt-1">

                                <x-text-input id="email_username" class="block w-full rounded-r-none" type="text"
                                    name="email_username" required placeholder="juan.delacruz" />

                                <span
                                    class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 text-gray-500 text-sm whitespace-nowrap">
                                    @vendo-ph.app
                                </span>

                            </div>

                            <x-input-error :messages="$errors->get('email_username')" class="mt-1" />

                        </div>

                        <p class="text-xs text-gray-500 mb-4">
                            A temporary password will be generated automatically.
                            You'll see it once after creating the account — copy it to give to the admin.
                            They'll be required to set their own password on first login.
                        </p>

                        <button type="submit"
                            class="w-full bg-[#3b1735] text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-[#4d1f45]">
                            Create Account
                        </button>

                    </form>

                </div>

            </div>
        @endif

        <!-- Edit Profile Modal -->
        <div x-show="editProfile" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
            @click.self="editProfile = false">

            <div class="bg-white rounded-2xl p-6 w-full max-w-lg" @click.stop>

                <div class="flex items-center justify-between mb-4">

                    <h3 class="font-bold text-lg text-gray-900">
                        Edit Profile
                    </h3>

                    <button type="button" @click="editProfile = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">
                        &times;
                    </button>

                </div>

                <form method="POST" action="{{ route('admin.account-management.profile.update') }}"
                    enctype="multipart/form-data" x-target.push="main-content">

                    @csrf
                    @method('PUT')

                    <div class="mb-3">

                        <x-input-label for="profile_picture" :value="__('Profile Picture')" />

                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                            class="block mt-1 w-full text-sm text-gray-600">

                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">

                        <div>
                            <x-input-label for="edit_first_name" :value="__('First Name')" />

                            <x-text-input id="edit_first_name" class="block mt-1 w-full" type="text"
                                name="first_name" :value="$currentAdmin->first_name" required />
                        </div>

                        <div>
                            <x-input-label for="edit_last_name" :value="__('Last Name')" />

                            <x-text-input id="edit_last_name" class="block mt-1 w-full" type="text"
                                name="last_name" :value="$currentAdmin->last_name" required />
                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">

                        <div>
                            <x-input-label for="edit_middle_initial" :value="__('Middle Initial')" />

                            <x-text-input id="edit_middle_initial" class="block mt-1 w-full" type="text"
                                name="middle_initial" :value="$currentAdmin->middle_initial" maxlength="5" />
                        </div>

                        <div>
                            <x-input-label for="edit_phone_number" :value="__('Contact Number')" />

                            <x-text-input id="edit_phone_number" class="block mt-1 w-full" type="text"
                                name="phone_number" :value="$currentAdmin->phone_number" />
                        </div>

                    </div>

                    @if (!$currentAdmin->is_super_admin)
                        <div class="mb-4">

                            <x-input-label for="edit_recovery_email" :value="__('Secondary Email (for password recovery)')" />

                            <x-text-input id="edit_recovery_email" class="block mt-1 w-full" type="email"
                                name="recovery_email" :value="$currentAdmin->recovery_email" />

                        </div>
                    @endif

                    <button type="submit"
                        class="w-full bg-[#3b1735] text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-[#4d1f45]">
                        Save Changes
                    </button>

                </form>

            </div>

        </div>

        <!-- Change Password Modal -->
        <div x-show="changePass" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
            @click.self="changePass = false">

            <div class="bg-white rounded-2xl p-6 w-full max-w-md" @click.stop>

                <div class="flex items-center justify-between mb-4">

                    <h3 class="font-bold text-lg text-gray-900">
                        Change Password
                    </h3>

                    <button type="button" @click="changePass = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">
                        &times;
                    </button>

                </div>

                <form method="POST" action="{{ route('admin.account-management.profile.password') }}"
                    x-target.push="main-content">

                    @csrf
                    @method('PUT')

                    <div class="mb-3">

                        <x-input-label for="current_password" :value="__('Current Password')" />

                        <x-text-input id="current_password" class="block mt-1 w-full" type="password"
                            name="current_password" required />

                        <x-input-error :messages="$errors->get('current_password')" class="mt-1" />

                    </div>

                    <div class="mb-3">

                        <x-input-label for="new_password" :value="__('New Password')" />

                        <x-text-input id="new_password" class="block mt-1 w-full" type="password" name="password"
                            required />

                        <x-input-error :messages="$errors->get('password')" class="mt-1" />

                    </div>

                    <div class="mb-4">

                        <x-input-label for="new_password_confirmation" :value="__('Confirm New Password')" />

                        <x-text-input id="new_password_confirmation" class="block mt-1 w-full" type="password"
                            name="password_confirmation" required />

                    </div>

                    <button type="submit"
                        class="w-full bg-[#3b1735] text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-[#4d1f45]">
                        Update Password
                    </button>

                </form>

            </div>

        </div>

    </div>
</x-admin.layout>
