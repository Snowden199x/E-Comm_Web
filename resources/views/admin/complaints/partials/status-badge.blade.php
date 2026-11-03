{{-- Status pill. Needs $complaint. Open = amber, In progress = plum, Resolved = green; the label always says it. --}}
@php
    $statusBadge = [
        'open' => ['Open', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
        'in_review' => ['In progress', 'border-vd-plum-soft bg-vd-lavender text-vd-plum'],
        'resolved' => ['Resolved', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
    ][$complaint->status] ?? [ucfirst(str_replace('_', ' ', (string) $complaint->status)), 'border-vd-line-strong bg-vd-tint text-vd-muted'];
@endphp
<span class="inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[1] }}">{{ $statusBadge[0] }}</span>
