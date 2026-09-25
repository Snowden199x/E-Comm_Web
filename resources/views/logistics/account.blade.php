<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Account Management | Vendo Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="min-h-screen bg-[#faf6f0] text-gray-900">
    <header class="sticky top-0 z-20 border-b border-gray-100 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <a href="{{ route('logistics.dashboard') }}" class="flex items-center gap-3" aria-label="Logistics dashboard">
                <img src="{{ asset('assets/branding/vendo-logo.svg') }}" alt="Vendo" class="h-7">
                <span class="hidden border-l border-gray-200 pl-3 text-sm font-semibold text-gray-600 sm:inline">Logistics Center Portal</span>
            </a>
            <nav class="flex items-center gap-4 text-sm" aria-label="Account navigation">
                <a href="{{ route('logistics.dashboard') }}" class="font-medium text-[#5c2864] hover:underline">Dashboard</a>
                <form method="POST" action="{{ route('logistics.logout') }}">@csrf<button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-gray-600 hover:bg-gray-50">Logout</button></form>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div><h1 class="text-2xl font-bold">Account Management</h1><p class="mt-1 text-sm text-gray-500">Your registered details and platform policies.</p></div>
            <a href="#accountPolicies" class="rounded-lg border border-[#d8c8dc] bg-white px-3 py-2 text-sm font-medium text-[#5c2864] hover:bg-[#f8f1f9]">Policies</a>
        </div>
        <section class="rounded-2xl border border-gray-100 bg-white p-5 sm:p-6" aria-labelledby="logisticsProfileTitle">
            <div class="flex items-center gap-4 border-b border-gray-100 pb-5">
                @if($user->profile_picture)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_picture) }}" alt="" class="h-14 w-14 rounded-full object-cover">
                @else<span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#f2eaf4] text-xl font-semibold text-[#5c2864]" aria-hidden="true">{{ mb_strtoupper(mb_substr($center?->business_name ?: $user->name, 0, 1)) }}</span>@endif
                <div class="min-w-0"><h2 id="logisticsProfileTitle" class="break-words text-lg font-semibold">{{ $center?->business_name ?: $user->name }}</h2><p class="mt-1 text-sm text-gray-500">Logistics center</p></div>
            </div>
            <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">Registered name</dt><dd class="mt-1 break-words font-medium">{{ $user->name }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="mt-1 break-words font-medium">{{ $user->email }}</dd></div>
                <div><dt class="text-gray-500">Contact number</dt><dd class="mt-1 font-medium">{{ $user->phone_number ?: 'Not provided' }}</dd></div>
                <div><dt class="text-gray-500">Account status</dt><dd class="mt-1 font-medium">{{ ucfirst($user->status) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-gray-500">Registered address</dt><dd class="mt-1 break-words font-medium">{{ collect([$center?->house_no, $center?->street, $center?->barangay, $center?->municipality, $center?->province, $center?->zip_code])->filter()->implode(', ') ?: 'Not provided' }}</dd></div>
            </dl>
        </section>
        <x-account-policies :policies="$policies" />
    </main>
</body>
</html>
