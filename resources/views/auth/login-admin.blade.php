<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Vendo</title>
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

                <!-- Mobile-only logo badge (left panel is hidden below lg breakpoint) -->
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="bg-[#3b1735] rounded-2xl px-8 py-6">
                        <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-40">
                    </div>
                </div>

                <!-- Login Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Admin Login</h2>
                    <p class="text-sm text-gray-500 mb-6">Enter your credentials to access the admin console.</p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@vendo.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                <x-input-label for="password" :value="__('Password')" />
                                @if (Route::has('password.request'))
                                    <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                                        {{ __('Forgot password?') }}
                                    </a>
                                @endif
                            </div>
                            <div class="relative mt-1" x-data="{ showPassword: false }">
                                <x-text-input id="password" class="block w-full pr-10"
                                                :type="'password'"
                                                x-bind:type="showPassword ? 'text' : 'password'"
                                                name="password"
                                                required autocomplete="current-password" />
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <img x-show="!showPassword" src="{{ asset('assets/icons/dashboard/see-password.svg') }}" alt="Show password" class="w-5 h-5">
                                    <img x-show="showPassword" src="{{ asset('assets/icons/dashboard/hide-password.svg') }}" alt="Hide password" class="w-5 h-5" x-cloak>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="block mt-4">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-[#3b1735] shadow-sm focus:ring-[#3b1735]" name="remember">
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        <div class="mt-6">
                            <x-primary-button class="w-full justify-center bg-[#3b1735] hover:bg-[#4d1f45]">
                                {{ __('Login') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</body>
</html>