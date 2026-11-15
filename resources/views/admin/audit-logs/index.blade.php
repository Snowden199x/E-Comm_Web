<x-admin.layout title="Audit logs">
    @php
        $filtered = $q !== '' || $action !== '' || $from !== '' || $to !== '';
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $pill = 'inline-flex h-10 items-center gap-2 rounded-full border border-vd-orchid bg-vd-paper px-3.5 text-[13px] font-medium text-vd-ink transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/30';
        $chip = 'inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink';

        // One tone per kind of decision, same meaning as every Admin table:
        // green = allowed or restored, deep red = refused or removed, salmon = held back, plum = changed.
        $tone = function (string $key): string {
            if (preg_match('/(approved|activated|lifted|created|restored|reactivated|granted|extended|resolved|set)$/', $key)) return 'success';
            if (preg_match('/(rejected|declined|dismissed|deleted)$/', $key)) return 'danger';
            if (preg_match('/(suspended|deactivated|archived|warned)$/', $key)) return 'warning';
            return 'plum';
        };
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6"
        x-data="{
            action: @js($action), actionOpen: false,
            from: @js($from), to: @js($to), applied: @js($from.'|'.$to),
            entry: null,
            get datesChanged() { return (this.from + '|' + this.to) !== this.applied; },
            get dateError() { return this.from && this.to && this.to < this.from ? 'End date is before the start date. Pick an end date on or after the start date.' : ''; },
            applyDates() { if (!this.dateError) this.$refs.filters.requestSubmit(); },
        }"
        @keydown.escape.window="entry = null"
        @ajax:after.window="document.getElementById('main-content')?.scrollTo({ top: 0 })">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Audit logs</h1>
            <p class="mt-1 text-sm text-vd-muted">Decisions Admins made: who did it, which account it affected, when and why. New entries appear as soon as the action is saved.</p>
        </div>

        <x-admin.flash class="mb-4" />

        {{-- Toolbar: search runs on Enter, the action applies when picked, dates show Apply dates after a change. --}}
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" x-ref="filters" x-target.push="main-content" data-skeleton="table" role="search"
            class="mb-5 space-y-2" @submit="if (dateError) $event.preventDefault()">
            <input type="hidden" name="action" :value="action" :disabled="!action" @if ($action === '') disabled @else value="{{ $action }}" @endif>

            <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between">
                <div class="group relative w-full lg:max-w-[400px]">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
                    <input type="search" name="q" value="{{ $q }}" maxlength="100" autocomplete="off" aria-label="Search audit logs"
                        placeholder="Search by admin, account or reason, then press Enter"
                        class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-4 text-[13px] text-vd-ink placeholder:text-vd-muted transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($filtered)
                        <a href="{{ route('admin.audit-logs.index') }}" x-target.push="main-content" data-skeleton="table"
                            class="inline-flex h-10 items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">Clear filters</a>
                    @endif

                    <label class="{{ $pill }} cursor-pointer">
                        <x-admin.icon name="calendar" class="h-4 w-4 text-vd-plum" />
                        <span class="text-vd-muted">From</span>
                        <input type="date" name="from" x-model="from" :max="to || null" aria-label="From date"
                            class="w-[128px] border-0 bg-transparent p-0 text-[13px] text-vd-ink focus:outline-none focus:ring-0">
                    </label>
                    <label class="{{ $pill }} cursor-pointer">
                        <x-admin.icon name="calendar" class="h-4 w-4 text-vd-plum" />
                        <span class="text-vd-muted">To</span>
                        <input type="date" name="to" x-model="to" :min="from || null" aria-label="To date"
                            class="w-[128px] border-0 bg-transparent p-0 text-[13px] text-vd-ink focus:outline-none focus:ring-0">
                    </label>
                    <button type="button" x-show="datesChanged" x-cloak @click="applyDates()" :disabled="!!dateError"
                        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-1 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-vd-plum px-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover disabled:cursor-not-allowed disabled:opacity-50 {{ $focus }}">
                        Apply dates
                    </button>

                    <div class="relative" @click.outside="actionOpen = false" @keydown.escape="actionOpen = false">
                        <button type="button" @click="actionOpen = !actionOpen" aria-haspopup="listbox" :aria-expanded="actionOpen" aria-label="Filter by action"
                            :class="actionOpen ? '!border-vd-plum !bg-white' : ''" class="{{ $pill }} pr-3">
                            <span class="max-w-[200px] truncate">{{ $usedActions[$action] ?? 'All actions' }}</span>
                            <x-admin.icon name="chevron-down" class="h-4 w-4 flex-shrink-0 text-vd-plum transition-transform duration-200" ::class="actionOpen ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="actionOpen" x-cloak role="listbox" aria-label="Filter by action"
                            x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                            x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="-translate-y-1 scale-95 opacity-0"
                            class="thin-scroll absolute right-0 z-30 mt-2 max-h-72 w-72 origin-top-right overflow-y-auto rounded-2xl border border-vd-line-strong bg-white p-1.5">
                            @foreach (['' => 'All actions'] + $usedActions->all() as $value => $text)
                                <button type="button" role="option" :aria-selected="action === '{{ $value }}'"
                                    @click="action = '{{ $value }}'; actionOpen = false; $nextTick(() => $refs.filters.requestSubmit())"
                                    :class="action === '{{ $value }}' ? 'bg-vd-lavender font-semibold text-vd-plum' : 'text-vd-ink hover:bg-vd-tint hover:text-vd-plum focus:bg-vd-tint'"
                                    class="flex w-full items-center justify-between gap-2.5 rounded-xl px-3 py-2 text-left text-[13px] font-medium transition-colors duration-150 focus:outline-none">
                                    <span class="min-w-0 [overflow-wrap:anywhere]">{{ $text }}</span>
                                    <x-admin.icon name="check" class="h-4 w-4 flex-shrink-0 text-vd-plum" x-show="action === '{{ $value }}'" />
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <p x-show="dateError" x-cloak x-text="dateError" class="text-right text-xs font-medium text-vd-danger-ink" role="alert"></p>
        </form>

        <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
            @if ($logs->isEmpty())
                <x-admin.empty-state icon="file-text" title="No decisions recorded yet" text="Admin decisions, such as approvals and suspensions, appear here."
                    :filtered="$filtered" filtered-title="No entries match your filters" filtered-text="Search for a different word, or widen the dates.">
                    @if ($filtered)
                        <a href="{{ route('admin.audit-logs.index') }}" x-target.push="main-content" data-skeleton="table"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">Clear filters</a>
                    @endif
                </x-admin.empty-state>
            @else
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-[13px]">
                        <caption class="sr-only">Admin decisions, newest first. Select a row to read the full entry.</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">When</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Admin</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Action</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Account</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Reason</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium"><span class="sr-only">Details</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($logs as $log)
                                @php
                                    $actionText = $label($log->action);
                                    $payload = [
                                        'when' => $log->created_at?->format('M j, Y, g:i A'),
                                        'admin' => $log->actor?->name ?? 'Removed admin',
                                        'adminEmail' => $log->actor?->email,
                                        'action' => $actionText,
                                        'account' => $log->target?->name ?? 'Platform setting',
                                        'accountEmail' => $log->target?->email,
                                        'role' => $log->target?->role ? ucfirst($log->target->role) : null,
                                        'reason' => $log->reason,
                                    ];
                                @endphp
                                <tr tabindex="0" aria-label="Open entry: {{ $actionText }}"
                                    @click="if (!$event.target.closest('a, button')) entry = @js($payload)"
                                    @keydown.enter.self="entry = @js($payload)"
                                    class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint {{ $focus }} focus-visible:ring-inset">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="font-medium text-vd-ink">{{ $log->created_at?->format('M j, Y') }}</span>
                                        <span class="block text-xs text-vd-muted">{{ $log->created_at?->format('g:i A') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <x-admin.avatar :user="$log->actor" name="Removed admin" size="h-8 w-8" text="text-xs" />
                                            <div class="min-w-0">
                                                <p class="max-w-[170px] truncate font-medium text-vd-ink">{{ $log->actor?->name ?? 'Removed admin' }}</p>
                                                <p class="max-w-[170px] truncate text-xs text-vd-muted">{{ $log->actor?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><x-admin.status-pill :tone="$tone($log->action)">{{ $actionText }}</x-admin.status-pill></td>
                                    <td class="px-4 py-3">
                                        @if ($log->target)
                                            <p class="max-w-[190px] truncate font-medium text-vd-ink">{{ $log->target->name }}</p>
                                            <p class="max-w-[190px] truncate text-xs text-vd-muted">{{ $log->target->email }}@if ($log->target->role) · {{ ucfirst($log->target->role) }}@endif</p>
                                        @else
                                            <span class="{{ $chip }}">Platform setting</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><p class="line-clamp-2 max-w-[300px] text-vd-ink [overflow-wrap:anywhere]">{{ $log->reason ?: '—' }}</p></td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="entry = @js($payload)" aria-label="View entry: {{ $actionText }}"
                                            class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-plum hover:text-white {{ $focus }}">View details</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('admin.partials.pagination', ['paginator' => $logs, 'asLinks' => true])
            @endif
        </div>

        {{-- One popup for the full entry --}}
        <x-admin.dialog model="entry !== null" close="entry = null" title="Audit entry" text="A decision an Admin made." size="md">
            <template x-if="entry">
                <dl class="space-y-3 text-[13px]">
                    <div class="flex items-start justify-between gap-4"><dt class="text-vd-muted">When</dt><dd class="text-right font-medium text-vd-ink" x-text="entry.when"></dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-vd-muted">Action</dt><dd class="text-right font-medium text-vd-ink" x-text="entry.action"></dd></div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-vd-muted">Admin</dt>
                        <dd class="text-right"><span class="block font-medium text-vd-ink [overflow-wrap:anywhere]" x-text="entry.admin"></span><span class="block text-xs text-vd-muted [overflow-wrap:anywhere]" x-text="entry.adminEmail" x-show="entry.adminEmail"></span></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-vd-muted">Account</dt>
                        <dd class="text-right"><span class="block font-medium text-vd-ink [overflow-wrap:anywhere]" x-text="entry.account"></span><span class="block text-xs text-vd-muted [overflow-wrap:anywhere]" x-show="entry.accountEmail" x-text="entry.accountEmail + (entry.role ? ' · ' + entry.role : '')"></span></dd>
                    </div>
                    <div>
                        <dt class="mb-1.5 text-vd-muted">Reason</dt>
                        <dd class="whitespace-pre-line rounded-xl border border-vd-line bg-vd-paper p-3 text-sm text-vd-ink [overflow-wrap:anywhere]" x-text="entry.reason || 'No reason was recorded.'"></dd>
                    </div>
                </dl>
            </template>
            <div class="mt-5 flex justify-end">
                <button type="button" @click="entry = null"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-4 text-sm font-medium text-vd-ink transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">Close</button>
            </div>
        </x-admin.dialog>
    </div>
</x-admin.layout>
