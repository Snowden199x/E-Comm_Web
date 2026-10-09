@props([
    'user' => null,        // App\Models\User (uses ->name and ->profile_picture)
    'name' => null,        // fallback label when there is no user model (initial only)
    'size' => 'h-9 w-9',   // Tailwind size classes
    'text' => 'text-[13px]', // Tailwind text size for the initial
])

{{--
    Admin avatar: shows the user's uploaded profile picture, with the initial as the fallback.

    Why this exists: the Admin screens used to draw only the first letter, so a profile picture
    changed by a buyer, seller or logistics center never appeared here (Messages was the exception).
    Pictures live on the "public" disk (profile-pictures/...), so the URL is asset('storage/...'),
    the same way the Admin top bar already shows the admin's own photo.

    - The initial is always rendered underneath; the image covers it. If the file is missing or fails
      to load, x-on:error hides the image and the initial shows. Nothing breaks.
    - Needs Alpine (already on every Admin page). Purely presentational: no backend or schema change.

    Usage: <x-admin.avatar :user="$user" />
           <x-admin.avatar :user="$user" size="h-16 w-16" text="text-xl" />
           <x-admin.avatar :name="$row['name']" />      (no model: shows the initial only)
--}}
@php
    $label = trim((string) ($user->name ?? $name ?? ''));
    $initial = strtoupper(mb_substr($label !== '' ? $label : '?', 0, 1));
    $picture = $user->profile_picture ?? null;
    $src = $picture ? asset('storage/' . ltrim((string) $picture, '/')) : null;
@endphp

<span {{ $attributes->class(['relative inline-flex flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#EFE4F1] font-semibold text-[#5b2963]', $size, $text]) }}
    @if ($src) x-data="{ broken: false }" @endif>
    <span aria-hidden="true">{{ $initial }}</span>
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async"
            x-show="!broken" x-on:error="broken = true"
            class="absolute inset-0 h-full w-full bg-white object-cover">
    @endif
</span>