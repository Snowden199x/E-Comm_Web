@use('App\Models\Support\SupportTicket')
@use('Illuminate\Support\Str')
<x-admin.layout title="Support tickets">
    @vite('resources/css/admin/registrations.css')

    @php

        $tabs = ['open' => 'Open', 'in_review' => 'In review', 'resolved' => 'Resolved'];
        $tabIcons = ['open' => 'inbox', 'in_review' => 'hourglass', 'resolved' => 'check-circle'];
        // One pill per status, same meaning as every Admin table: amber = waiting, plum = in progress, green = done.
        $pillTone = ['open' => 'pending', 'in_review' => 'plum', 'resolved' => 'success'];
        $topicIcons = ['order' => 'package', 'payment' => 'banknote', 'account' => 'user', 'product' => 'store', 'other' => 'message'];
        $topicOptions = collect(SupportTicket::CATEGORIES)->mapWithKeys(fn ($text, $key) => [$key => [$text, $topicIcons[$key] ?? 'message']])->all();

        $filtered = $status !== '' || $category !== '' || $q !== '';
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $pill = 'inline-flex h-10 items-center gap-2 rounded-full border border-vd-orchid bg-vd-paper px-3.5 text-[13px] font-medium text-vd-ink transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/30';
        $chip = 'inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink';
        $openCount = (int) ($counts['open'] ?? 0);
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6"
        x-data="{ category: @js($category), topicOpen: false }"
        @ajax:after.window="document.getElementById('main-content')?.scrollTo({ top: 0 })">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Support tickets</h1>
            <p class="mt-1 text-sm text-vd-muted">Questions Buyers send to Vendo. Replying to an open ticket moves it to In review. Resolving it tells the Buyer.</p>
        </div>

        <x-admin.flash class="mb-4" :except="['q']" />

        {{-- Status cards with counts. They are links; select the active card again to clear it. --}}
        <nav aria-label="Ticket status" data-skeleton="table" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($tabs as $key => $tabLabel)
                @php $active = $status === $key; @endphp
                <a href="{{ route('admin.support-tickets.index', array_filter(['status' => $active ? null : $key, 'q' => $q, 'category' => $category])) }}"
                    x-target.push="main-content" style="--i: {{ $loop->index }}" @if ($active) aria-current="page" @endif
                    @class([
                        'rg-card flex items-center gap-3 rounded-[18px] border px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40',
                        'border-vd-plum bg-[#DDD0DB] ring-2 ring-vd-orchid/30' => $active,
                        'border-vd-orchid bg-[#E6DEE5] hover:-translate-y-0.5 hover:border-vd-plum hover:bg-[#E0D5DF]' => ! $active,
                    ])>
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white">
                        <x-admin.icon :name="$tabIcons[$key]" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $tabLabel }}</span>
                        <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ number_format($counts[$key] ?? 0) }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- Toolbar: search runs on Enter, the topic applies as soon as it is picked. --}}
        <form method="GET" action="{{ route('admin.support-tickets.index') }}" x-ref="filters" x-target.push="main-content" data-skeleton="table" role="search"
            class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="hidden" name="category" :value="category" :disabled="!category" @if ($category === '') disabled @else value="{{ $category }}" @endif>

            <div class="group relative w-full sm:max-w-[400px]">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
                <input type="search" name="q" value="{{ $q }}" maxlength="100" autocomplete="off" aria-label="Search tickets"
                    placeholder="Search by number, subject, buyer or email, then press Enter"
                    class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-4 text-[13px] text-vd-ink placeholder:text-vd-muted transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($filtered)
                    <a href="{{ route('admin.support-tickets.index') }}" x-target.push="main-content" data-skeleton="table"
                        class="inline-flex h-10 items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">Clear filters</a>
                @endif

                <div class="relative" @click.outside="topicOpen = false" @keydown.escape="topicOpen = false">
                    <button type="button" @click="topicOpen = !topicOpen" aria-haspopup="listbox" :aria-expanded="topicOpen" aria-label="Filter by topic"
                        :class="topicOpen ? '!border-vd-plum !bg-white' : ''" class="{{ $pill }} pr-3">
                        <span>{{ $topicOptions[$category][0] ?? 'All topics' }}</span>
                        <x-admin.icon name="chevron-down" class="h-4 w-4 text-vd-plum transition-transform duration-200" ::class="topicOpen ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="topicOpen" x-cloak role="listbox" aria-label="Filter by topic"
                        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="-translate-y-1 scale-95 opacity-0"
                        class="absolute right-0 z-30 mt-2 w-60 origin-top-right rounded-2xl border border-vd-line-strong bg-white p-1.5">
                        @foreach (['' => ['All topics', 'layers']] + $topicOptions as $value => [$label, $icon])
                            <button type="button" role="option" :aria-selected="category === '{{ $value }}'"
                                @click="category = '{{ $value }}'; topicOpen = false; $nextTick(() => $refs.filters.requestSubmit())"
                                :class="category === '{{ $value }}' ? 'bg-vd-lavender font-semibold text-vd-plum' : 'text-vd-ink hover:bg-vd-tint hover:text-vd-plum focus:bg-vd-tint'"
                                class="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-[13px] font-medium transition-colors duration-150 focus:outline-none">
                                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid/15 text-vd-plum"><x-admin.icon name="{{ $icon }}" class="h-3.5 w-3.5" /></span>
                                <span class="flex-1">{{ $label }}</span>
                                <x-admin.icon name="check" class="h-4 w-4 text-vd-plum" x-show="category === '{{ $value }}'" />
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
            @if ($tickets->isEmpty())
                <x-admin.empty-state icon="inbox" title="No support tickets yet" text="When a Buyer writes to Vendo, the ticket appears here."
                    :filtered="$filtered" filtered-title="No tickets match your filters" filtered-text="Search for a different word, or choose another status or topic.">
                    @if ($filtered)
                        <a href="{{ route('admin.support-tickets.index') }}" x-target.push="main-content" data-skeleton="table"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">Clear filters</a>
                    @endif
                </x-admin.empty-state>
            @else
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-[13px]">
                        <caption class="sr-only">Support tickets. Select a row to open the ticket.</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">Ticket</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Buyer</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Topic</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Last activity</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium"><span class="sr-only">Action</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($tickets as $ticket)
                                @php
                                    $isOpen = $ticket->status === 'open';
                                    $last = $ticket->last_reply_at ?? $ticket->created_at;
                                    $stale = $isOpen && $last && $last->lt(now()->subDay());
                                    $link = route('admin.support-tickets.show', $ticket);
                                @endphp
                                <tr tabindex="0" aria-label="Open ticket {{ $ticket->id }}, {{ $ticket->subject }}"
                                    @click="if (!$event.target.closest('a, button')) $el.querySelector('[data-row-link]').click()"
                                    @keydown.enter.self="$el.querySelector('[data-row-link]').click()"
                                    class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint {{ $focus }} focus-visible:ring-inset">
                                    <td class="px-4 py-3">
                                        <p class="max-w-[280px] truncate font-medium text-vd-ink">{{ $ticket->subject }}</p>
                                        <p class="text-xs text-vd-muted">#{{ $ticket->id }} · {{ $ticket->messages_count }} {{ Str::plural('message', $ticket->messages_count) }}@if ($ticket->order) · {{ $ticket->order->number }}@endif</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <x-admin.avatar :user="$ticket->user" name="Buyer" size="h-8 w-8" text="text-xs" />
                                            <div class="min-w-0">
                                                <p class="max-w-[180px] truncate font-medium text-vd-ink">{{ $ticket->user?->name ?? 'Deleted buyer' }}</p>
                                                <p class="max-w-[180px] truncate text-xs text-vd-muted">{{ $ticket->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="{{ $chip }}">{{ $ticket->category_label }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-admin.status-pill :tone="$pillTone[$ticket->status] ?? 'neutral'">{{ $ticket->status_label }}</x-admin.status-pill></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-vd-muted">
                                        {{ $last?->diffForHumans() }}
                                        <span @class(['block text-xs', 'font-medium text-vd-warning-ink' => $stale])>{{ $stale ? 'Waiting for a reply' : $ticket->created_at?->format('M j, Y') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ $link }}" data-row-link x-target.push="main-content" data-skeleton="form" aria-label="{{ $isOpen ? 'Review' : 'View' }} ticket {{ $ticket->id }}"
                                            @class([
                                                'inline-flex h-8 items-center rounded-lg px-3 text-[13px] font-medium transition-colors duration-200',
                                                $focus,
                                                'bg-vd-plum text-white hover:bg-vd-plum-hover' => $isOpen,
                                                'border border-vd-line-strong bg-white text-vd-plum hover:bg-vd-plum hover:text-white' => ! $isOpen,
                                            ])>{{ $isOpen ? 'Review ticket' : 'View ticket' }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('admin.partials.pagination', ['paginator' => $tickets, 'asLinks' => true])
            @endif
        </div>
    </div>
</x-admin.layout>
