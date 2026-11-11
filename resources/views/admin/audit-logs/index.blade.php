@php
    $field = 'h-10 w-full rounded-lg border border-vd-line-strong bg-white px-3 text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-vd-plum/40';
    $btn = 'inline-flex h-10 items-center rounded-lg bg-vd-plum px-4 text-[13px] font-semibold text-white transition-all duration-200 hover:bg-vd-plum-hover active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $soft = 'inline-flex h-10 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-tint';
    $cell = 'border-t border-vd-line px-3 py-3 align-top text-[13px] text-vd-ink';
    $tone = [
        'registration_approved' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'user_activated' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'suspension_lifted' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'seller_category_request_approved' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'registration_rejected' => 'bg-[#fdf1f3] text-[#a32b43]',
        'user_suspended' => 'bg-[#fdf1f3] text-[#a32b43]',
        'deletion_rejected' => 'bg-[#fdf1f3] text-[#a32b43]',
        'seller_category_request_rejected' => 'bg-[#fdf1f3] text-[#a32b43]',
        'user_deactivated' => 'bg-slate-100 text-slate-700',
        'deletion_approved' => 'bg-slate-100 text-slate-700',
    ];
@endphp
<x-admin.layout title="Audit Logs">
    <div class="mx-auto w-full max-w-[1200px] p-4 sm:p-6 lg:p-7">
        <header class="mb-4">
            <h1 class="text-[24px] font-semibold text-vd-ink">Audit Logs</h1>
            <p class="mt-1 max-w-[820px] text-[13px] text-vd-muted">
                Account decisions recorded by Vendo Admins: who did it, which account it affected, when, and why.
                New entries appear here as soon as the action is saved.
            </p>
        </header>

        <x-admin.flash class="mb-4" />

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-4 grid gap-3 rounded-xl border border-vd-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="q" class="mb-1 block text-[12px] font-medium text-vd-ink">Search</label>
                <input id="q" name="q" value="{{ $q }}" placeholder="Admin, account name, email or reason" class="{{ $field }}">
            </div>
            <div>
                <label for="action" class="mb-1 block text-[12px] font-medium text-vd-ink">Action</label>
                <select id="action" name="action" class="{{ $field }}">
                    <option value="">All actions</option>
                    @foreach ($usedActions as $key => $text)
                        <option value="{{ $key }}" @selected($action === $key)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="mb-1 block text-[12px] font-medium text-vd-ink">From</label>
                <input id="from" type="date" name="from" value="{{ $from }}" class="{{ $field }}">
            </div>
            <div>
                <label for="to" class="mb-1 block text-[12px] font-medium text-vd-ink">To</label>
                <input id="to" type="date" name="to" value="{{ $to }}" class="{{ $field }}">
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="{{ $btn }}">Apply filters</button>
                @if ($q !== '' || $action !== '' || $from !== '' || $to !== '')
                    <a href="{{ route('admin.audit-logs.index') }}" class="{{ $soft }}">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-vd-line bg-white">
            <table class="w-full min-w-[860px] text-left">
                <thead class="bg-vd-tint text-[12px] uppercase tracking-wide text-vd-muted">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Admin</th>
                        <th class="px-3 py-2">Action</th>
                        <th class="px-3 py-2">Account</th>
                        <th class="px-3 py-2">Reason</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="{{ $cell }} whitespace-nowrap">
                            {{ $log->created_at?->format('M j, Y') }}
                            <div class="text-[12px] text-vd-muted">{{ $log->created_at?->format('g:i A') }}</div>
                        </td>
                        <td class="{{ $cell }}">
                            {{ $log->actor?->name ?? 'Removed admin' }}
                            <div class="text-[12px] text-vd-muted">{{ $log->actor?->email }}</div>
                        </td>
                        <td class="{{ $cell }}">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[12px] font-medium {{ $tone[$log->action] ?? 'bg-vd-tint text-vd-plum' }}">
                                {{ $label($log->action) }}
                            </span>
                        </td>
                        <td class="{{ $cell }}">
                            @if ($log->target)
                                {{ $log->target->name }}
                                <div class="text-[12px] text-vd-muted">
                                    {{ $log->target->email }}
                                    @if ($log->target->role) · {{ ucfirst($log->target->role) }} @endif
                                </div>
                            @else
                                Platform setting
                            @endif
                        </td>
                        <td class="{{ $cell }} max-w-[320px]">{{ $log->reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-[13px] text-vd-muted">
                            @if ($q !== '' || $action !== '' || $from !== '' || $to !== '')
                                No audit entry matched these filters.
                            @else
                                No account decision has been recorded yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </div>
</x-admin.layout>
