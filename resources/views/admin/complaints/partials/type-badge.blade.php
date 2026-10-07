{{-- Outlined type pill (as in the mockup). Needs $complaint. Long type names wrap instead of being cut off. --}}
<span class="inline-flex max-w-full items-center rounded-full border px-3 py-0.5 text-center text-xs font-medium leading-snug"
    style="background-color: {{ $complaint->type_colors['bg'] }}; border-color: {{ $complaint->type_colors['border'] }}; color: {{ $complaint->type_colors['border'] }};">
    {{ $complaint->type ?? 'Other' }}
</span>