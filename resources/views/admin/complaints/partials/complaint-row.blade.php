{{--
    One case row. Opens the case preview (see preview-modal.blade.php).
    Needs: $complaint. Optional: $index (stagger), $sample (true for the preview-only sample case).
--}}
@php
    $sample = $sample ?? false;
    $key = $sample ? "'sample'" : (string) $complaint->id;
    $roleLabel = fn ($role) => $role === 'logistics_center' ? 'Logistics' : ucfirst((string) $role);
    $caseNo = 'CMP-' . $complaint->created_at->format('Y') . '-' . str_pad($complaint->id, 5, '0', STR_PAD_LEFT);
    $isReport = $complaint->kind === 'user_report';
    $waitingDays = $complaint->status === 'open' ? (int) $complaint->created_at->diffInDays(now()) : 0;
@endphp
<tr data-case-row data-status="{{ $complaint->status }}" data-kind="{{ $isReport ? 'user_report' : 'complaint' }}"
    style="--i: {{ $index ?? 0 }}" tabindex="0" role="button"
    aria-label="Preview case {{ $caseNo }}"
    @click="drawerId = {!! $key !!}" @keydown.enter="drawerId = {!! $key !!}" @keydown.space.prevent="drawerId = {!! $key !!}"
    class="cursor-pointer transition-colors duration-150 hover:bg-[#F6EFF6] focus-visible:bg-[#F6EFF6] focus-visible:outline-none
           focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40">

    <td class="px-5 py-3.5 align-middle">
        <p class="whitespace-nowrap font-medium text-[#2B1730]">{{ $caseNo }}</p>
        @if ($isReport)
            <p class="mt-0.5 text-xs text-gray-500">Account report</p>
        @endif
    </td>

    <td class="px-4 py-3.5 align-middle">
        <div class="space-y-1.5">
            @foreach ([$complaint->complainant, $complaint->respondent] as $person)
                <div class="leading-tight">
                    <p class="text-[13px] font-medium text-[#2B1730] [overflow-wrap:anywhere]">{{ $person->name ?? 'Deleted account' }}</p>
                    <p class="text-[11px] text-gray-500">{{ $roleLabel($person->role ?? '') }}</p>
                </div>
            @endforeach
        </div>
    </td>

    <td class="px-4 py-3.5 text-center align-middle">@include('admin.complaints.partials.type-badge')</td>

    <td class="px-4 py-3.5 text-center align-middle">
        @include('admin.complaints.partials.status-badge')
        @if ($complaint->decision)
            <p class="mt-1 text-xs font-medium text-gray-600">{{ ucfirst($complaint->decision) }}</p>
        @endif
    </td>

    <td class="whitespace-nowrap px-5 py-3.5 text-right align-middle">
        <p class="text-[13px] font-medium text-[#2B1730]">{{ $complaint->created_at->format('M j, Y') }}</p>
        <p class="text-xs text-gray-500">{{ $complaint->created_at->format('g:i A') }}</p>
        @if ($waitingDays >= 2)
            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-amber-700">
                <x-admin.icon name="clock" class="h-3.5 w-3.5" /> Waiting {{ $waitingDays }} days
            </p>
        @endif
    </td>
</tr>