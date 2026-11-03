{{--
    Colour-coded user type pill (Registrations + User Management tables). Expects $role.
    Hues are kept apart from the status pills: seller = orchid, buyer = blue, logistics = amber.
--}}
@php
    $typeTone = [
        'seller' => 'border-vd-mauve-line bg-vd-mauve text-[#5E3A66]',
        'buyer' => 'border-[#B9D3EE] bg-[#E3EEF9] text-[#1F4E85]',
        'logistics_center' => 'border-vd-sand-line bg-vd-sand text-vd-warning-ink',
    ][$role] ?? 'border-vd-line-strong bg-vd-tint text-vd-ink';
@endphp
<span class="inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium {{ $typeTone }}">{{ ucwords(str_replace('_', ' ', $role)) }}</span>
