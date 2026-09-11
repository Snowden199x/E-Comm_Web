<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Vendo</title>
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

                {{-- Logo + wordmark --}}
                <img
                    src="{{ asset('assets/branding/log-in-logo.svg') }}"
                    alt="Vendo"
                    class="w-full max-w-[340px] mx-auto mb-14"
                >

                {{-- Welcome copy --}}
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

                {{-- Login Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-9">

                    {{-- Heading --}}
                    <h2 class="text-[1.6rem] font-bold text-gray-900 mb-1 tracking-tight">Login</h2>
                    <p class="text-[0.8rem] text-gray-500 mb-7 font-normal leading-snug">
                        Enter your credentials to access your Vendo account.
                    </p>

                    {{-- Session status --}}
                    @if (session('status'))
                        <div class="mb-4 text-sm text-green-600 font-medium">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-4">
                            <label for="email" class="block text-[0.78rem] font-medium text-gray-700 mb-1.5">
                                Email Address
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="user@gmail.com"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full rounded-lg border border-gray-200 bg-[#f7f7f8] text-gray-800 text-[0.85rem] placeholder-gray-400 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30 focus:border-[#3b1735] transition @error('email') border-red-400 @enderror"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="text-[0.78rem] font-medium text-gray-700">
                                    Password
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('buyer.password.request') }}"
                                       class="text-[0.78rem] text-gray-500 hover:text-[#3b1735] transition font-normal">
                                        Forgot password?
                                    </a>
                                @endif
                            </div>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    placeholder="••••••••••••"
                                    required
                                    autocomplete="current-password"
                                    class="w-full rounded-lg border border-gray-200 bg-[#f7f7f8] text-gray-800 text-[0.85rem] placeholder-gray-400 px-4 py-3 pr-11 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30 focus:border-[#3b1735] transition @error('password') border-red-400 @enderror"
                                >
                                {{-- Toggle password visibility --}}
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-gray-400 hover:text-gray-600 transition"
                                    tabindex="-1"
                                >
                                    {{-- Eye open --}}
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    {{-- Eye closed --}}
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Login button --}}
                        <button
                            type="submit"
                            class="w-full bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.9rem] font-semibold rounded-lg py-3 tracking-wide transition-colors duration-150"
                        >
                            Login
                        </button>

                        {{-- Divider --}}
                        <div class="flex items-center gap-3 my-5">
                            <div class="flex-1 h-px bg-gray-200"></div>
                            <span class="text-[0.78rem] text-gray-400 font-normal px-1 border border-gray-200 rounded-full w-7 h-7 flex items-center justify-center">or</span>
                            <div class="flex-1 h-px bg-gray-200"></div>
                        </div>

                        {{-- Continue with Google --}}
                        <a
                            href="#"
                            class="w-full flex items-center justify-center gap-3 border border-gray-200 rounded-lg py-3 bg-white hover:bg-gray-50 active:bg-gray-100 transition-colors duration-150"
                        >
                            {{-- Google "G" logo --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5 flex-shrink-0">
                                <path fill="#EA4335" d="M24 9.5c3.15 0 5.64 1.08 7.55 2.84l5.63-5.63C33.73 3.52 29.19 1.5 24 1.5 14.98 1.5 7.3 7.08 4.03 14.9l6.56 5.1C12.2 13.67 17.63 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.5 24.5c0-1.64-.15-3.22-.42-4.75H24v9h12.67c-.55 2.97-2.18 5.48-4.64 7.18l7.18 5.57C43.63 37.57 46.5 31.49 46.5 24.5z"/>
                                <path fill="#FBBC05" d="M10.59 28.99A14.52 14.52 0 019.5 24c0-1.74.3-3.42.83-4.99l-6.56-5.1A22.44 22.44 0 001.5 24c0 3.6.86 7.01 2.38 10.03l6.71-5.04z"/>
                                <path fill="#34A853" d="M24 46.5c5.19 0 9.55-1.72 12.73-4.67l-7.18-5.57c-1.79 1.2-4.08 1.91-5.55 1.91-6.37 0-11.8-4.17-13.74-9.99l-6.71 5.04C7.3 40.92 14.98 46.5 24 46.5z"/>
                                <path fill="none" d="M1.5 1.5h45v45h-45z"/>
                            </svg>
                            <span class="text-[0.88rem] font-medium text-gray-700">Continue with Google</span>
                        </a>

                    </form>

                    {{-- Register link --}}
                    <p class="text-center text-[0.78rem] text-gray-500 mt-6 font-normal">
                        Don't have an account?
                        <a href="{{ route('buyer.register') }}" class="text-[#3b1735] font-semibold hover:underline">
                            Register Here.
                        </a>
                    </p>

                </div>
            </div>
        </div>

    </div>

</body>
</html>
