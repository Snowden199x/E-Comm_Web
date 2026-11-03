<x-admin.layout title="Admin account">
    @php
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $card = 'rounded-2xl border border-vd-line bg-white p-5';
        $action = "flex h-10 w-full items-center justify-center gap-2 rounded-lg border px-4 text-sm font-medium transition-colors duration-200 {$focus}";
        $secondary = 'border-vd-line-strong bg-white text-vd-plum hover:border-vd-plum hover:bg-vd-tint';

        // One status for the whole page: what the admin can do right now.
        if ($admin->archived_at) {
            $status = ['neutral', 'Archived', 'archive'];
        } elseif ($admin->account_status === 'suspended') {
            $status = ['danger', 'Suspended', 'ban'];
        } elseif ($admin->must_change_password) {
            $status = ['pending', 'Pending setup', 'clock'];
        } else {
            $status = ['success', 'Active', 'check-circle'];
        }
    @endphp

    <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{ confirmAction: null }">
        <a href="{{ route('admin.account-management.index', ['tab' => 'admin-accounts']) }}" x-target.push="main-content" data-skeleton="table"
            class="-ml-2 mb-3 inline-flex h-8 items-center gap-1 rounded-lg px-2 text-[13px] font-medium text-vd-ink transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum {{ $focus }}">
            <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" /> Back to admin accounts
        </a>

        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">{{ $admin->name }}</h1>
            <p class="mt-1 text-sm text-vd-muted">View this admin and manage their access.</p>
        </div>

        @include('admin.account-management.partials.notice')
        @include('admin.account-management.partials.credentials-dialog')

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            {{-- Who they are and what you can do --}}
            <section class="{{ $card }} text-center" aria-label="Admin summary and actions">
                <div class="flex justify-center">
                    <x-admin.avatar :user="$admin" size="h-20 w-20" text="text-2xl" />
                </div>
                <h2 class="mt-3 font-display text-lg font-semibold text-vd-ink">{{ $admin->name }}</h2>
                <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
                    <x-admin.status-pill :tone="$admin->is_super_admin ? 'plum' : 'neutral'">{{ $admin->is_super_admin ? 'Super Admin' : 'Admin' }}</x-admin.status-pill>
                    <x-admin.status-pill :tone="$status[0]" :icon="$status[2]">{{ $status[1] }}</x-admin.status-pill>
                </div>

                @unless ($admin->is_super_admin || $admin->archived_at)
                    <div class="mt-5 space-y-2 border-t border-vd-line pt-5">
                        @if ($admin->must_change_password && $admin->temp_password_plain)
                            <form method="POST" action="{{ route('admin.account-management.view-temp-password', $admin) }}" x-target.push="main-content">
                                @csrf
                                <button type="submit" class="{{ $action }} {{ $secondary }}">
                                    <x-admin.icon name="key" class="h-4 w-4" /> View temporary password
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.account-management.send-reset-link', $admin) }}" x-target.push="main-content">
                            @csrf
                            <button type="submit" class="{{ $action }} {{ $secondary }}">
                                <x-admin.icon name="mail" class="h-4 w-4" /> Send password reset link
                            </button>
                        </form>

                        @if ($admin->account_status === 'active')
                            <button type="button" @click="confirmAction = 'suspend'" aria-haspopup="dialog"
                                class="{{ $action }} border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink hover:border-vd-warning-ink">
                                <x-admin.icon name="ban" class="h-4 w-4" /> Suspend account
                            </button>
                        @elseif ($admin->account_status === 'suspended')
                            <form method="POST" action="{{ route('admin.account-management.reactivate', $admin) }}" x-target.push="main-content">
                                @csrf
                                <button type="submit" class="{{ $action }} border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink">
                                    <x-admin.icon name="rotate-ccw" class="h-4 w-4" /> Reactivate account
                                </button>
                            </form>
                        @endif

                        <button type="button" @click="confirmAction = 'delete'" aria-haspopup="dialog"
                            class="{{ $action }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink">
                            <x-admin.icon name="trash" class="h-4 w-4" /> Delete account
                        </button>
                    </div>
                @endunless
            </section>

            {{-- Details --}}
            <section class="{{ $card }} lg:col-span-2" aria-labelledby="admin-info-title">
                <h2 id="admin-info-title" class="font-display text-base font-semibold text-vd-ink">Account information</h2>

                <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                    @foreach ([
                        ['Admin ID', '#' . $admin->id],
                        ['Full name', $admin->name],
                        ['Primary email', $admin->email],
                        ['Secondary email', $admin->recovery_email ?: '—'],
                        ['Contact number', $admin->phone_number ?: '—'],
                        ['Date created', $admin->created_at->format('M d, Y')],
                        ['Last sign-in', $admin->last_login_at ? $admin->last_login_at->format('M d, Y \a\t g:i A') : 'Never'],
                        ['Password', $admin->must_change_password ? 'Waiting for the admin to set one' : 'Set by the admin'],
                    ] as [$name, $value])
                        <div class="min-w-0">
                            <dt class="text-xs text-vd-muted">{{ $name }}</dt>
                            <dd class="mt-0.5 break-words font-medium text-vd-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div>
                        <dt class="text-xs text-vd-muted">Account status</dt>
                        <dd class="mt-1"><x-admin.status-pill :tone="$status[0]" :icon="$status[2]">{{ $status[1] }}</x-admin.status-pill></dd>
                    </div>
                </dl>
            </section>
        </div>

        <x-admin.confirm-dialog model="confirmAction === 'suspend'" close="confirmAction = null" title="Suspend this admin?"
            text="They can't sign in to the Admin Panel until you reactivate them."
            :action="route('admin.account-management.suspend', $admin)" confirm="Suspend admin" tone="warning" ajax />

        <x-admin.confirm-dialog model="confirmAction === 'delete'" close="confirmAction = null" title="Delete this admin?"
            :text="$admin->name . ' loses access now. You can restore or delete them for good from the Archived filter.'"
            :action="route('admin.account-management.destroy', $admin)" method="DELETE" confirm="Delete admin" tone="danger" ajax />
    </div>
</x-admin.layout>
