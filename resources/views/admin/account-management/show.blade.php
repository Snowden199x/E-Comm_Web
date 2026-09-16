<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ confirmAction: null }">

        <a href="{{ route('admin.account-management.index', ['tab' => 'admin-accounts']) }}" x-target.push="main-content"
            class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <span>&lt;</span> Back to Admin Accounts
        </a>

        @if (session('confirmation'))
            <div x-data="{ show: true }" x-show="show" x-transition:enter="transform ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform ease-in duration-300" x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0" x-init="setTimeout(() => show = false, 5000)" x-cloak
                class="fixed top-6 right-6 z-[100] w-full max-w-sm">

                <div class="bg-white rounded-xl shadow-lg border border-green-100 p-4 flex items-start gap-3">

                    <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-green-600 text-lg">✓</span>
                    </div>

                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-sm">Success</p>

                        @php
                            $messages = [
                                'admin-updated' => 'Admin information updated.',
                                'suspended' => 'Admin account suspended.',
                                'reactivated' => 'Admin account reactivated.',
                                'deactivated' => 'Admin account deactivated.',
                                'reset-link-sent' => 'Password reset link sent to the admin.',
                                'admin-deleted' => 'Admin account deleted successfully.',
                                'admin-archived' => 'Admin account archived.',
                                'admin-restored' => 'Admin account restored.',
                            ];
                        @endphp

                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $messages[session('confirmation')] ?? 'Action completed successfully.' }}
                        </p>
                    </div>

                    <button type="button" @click="show = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">
                        &times;
                    </button>

                </div>
            </div>
        @endif

        @if (session('generated_password'))
            <div x-data="{ show: true }" x-show="show" x-cloak
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">

                <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                    <h3 class="font-bold text-lg text-gray-900 mb-2">Admin Credentials</h3>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Profile Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm text-center">

                <div
                    class="w-20 h-20 rounded-full bg-[#3b1735] text-white flex items-center justify-center text-2xl font-bold overflow-hidden mx-auto mb-3">

                    @if ($admin->profile_picture)
                        <img src="{{ asset('storage/' . $admin->profile_picture) }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($admin->first_name ?? $admin->name, 0, 1)) }}{{ strtoupper(substr($admin->last_name ?? '', 0, 1)) }}
                    @endif

                </div>

                <h3 class="font-bold text-gray-900 text-lg">
                    {{ $admin->name }}
                </h3>

                <span
                    class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                    {{ $admin->is_super_admin ? 'Super Admin' : 'Admin' }}
                </span>

                @if (!$admin->is_super_admin)
                    <div class="mt-5 space-y-2">

                        @if ($admin->must_change_password && $admin->temp_password_plain)
                            <form method="POST"
                                action="{{ route('admin.account-management.view-temp-password', $admin) }}"
                                x-target.push="main-content">
                                @csrf

                                <button type="submit"
                                    class="w-full text-sm font-medium text-[#3b1735] bg-purple-50 border border-purple-200 py-2 rounded-lg hover:bg-purple-100">
                                    View Temporary Password
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.account-management.send-reset-link', $admin) }}"
                            x-target.push="main-content">
                            @csrf

                            <button type="submit"
                                class="w-full text-sm font-medium text-[#3b1735] border border-[#3b1735] py-2 rounded-lg hover:bg-[#3b1735] hover:text-white transition">
                                Send Password Reset Link
                            </button>
                        </form>

                        @if ($admin->account_status === 'active')
                            <button type="button" @click="confirmAction = 'suspend'"
                                class="w-full text-sm font-medium text-yellow-700 border border-yellow-400 py-2 rounded-lg hover:bg-yellow-50">
                                Suspend Account
                            </button>
                        @elseif ($admin->account_status === 'suspended')
                            <form method="POST" action="{{ route('admin.account-management.reactivate', $admin) }}"
                                x-target.push="main-content">

                                @csrf

                                <button type="submit"
                                    class="w-full text-sm font-medium text-green-700 border border-green-400 py-2 rounded-lg hover:bg-green-50">
                                    Reactivate Account
                                </button>

                            </form>
                        @endif

                        <button type="button" @click="confirmAction = 'delete'"
                            class="w-full text-sm font-medium text-red-700 border border-red-400 py-2 rounded-lg hover:bg-red-50">
                            Delete Account
                        </button>

                    </div>
                @endif

            </div>

            <!-- Account Information -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm">

                <h3 class="font-bold text-gray-900 mb-4">
                    Account Information
                </h3>

                <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">

                    <div>
                        <p class="text-gray-400">Admin ID</p>
                        <p class="font-medium text-gray-900">#{{ $admin->id }}</p>
                    </div>

                    <div>
                        <p class="text-gray-400">Full Name</p>
                        <p class="font-medium text-gray-900">{{ $admin->name }}</p>
                    </div>

                    <div>
                        <p class="text-gray-400">Primary Email</p>
                        <p class="font-medium text-gray-900">{{ $admin->email }}</p>
                    </div>

                    <div>
                        <p class="text-gray-400">Secondary Email</p>
                        <p class="font-medium text-gray-900">{{ $admin->recovery_email ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-400">Contact Number</p>
                        <p class="font-medium text-gray-900">{{ $admin->phone_number ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-400">Account Status</p>

                        @if ($admin->account_status === 'active' && $admin->must_change_password)
                            <span
                                class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                Pending Setup
                            </span>
                        @else
                            @if ($admin->archived_at)
                                <span
                                    class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                    Deleted
                                </span>
                            @else
                                <span @class([
                                    'inline-block px-2 py-1 rounded-full text-xs font-medium',
                                    'bg-green-100 text-green-700' => $admin->account_status === 'active',
                                    'bg-red-100 text-red-700' => $admin->account_status === 'suspended',
                                ])>
                                    {{ ucfirst($admin->account_status) }}
                                </span>
                            @endif
                        @endif
                    </div>

                    <div>
                        <p class="text-gray-400">Date Created</p>
                        <p class="font-medium text-gray-900">
                            {{ $admin->created_at->format('M d, Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-400">Last Login</p>
                        <p class="font-medium text-gray-900">
                            {{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y \a\t g:i A') : 'Never' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-400">Password Status</p>
                        <p class="font-medium text-gray-900">
                            {{ $admin->must_change_password ? 'Awaiting first-time setup' : 'Set by admin' }}
                        </p>
                    </div>

                </div>
            </div>

        </div>

        <!-- Confirm Suspend Modal -->
        <div x-show="confirmAction === 'suspend'" x-cloak
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
            @click.self="confirmAction = null">

            <div class="bg-white rounded-2xl p-6 w-full max-w-sm" @click.stop>

                <h3 class="font-bold text-lg text-gray-900 mb-2">
                    Suspend this admin?
                </h3>

                <p class="text-sm text-gray-500 mb-4">
                    They will temporarily lose access to the Admin Panel until reactivated.
                </p>

                <div class="flex gap-3">

                    <button type="button" @click="confirmAction = null"
                        class="flex-1 w-full border border-gray-300 text-gray-700 text-sm font-medium py-2 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>

                    <form method="POST" action="{{ route('admin.account-management.suspend', $admin) }}"
                        class="flex-1 w-full" x-target.push="main-content">
                        @csrf

                        <button type="submit"
                            class="w-full bg-yellow-500 text-white text-sm font-medium py-2 rounded-lg hover:bg-yellow-600">
                            Suspend
                        </button>
                    </form>

                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div x-show="confirmAction === 'delete'" x-cloak
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
            @click.self="confirmAction = null">

            <div class="bg-white rounded-2xl p-6 w-full max-w-sm" @click.stop>

                <h3 class="font-bold text-lg text-gray-900 mb-2">
                    Delete this admin?
                </h3>

                <p class="text-sm text-gray-500 mb-4">
                    This will archive
                    <span class="font-medium text-gray-900">{{ $admin->name }}</span>. They will lose access
                    immediately, but you can restore or permanently delete them later from the Archived tab.
                </p>

                <div class="flex gap-3">

                    <button type="button" @click="confirmAction = null"
                        class="flex-1 w-full border border-gray-300 text-gray-700 text-sm font-medium py-2 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>

                    <form method="POST" action="{{ route('admin.account-management.destroy', $admin) }}"
                        class="flex-1 w-full" x-target.push="main-content">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="w-full bg-red-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-red-700">
                            Delete
                        </button>
                    </form>

                </div>
            </div>
        </div>

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition:enter="transform ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transform ease-in duration-300"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0" x-init="setTimeout(() => show = false, 5000)" x-cloak
                class="fixed top-6 right-6 z-[100] w-full max-w-sm">

                <div class="bg-white rounded-xl shadow-lg border border-red-100 p-4 flex items-start gap-3">

                    <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-red-600 text-lg">!</span>
                    </div>

                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-sm">
                            Unable to send
                        </p>

                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ session('error') }}
                        </p>
                    </div>

                    <button type="button" @click="show = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">
                        &times;
                    </button>

                </div>
            </div>
        @endif

    </div>
</x-admin-layout>
