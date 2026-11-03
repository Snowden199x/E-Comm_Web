{{-- Complaint case: header + one chat per person. Needs $complaint, $parties, $defaultWho. --}}
@php
    $caseCode = 'CMP-'.($complaint->created_at?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $complaint->id, 4, '0', STR_PAD_LEFT);
@endphp

<div class="flex flex-none flex-wrap items-center gap-x-3 gap-y-2 border-b border-vd-line p-4">
    <button type="button" @click="back()" aria-label="Back to messages"
        class="-ml-1 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-vd-plum transition-colors duration-200 hover:bg-vd-tint lg:hidden">
        <x-admin.icon name="chevron-left" class="h-5 w-5" />
    </button>
    <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-vd-ink">{{ $caseCode }}</span>
            <span class="inline-flex items-center rounded-full border border-vd-peach-line bg-vd-peach px-2.5 py-0.5 text-[11px] font-medium text-vd-clay">Complaint</span>
            @include('admin.complaints.partials.status-badge', ['complaint' => $complaint])
        </p>
        <p class="mt-0.5 truncate text-xs text-vd-muted">
            {{ $complaint->type ?? 'Other' }}@if ($complaint->order) · Order #ORD-{{ str_pad((string) $complaint->order->id, 4, '0', STR_PAD_LEFT) }}@endif
        </p>
    </div>
    <a href="{{ route('admin.complaints.show', $complaint) }}" x-target.push="main-content sidebar"
        class="inline-flex h-9 flex-shrink-0 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
        Open case
    </a>
</div>

@include('admin.messages.partials.chat-pane', [
    'fetchUrl' => route('admin.messages.case.fetch', $complaint),
    'sendUrl' => route('admin.complaints.messages.store', $complaint),
    'parties' => $parties,
    'defaultWho' => $defaultWho,
    'attachments' => false,
])
