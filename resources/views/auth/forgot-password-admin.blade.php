<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Restoration - Vendo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Left Panel (desktop only) -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#3b1735] flex-col items-center justify-center text-white px-12 py-16">
            <div class="max-w-md text-center">
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-full max-w-lg mx-auto mb-12">
                <h1 class="text-4xl xl:text-5xl font-bold mb-3">Welcome Back!</h1>
                <p class="text-lg text-gray-300">Sign in to access the admin console</p>
            </div>
        </div>

        <!-- Right Panel (form) -->
        <div class="w-full lg:w-1/2 flex-1 flex items-center justify-center bg-[#faf6f0] px-4 py-12 sm:px-6">

            <div class="w-full max-w-md">

                <!-- Mobile-only logo badge -->
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="bg-[#3b1735] rounded-2xl px-8 py-6">
                        <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-40">
                    </div>
                </div>

                <!-- Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">

                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <span>&lt;</span> Back
                    </a>

                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Password Restoration</h2>
                    <p class="text-sm text-gray-500 mb-6">Enter your email and you will receive reset instructions.</p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="admin@vendo.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mt-6">
                            <x-primary-button class="w-full justify-center bg-[#3b1735] hover:bg-[#4d1f45]">
                                {{ __('Forgot Password') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</body>
</html>