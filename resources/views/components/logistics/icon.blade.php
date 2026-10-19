@props(['name', 'size' => 20])

@php
    // Stroke icons (24x24 grid). Paths are static strings defined here, never user input.
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.7a3.5 3.5 0 0 1 0 6.6"/><path d="M18 14.3c2.1.7 3.5 2.6 3.5 5.7"/>',
        'inbox' => '<path d="M3 13.5 5.8 5.6A2 2 0 0 1 7.7 4h8.6a2 2 0 0 1 1.9 1.6L21 13.5"/><path d="M3 13.5V18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4.5h-5.5a3.5 3.5 0 0 1-7 0H3Z"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/><path d="m3 17.5 9 5 9-5" opacity=".55"/>',
        'truck' => '<path d="M2.5 6.5A1.5 1.5 0 0 1 4 5h9v11H2.5V6.5Z"/><path d="M13 9h4.2a1 1 0 0 1 .8.4l2.7 3.4c.2.2.3.5.3.8V16h-8V9Z"/><circle cx="7" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/>',
        'map-pin' => '<path d="M12 21s7-5.6 7-11.2A7 7 0 0 0 5 9.8C5 15.4 12 21 12 21Z"/><circle cx="12" cy="9.8" r="2.6"/>',
        'bar-chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
        'message' => '<path d="M21 12a8 8 0 0 1-11.7 7.1L4 20.5l1.5-4.6A8 8 0 1 1 21 12Z"/>',
        'user-cog' => '<circle cx="10" cy="8" r="3.5"/><path d="M3 20c0-3.5 3-6 7-6 1.2 0 2.300.2 3.200.6"/><circle cx="18" cy="17" r="2.2"/><path d="M18 12.800v1.100M18 20.100v1.100M13.700 14.500l.9.6M21.400 19l.9.6M13.700 19.500l.9-.6M21.400 15l.9-.6"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.500-3.500"/>',
        'check' => '<path d="m4 12.500 5 5L20 6.500"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.500 2.700 2.700L16 9.500"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'alert' => '<path d="M12 3.500 2.500 20h19L12 3.500Z"/><path d="M12 10v4.500"/><path d="M12 17.500h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.800h.01"/>',
        'package' => '<path d="m12 3 8.500 4.500v9L12 21l-8.500-4.500v-9L12 3Z"/><path d="m3.500 7.500 8.500 4.500 8.500-4.500"/><path d="M12 12v9"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3.500" y="5" width="17" height="15.500" rx="2"/><path d="M3.500 10h17M8 3v4M16 3v4"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
        'download' => '<path d="M12 4v11"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/>',
        'printer' => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="9" rx="2"/><path d="M7 15h10v6H7z"/>',
        'shield' => '<path d="M12 3 4.500 6v5.500c0 4.600 3.100 8.300 7.500 9.500 4.400-1.200 7.500-4.900 7.500-9.500V6L12 3Z"/><path d="m9 12 2.200 2.200L15.500 10"/>',
        'bike' => '<circle cx="6" cy="16" r="3.500"/><circle cx="18" cy="16" r="3.500"/><path d="M6 16 9.500 8h4l4.500 8"/><path d="M13.500 8 15 5h2.500"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L18.500 9.500a2.100 2.100 0 0 0-3-3L5 17v3Z"/><path d="m13.500 6.500 3 3"/>',
        'eye' => '<path d="M2 12s3.500-7 10-7 10 7 10 7-3.500 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="m3 3 18 18"/><path d="M10.600 6.100A10 10 0 0 1 12 6c6.500 0 10 6 10 6a17 17 0 0 1-3.100 3.900M6.500 6.600C3.700 8.300 2 12 2 12s3.500 6 10 6a9.500 9.500 0 0 0 4-.9"/><path d="M9.900 9.900a3 3 0 0 0 4.200 4.200"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14.500-4.200L4 9"/><path d="M4 4v5h5"/><path d="M4 13a8 8 0 0 0 14.500 4.200L20 15"/><path d="M20 20v-5h-5"/>',
        'undo' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01"/><path d="M10 21v-4h4v4"/>',
        'bell' => '<path d="M6 9a6 6 0 1 1 12 0c0 6 2.500 7.500 2.500 7.500h-17S6 15 6 9Z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'phone' => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
    ];
    $svg = $paths[$name] ?? $paths['package'];
@endphp
<svg {{ $attributes->class(['lg-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $svg !!}</svg>