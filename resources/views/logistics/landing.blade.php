<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendo Logistics — Partner With Us</title>
    <meta name="description" content="Become a Vendo Logistics partner. Handle deliveries for Vendo sellers and buyers at scale.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Poppins', sans-serif; }

        .lg-blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
            pointer-events: none;
        }
        .lg-blob-1 {
            width: 420px; height: 420px; top: -140px; right: -80px;
            background: radial-gradient(circle at 30% 30%, rgba(201,147,58,0.45), rgba(201,147,58,0) 70%);
        }
        .lg-blob-2 {
            width: 360px; height: 360px; bottom: -160px; left: -100px;
            background: radial-gradient(circle at 60% 60%, rgba(168,101,201,0.35), rgba(168,101,201,0) 70%);
        }
    </style>
</head>
<body class="antialiased bg-white text-gray-800">

    {{-- ─── NAV ─────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 h-[72px] flex items-center justify-between">
            <a href="{{ route('logistics.landing') }}">
                <img src="{{ asset('assets/branding/vendo-logo.svg') }}" alt="Vendo" class="h-8">
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('logistics.login') }}"
                   class="text-[0.85rem] font-semibold text-gray-600 hover:text-[#3b1735] transition px-2">
                    Log In
                </a>
                <a href="{{ route('logistics.register') }}"
                   class="bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.85rem] font-semibold rounded-full px-5 py-2.5 transition-all duration-150 hover:shadow-md">
                    Apply Now
                </a>
            </div>
        </div>
    </header>

    {{-- ─── HERO ────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden bg-[#3b1735]">
        <div class="lg-blob lg-blob-1"></div>
        <div class="lg-blob lg-blob-2"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-10 py-20 lg:py-28 grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <span class="inline-flex items-center gap-2 bg-white/10 text-[#e9c98a] text-[0.78rem] font-semibold rounded-full px-4 py-1.5 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#e9c98a]"></span>
                    Vendo Logistics
                </span>
                <h1 class="text-[2.4rem] lg:text-[3rem] font-bold text-white leading-[1.15] mb-5">
                    Become a Vendo<br>Logistics Provider.
                </h1>
                <p class="text-[1rem] text-white/70 leading-relaxed max-w-lg mb-8">
                    Handle deliveries for Vendo's growing network of sellers and buyers. Register your business,
                    get verified, and start receiving delivery assignments matched to your coverage area.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('logistics.register') }}"
                       class="bg-white text-[#3b1735] text-[0.9rem] font-semibold rounded-full px-7 py-3.5 transition-all duration-150 hover:shadow-lg hover:scale-[1.02]">
                        Apply Now
                    </a>
                    <a href="{{ route('logistics.login') }}"
                       class="border border-white/35 text-white text-[0.9rem] font-semibold rounded-full px-7 py-3.5 transition hover:bg-white/10">
                        Log In
                    </a>
                </div>
            </div>

            <div class="hidden lg:flex justify-center">
                <div class="bg-white/[0.06] border border-white/10 rounded-3xl p-8 w-full max-w-sm backdrop-blur-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-11 h-11 rounded-full bg-[#e9c98a]/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#e9c98a]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="1" y="6" width="15" height="12" rx="2"/><path d="M16 10h3l3 3v5h-6z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-[0.9rem]">Assignments routed</p>
                            <p class="text-white/50 text-[0.75rem]">Metro Manila coverage</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-white/80 text-[0.82rem]">
                            <span class="w-2 h-2 rounded-full bg-[#e9c98a] shrink-0"></span>
                            Pickup requests from sellers
                        </div>
                        <div class="border-l border-dashed border-white/25 ml-[3px] h-4"></div>
                        <div class="flex items-center gap-3 text-white/80 text-[0.82rem]">
                            <span class="w-2 h-2 rounded-full bg-white/40 shrink-0"></span>
                            Delivered to buyers
                        </div>
                    </div>
                    <div class="mt-6 pt-5 border-t border-white/10 flex items-center justify-between">
                        <span class="text-white/50 text-[0.78rem]">Payout basis</span>
                        <span class="text-[#e9c98a] font-semibold text-[0.9rem]">Per completed delivery</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── BENEFITS ────────────────────────────────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-10 py-20">
        <div class="max-w-xl mb-12">
            <span class="text-[0.78rem] font-semibold text-[#3b1735] uppercase tracking-wide">Why partner with us</span>
            <h2 class="text-[1.9rem] font-bold text-gray-900 mt-2">Built for logistics businesses, not one-off riders.</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
                $benefits = [
                    ['icon' => 'M12 7v5l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Steady order volume', 'desc' => 'Tap into a growing base of Vendo sellers and buyers who need reliable delivery.'],
                    ['icon' => 'M12 7v10M9 9.5c0-1.4 1.3-2.5 3-2.5s3 1.1 3 2.5-1.3 2-3 2.5-3 1.1-3 2.5 1.3 2.5 3 2.5 3-1.1 3-2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Transparent payouts', 'desc' => 'Clear, trackable earnings for every delivery your team completes.'],
                    ['icon' => 'M12 21s-7-6.5-7-11.5A7 7 0 0112 2a7 7 0 017 7.5C19 14.5 12 21 12 21zM12 9.5a2.3 2.3 0 100 4.6 2.3 2.3 0 000-4.6z', 'title' => 'Assignments by coverage area', 'desc' => 'Get matched with pickups and drop-offs within the area your business serves.'],
                    ['icon' => 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Straightforward onboarding', 'desc' => 'Apply online, submit your business documents, and start once you\'re verified.'],
                    ['icon' => 'M3 21h18M4 21V8l8-5 8 5v13M9 21v-6h6v6', 'title' => 'Any fleet size welcome', 'desc' => 'Whether you run a few vehicles or a full fleet, your business can apply.'],
                    ['icon' => 'M18 10a6 6 0 10-12 0c0 5 6 11 6 11s6-6 6-11zM12 2v2', 'title' => 'Support when you need it', 'desc' => 'Our team is here to help with onboarding questions and day-to-day concerns.'],
                ];
            @endphp
            @foreach ($benefits as $benefit)
                <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-gray-200 transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-[#f3edf7] flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-[#3b1735]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefit['icon'] }}"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-[1rem] mb-1.5">{{ $benefit['title'] }}</h3>
                    <p class="text-gray-500 text-[0.85rem] leading-relaxed">{{ $benefit['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ─── HOW IT WORKS ────────────────────────────────────────────────── --}}
    <section class="bg-[#faf7f3] py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">
            <div class="max-w-xl mb-12">
                <span class="text-[0.78rem] font-semibold text-[#3b1735] uppercase tracking-wide">Getting started</span>
                <h2 class="text-[1.9rem] font-bold text-gray-900 mt-2">From application to your first assignment.</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $steps = [
                        ['num' => '01', 'title' => 'Apply online', 'desc' => 'Fill out the registration form with your business and authorized representative details.'],
                        ['num' => '02', 'title' => 'Submit documents', 'desc' => 'Upload a valid ID and your business permit.'],
                        ['num' => '03', 'title' => 'Get verified', 'desc' => 'Our team reviews your application and notifies you by email.'],
                        ['num' => '04', 'title' => 'Start operating', 'desc' => 'Log in and start receiving delivery assignments in your coverage area.'],
                    ];
                @endphp
                @foreach ($steps as $step)
                    <div>
                        <div class="text-[1.6rem] font-bold text-[#c9933a] mb-2">{{ $step['num'] }}</div>
                        <h3 class="font-bold text-gray-900 text-[1rem] mb-1.5">{{ $step['title'] }}</h3>
                        <p class="text-gray-500 text-[0.85rem] leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── REQUIREMENTS ────────────────────────────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-10 py-20">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <span class="text-[0.78rem] font-semibold text-[#3b1735] uppercase tracking-wide">Before you apply</span>
                <h2 class="text-[1.9rem] font-bold text-gray-900 mt-2 mb-4">What you'll need on hand.</h2>
                <p class="text-gray-500 text-[0.9rem] leading-relaxed max-w-md">
                    Having these ready before you start the application makes the process faster.
                    Files can be JPEG, PNG, or PDF.
                </p>
            </div>

            <div class="space-y-3">
                @php
                    $requirements = [
                        'A valid government-issued ID of your authorized representative',
                        'Your business permit',
                        'Basic business details — name, complete address, and contact number',
                    ];
                @endphp
                @foreach ($requirements as $requirement)
                    <div class="flex items-start gap-3 bg-white border border-gray-100 rounded-xl px-5 py-4 shadow-sm">
                        <div class="w-6 h-6 rounded-full bg-[#3b1735] flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-gray-700 text-[0.88rem] leading-snug">{{ $requirement }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── INDIVIDUAL RIDER CALLOUT ───────────────────────────────────────── --}}
    <section class="bg-[#faf7f3] py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">
            <div class="bg-white border border-gray-100 rounded-3xl p-8 lg:p-12 grid lg:grid-cols-[1fr_auto] gap-10 items-center">
                <div>
                    <span class="text-[0.78rem] font-semibold text-[#3b1735] uppercase tracking-wide">Looking to ride, not run a business?</span>
                    <h2 class="text-[1.6rem] lg:text-[1.8rem] font-bold text-gray-900 mt-2 mb-3">
                        Vendo Rider is available on our mobile app.
                    </h2>
                    <p class="text-gray-500 text-[0.9rem] leading-relaxed max-w-lg mb-6">
                        This page is for registering a logistics business. If you want to deliver as an individual rider,
                        download the Vendo Rider app to apply, get verified, and start accepting deliveries.
                    </p>
                    <a href="{{ route('logistics.rider-app') }}"
                       class="inline-flex items-center gap-2 bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.88rem] font-semibold rounded-full px-6 py-3 transition-all duration-150 hover:shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path stroke-linecap="round" d="M11 18h2"/></svg>
                        Get the Vendo Rider App
                    </a>
                </div>

                <div class="flex flex-col items-center gap-3 justify-self-center">
                    <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm">
                        <img src="{{ asset('assets/logistics/rider-app-qr.png') }}" alt="Scan to get the Vendo Rider app" class="w-32 h-32">
                    </div>
                    <p class="text-[0.7rem] text-gray-400 text-center max-w-[140px]">Scan to download the Vendo Rider app</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── FINAL CTA ───────────────────────────────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-10 pb-20">
        <div class="bg-[#3b1735] rounded-3xl px-8 py-14 lg:py-16 text-center relative overflow-hidden">
            <h2 class="text-[1.8rem] lg:text-[2.1rem] font-bold text-white mb-3">Ready to start earning with Vendo?</h2>
            <p class="text-white/70 text-[0.95rem] mb-8 max-w-md mx-auto">
                Applications are reviewed by our team — apply now and we'll notify you by email once you're approved.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('logistics.register') }}"
                   class="bg-white text-[#3b1735] text-[0.9rem] font-semibold rounded-full px-8 py-3.5 transition-all duration-150 hover:shadow-lg hover:scale-[1.02]">
                    Apply Now
                </a>
                <a href="{{ route('logistics.login') }}"
                   class="border border-white/35 text-white text-[0.9rem] font-semibold rounded-full px-8 py-3.5 transition hover:bg-white/10">
                    Already a partner? Log In
                </a>
            </div>
        </div>
    </section>

    {{-- ─── FOOTER ──────────────────────────────────────────────────────── --}}
    <footer class="border-t border-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('logistics.landing') }}">
                <img src="{{ asset('assets/branding/vendo-logo.svg') }}" alt="Vendo" class="h-6 opacity-80">
            </a>
            <p class="text-[0.8rem] text-gray-400">&copy; {{ date('Y') }} Vendo. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>