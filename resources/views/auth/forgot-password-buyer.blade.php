<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Restoration - Vendo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#f5f0eb]">

    <div class="min-h-screen flex flex-col lg:flex-row">

        {{-- ─── LEFT PANEL ─────────────────────────────────────────────────────── --}}
        <div class="hidden lg:flex lg:w-[45%] bg-[#3b1735] flex-col items-center justify-center px-16 py-20">
            <div class="flex flex-col items-center text-center">
                <img
                    src="{{ asset('assets/branding/log-in-logo.svg') }}"
                    alt="Vendo"
                    class="w-full max-w-[340px] mx-auto mb-14"
                >
                <h1 class="text-[2.6rem] font-bold text-white leading-tight mb-2 tracking-tight">
                    Welcome Back!
                </h1>
                <p class="text-[1rem] font-light text-white/70 tracking-wide">
                    Find What You Love. Vendo It.
                </p>
            </div>
        </div>

        {{-- ─── RIGHT PANEL ─────────────────────────────────────────────────────── --}}
        <div class="w-full lg:w-[55%] flex items-center justify-center bg-[#f5f0eb] px-4 py-12 sm:px-8">

            <div class="w-full max-w-[460px]">

                {{-- Mobile logo --}}
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="bg-[#3b1735] rounded-2xl px-8 py-5">
                        <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-36">
                    </div>
                </div>

                {{-- Back button (outside card) --}}
                <div class="mb-4">
                    <a href="{{ route('buyer.login') }}"
                       class="inline-flex items-center gap-1.5 text-[0.78rem] text-gray-600 border border-gray-300 rounded-lg px-4 py-1.5 bg-white hover:bg-gray-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back
                    </a>
                </div>

                {{-- Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-9">

                    <h2 class="text-[1.35rem] font-bold text-gray-900 mb-1 tracking-tight">Password Restoration</h2>
                    <p class="text-[0.78rem] text-gray-500 mb-6 font-normal leading-snug">
                        Enter your email and you will receive reset instructions.
                    </p>

                    {{-- Session status --}}
                    @if (session('status'))
                        <div class="mb-4 text-sm text-green-600 font-medium">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-5">
                            <label for="email" class="block text-[0.78rem] font-medium text-gray-700 mb-1.5">
                                Email Address
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="admin@vendo.com"
                                required
                                autofocus
                                class="w-full rounded-lg border border-gray-200 bg-[#f7f7f8] text-gray-800 text-[0.85rem] placeholder-gray-400 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30 focus:border-[#3b1735] transition @error('email') border-red-400 @enderror"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="w-full bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.9rem] font-semibold rounded-lg py-3 tracking-wide transition-colors duration-150"
                        >
                            Forgot Password
                        </button>

                    </form>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
