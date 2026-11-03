{{--
    Rows for the inbox. Rendered on first load and again by GET admin.messages.list.
    Needs $items, $total, $limit, $filters. Uses the page scope: activeKey, open(), clearAll().
--}}
@php
    $isFiltered = $filters['search'] !== '' || $filters['date'] !== '' || $filters['status'] !== 'all' || $filters['tab'] !== 'all';
@endphp

@forelse ($items as $item)
    <button type="button" @click="open('{{ $item->key }}', '{{ $item->url }}')"
        :aria-current="activeKey === '{{ $item->key }}' ? 'true' : null"
        :class="activeKey === '{{ $item->key }}' ? 'bg-vd-lavender/60' : 'hover:bg-vd-tint'"
        style="--i: {{ min($loop->index, 12) }}"
        class="grid w-full grid-cols-[minmax(0,1fr)_88px_minmax(0,0.9fr)] items-center gap-3 border-b border-vd-line px-4 py-3 text-left transition-colors duration-150
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-vd-plum/40">
        <span class="min-w-0">
            <span class="flex items-center gap-1.5">
                <span class="truncate text-[13px] font-semibold text-vd-ink">{{ $item->code }}</span>
                @if ($item->unread > 0)
                    <span class="inline-flex h-4 min-w-4 flex-shrink-0 items-center justify-center rounded-full bg-vd-plum px-1 text-[10px] font-semibold leading-none text-white"
                        aria-label="{{ $item->unread }} unread">{{ $item->unread > 99 ? '99+' : $item->unread }}</span>
                @endif
            </span>
            <span class="mt-0.5 block truncate text-xs text-vd-ink">{{ $item->subject }}</span>
            @if ($item->detail)
                <span class="block truncate text-xs text-vd-muted">{{ $item->detail }}</span>
            @endif
        </span>

        <span>
            @if ($item->kind === 'complaint')
                <span class="inline-flex items-center rounded-full border border-vd-peach-line bg-vd-peach px-2.5 py-0.5 text-[11px] font-medium text-vd-clay">Complaint</span>
            @else
                <span class="inline-flex items-center rounded-full border border-vd-plum-soft bg-vd-lavender px-2.5 py-0.5 text-[11px] font-medium text-vd-plum">Direct</span>
            @endif
        </span>

        <span class="min-w-0 space-y-1">
            @foreach ($item->people as $person)
                <span class="block">
                    <span class="block truncate text-[13px] font-medium leading-tight text-vd-ink">{{ $person['name'] }}</span>
                    <span class="block text-[11px] leading-tight text-vd-muted">{{ $person['role'] }}</span>
                </span>
            @endforeach
        </span>
    </button>
@empty
    @if ($isFiltered)
        <x-admin.empty-state size="sm" filtered>
            <button type="button" @click="clearAll()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                Clear filters
            </button>
        </x-admin.empty-state>
    @else
        <x-admin.empty-state size="sm" icon="message" title="No messages" text="Complaint cases and direct messages appear here." />
    @endif
@endforelse

@if ($total > $limit)
    <p class="px-4 py-3 text-center text-xs text-vd-muted">Showing the latest {{ $limit }} of {{ number_format($total) }}. Search to narrow the list.</p>
@endif
