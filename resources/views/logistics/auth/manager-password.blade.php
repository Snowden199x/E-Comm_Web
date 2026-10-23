<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
    <title>Create Your Password - Vendo Logistics</title>
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
</head>
<body class="min-h-screen bg-[#f5f0eb] text-gray-900 antialiased">
    <main class="min-h-screen flex flex-col lg:flex-row">
        <section class="hidden lg:flex lg:w-[45%] flex-col items-center justify-center bg-[#3b1735] px-12 py-16 text-white">
            <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="mb-10 w-full max-w-sm">
            <h1 class="text-3xl font-semibold">Secure your manager account</h1>
            <p class="mt-3 max-w-sm text-center text-sm leading-6 text-white/70">Choose a personal password before opening your Logistics workspace.</p>
        </section>

        <section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-md rounded-xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 lg:hidden">
                    <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-36">
                </div>
                <h2 class="text-2xl font-semibold">Create your password</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Choose a password for {{ $user->email }}. This secure setup link can only be used once.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('lgp.manager-password.store', ['token' => $token]) }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">New password</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" autofocus
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm outline-none transition focus:border-[#70436c] focus:ring-2 focus:ring-[#70436c]/20">
                        <p class="mt-1.5 text-xs text-gray-500">Use at least 8 characters, including letters and numbers.</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm outline-none transition focus:border-[#70436c] focus:ring-2 focus:ring-[#70436c]/20">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-[#3b1735] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#4d1f45] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30 focus:ring-offset-2">
                        Save password and continue
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
