<span class="inline-block px-2 py-1 rounded-full text-xs font-medium border"
    style="border-color: {{ $complaint->status_colors['border'] }}; background-color: {{ $complaint->status_colors['bg'] }}; color: {{ $complaint->status_colors['border'] }};">
    {{ $complaint->status_colors['label'] }}
</span>