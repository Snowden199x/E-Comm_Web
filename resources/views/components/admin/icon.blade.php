@props(['name', 'size' => 20, 'stroke' => 1.8])

{{--
    Admin stroke icon set (24x24 grid, currentColor), same approach as x-logistics.icon.
    Usage: <x-admin.icon name="shield-check" class="h-5 w-5" />
    Paths are static strings defined here, never user input. Unknown names fall back to "package".
--}}
@php
    $shield = '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.3 7.5 9.5 4.4-1.2 7.5-4.9 7.5-9.5V6L12 3Z"/>';

    $paths = [
        // Compliance states
        'shield-check' => $shield . '<path d="m9 12 2.2 2.2L15.5 10"/>',
        'shield-alert' => $shield . '<path d="M12 8.5v4"/><path d="M12 15.6h.01"/>',
        'shield-x' => $shield . '<path d="m9.5 9.5 5 5M14.5 9.5l-5 5"/>',
        'triangle-alert' => '<path d="M12 3.5 2.5 20h19L12 3.5Z"/><path d="M12 10v4.5"/><path d="M12 17.5h.01"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
        'infinity' => '<path d="M12 12c-1.6-2.2-3.4-3.6-5.4-3.6a3.6 3.6 0 1 0 0 7.2c2 0 3.8-1.4 5.4-3.6Zm0 0c1.6 2.2 3.4 3.6 5.4 3.6a3.6 3.6 0 1 0 0-7.2c-2 0-3.8 1.4-5.4 3.6Z"/>',
        'hourglass' => '<path d="M6.5 3h11M6.5 21h11"/><path d="M7.5 3v3.5c0 1.7 1.2 3 2.7 4l1.8 1.5-1.8 1.5c-1.5 1-2.7 2.3-2.7 4V21"/><path d="M16.5 3v3.5c0 1.7-1.2 3-2.7 4L12 12l1.8 1.5c1.5 1 2.7 2.3 2.7 4V21"/>',
        'rotate-ccw' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5"/><path d="M3.5 3.5v5h5"/>',

        // Page / tab icons
        'layout-grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'clipboard-check' => '<path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1Z"/><path d="M16 5h1.5A1.5 1.5 0 0 1 19 6.5v13a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19.5v-13A1.5 1.5 0 0 1 6.5 5H8"/><path d="m9 14 2 2 4-4"/>',
        'package' => '<path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9L12 3Z"/><path d="m3.5 7.5 8.5 4.5 8.5-4.5"/><path d="M12 12v9"/>',
        'store' => '<path d="M4 9.5 5.5 4h13L20 9.5"/><path d="M4 9.5a2.75 2.75 0 0 0 5.5 0 2.75 2.75 0 0 0 5 0 2.75 2.75 0 0 0 5.5 0"/><path d="M5 12.5V20h14v-7.5"/><path d="M10 20v-4.5h4V20"/>',
        'tag' => '<path d="M3.5 12.2V4.5h7.7l9.3 9.3a1.5 1.5 0 0 1 0 2.1l-5.6 5.6a1.5 1.5 0 0 1-2.1 0L3.5 12.2Z"/><path d="M7.8 8.8h.01"/>',
        'user' => '<circle cx="12" cy="8" r="3.8"/><path d="M4.5 20.5c0-3.8 3.4-6 7.5-6s7.5 2.2 7.5 6"/>',
        'inbox' => '<path d="M3 13.5 5.8 5.6A2 2 0 0 1 7.7 4h8.6a2 2 0 0 1 1.9 1.6L21 13.5"/><path d="M3 13.5V18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4.5h-5.5a3.5 3.5 0 0 1-7 0H3Z"/>',

        // Controls
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'sliders' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
        'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.7 2.7L16 9.5"/>',
        'alert-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5"/><path d="M12 16.2h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.8h.01"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',

        // Added for the 2026-10-06 admin refresh
        'users' => '<circle cx="9" cy="8" r="3.4"/><path d="M2.8 20c0-3.4 2.8-5.4 6.2-5.4s6.2 2 6.2 5.4"/><path d="M16 4.8a3.4 3.4 0 0 1 0 6.4M18.4 14.9c1.9.7 3.1 2.4 3.1 5.1"/>',
        'user-plus' => '<circle cx="10" cy="8" r="3.8"/><path d="M3 20.5c0-3.8 3.1-6 7-6s7 2.2 7 6"/><path d="M19 8v6M16 11h6"/>',
        'user-x' => '<circle cx="10" cy="8" r="3.8"/><path d="M3 20.5c0-3.8 3.1-6 7-6s7 2.2 7 6"/><path d="m16.5 9.5 5 5M21.5 9.5l-5 5"/>',
        'megaphone' => '<path d="M3.5 10.5v3a1 1 0 0 0 1 1H7l7 4.5v-14L7 9.5H4.5a1 1 0 0 0-1 1Z"/><path d="M17.5 9a4 4 0 0 1 0 6"/><path d="m7 14.5 1.2 4.5"/>',
        'trending-up' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'trending-down' => '<path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>',
        'scale' => '<path d="M12 4v16M7 20h10M5 7h14"/><path d="m5 7-3 7a3.5 3.5 0 0 0 6 0L5 7ZM19 7l-3 7a3.5 3.5 0 0 0 6 0l-3-7Z"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 4h12l-2 4 2 4H5"/>',
        'message' => '<path d="M4 5.5h16a1 1 0 0 1 1 1V16a1 1 0 0 1-1 1h-8l-4.5 3.5V17H4a1 1 0 0 1-1-1V6.5a1 1 0 0 1 1-1Z"/>',

        // Added for the 2026-10-31 admin shell: one distinct icon per sidebar destination
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'layers' => '<path d="M12.8 2.2a2 2 0 0 0-1.6 0L2.6 6.1a1 1 0 0 0 0 1.8l8.6 3.9a2 2 0 0 0 1.6 0l8.6-3.9a1 1 0 0 0 0-1.8l-8.6-3.9Z"/><path d="m22 12.7-9.2 4.1a2 2 0 0 1-1.6 0L2 12.7"/><path d="m22 17.7-9.2 4.1a2 2 0 0 1-1.6 0L2 17.7"/>',
        'file-check' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/><path d="m9 14.5 2 2 4-4"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'percent' => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.7a1 1 0 0 0-.2-.6l-3.5-4.4A1 1 0 0 0 17.5 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'bar-chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'settings' => '<path d="M12.2 2h-.4a2 2 0 0 0-2 2v.2a2 2 0 0 1-1 1.7l-.4.2a2 2 0 0 1-2 0l-.2-.1a2 2 0 0 0-2.7.7l-.2.4a2 2 0 0 0 .7 2.7l.2.1a2 2 0 0 1 1 1.7v.5a2 2 0 0 1-1 1.7l-.2.1a2 2 0 0 0-.7 2.7l.2.4a2 2 0 0 0 2.7.7l.2-.1a2 2 0 0 1 2 0l.4.2a2 2 0 0 1 1 1.7v.2a2 2 0 0 0 2 2h.4a2 2 0 0 0 2-2v-.2a2 2 0 0 1 1-1.7l.4-.2a2 2 0 0 1 2 0l.2.1a2 2 0 0 0 2.7-.7l.2-.4a2 2 0 0 0-.7-2.7l-.2-.1a2 2 0 0 1-1-1.7v-.5a2 2 0 0 1 1-1.7l.2-.1a2 2 0 0 0 .7-2.7l-.2-.4a2 2 0 0 0-2.7-.7l-.2.1a2 2 0 0 1-2 0l-.4-.2a2 2 0 0 1-1-1.7V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/>',
        'user-cog' => '<circle cx="9" cy="7.5" r="3.8"/><path d="M10 15H6.5a4 4 0 0 0-4 4v2"/><circle cx="18" cy="16" r="2.6"/><path d="M18 11.6v1.2M18 19.2v1.2M13.6 16h1.2M21.2 16h1.2M14.9 12.9l.9.9M20.2 18.2l.9.9M21.1 12.9l-.9.9M15.8 18.2l-.9.9"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'search-x' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="m9 9 4 4M13 9l-4 4"/>',

        // Documents and media
        'paperclip' => '<path d="m21.4 11-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'trash' => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'archive' => '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
        'file-text' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'image' => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m20.5 16-4.8-4.8L7 19.5"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        'zoom-in' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6M8 11h6"/>',
        'zoom-out' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8 11h6"/>',
        'maximize' => '<path d="M4 9V5a1 1 0 0 1 1-1h4M15 4h4a1 1 0 0 1 1 1v4M20 15v4a1 1 0 0 1-1 1h-4M9 20H5a1 1 0 0 1-1-1v-4"/>',
    ];

    $svg = $paths[$name] ?? $paths['package'];
@endphp
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $svg !!}</svg>