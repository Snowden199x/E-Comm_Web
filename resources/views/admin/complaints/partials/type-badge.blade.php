<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium border"
    style="border-color: {{ $complaint->type_colors['border'] }}; background-color: {{ $complaint->type_colors['bg'] }}; color: {{ $complaint->type_colors['border'] }};">
    {{ $complaint->type ?? 'Other' }}
</span>