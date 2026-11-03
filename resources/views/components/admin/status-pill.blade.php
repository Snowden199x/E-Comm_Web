@props(['tone' => 'neutral', 'icon' => null])

{{--
    One pastel status pill for every Admin table. Tones are fixed so a colour always means the same thing:
    success (green) = fine / can sell · warning (salmon) = needs attention · danger (deep red) = blocked
    plum = suspended / special · pending (amber) = waiting for a decision · neutral (grey) = inactive.
    Usage: <x-admin.status-pill tone="success" icon="shield-check">Compliant</x-admin.status-pill>
--}}
@php
    $tones = [
        'success' => 'border-[#2E8B3E] bg-[#DCEFD9] text-[#1F6B35]',
        'warning' => 'border-[#E58A76] bg-[#FDE6DF] text-[#C2452D]',
        'danger' => 'border-[#9B1C2C] bg-[#F9D6DA] text-[#9B1C2C]',
        'plum' => 'border-vd-orchid bg-[#E6DEE5] text-[#5E3A66]',
        'pending' => 'border-vd-sand-line bg-vd-sand text-vd-warning-ink',
        'neutral' => 'border-[#B8B2BC] bg-[#F0EEF1] text-[#5E5663]',
    ][$tone] ?? 'border-[#B8B2BC] bg-[#F0EEF1] text-[#5E5663]';
@endphp

<span {{ $attributes->class(['inline-flex max-w-full items-center gap-1.5 rounded-full border-[1.5px] px-3 py-0.5 text-xs font-semibold', $tones]) }}>
    @if ($icon)
        <x-admin.icon :name="$icon" class="h-3.5 w-3.5 flex-shrink-0" />
    @endif
    {{ $slot }}
</span>
