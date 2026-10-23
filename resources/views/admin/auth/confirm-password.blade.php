<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
    <title>Confirm Password - Vendo Admin</title>
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
</head>

<body class="min-h-screen bg-[#faf6f0] text-[#241b22] antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-lg items-center px-5 py-10">
        <section class="w-full rounded-lg border border-[#e8e0e5] bg-white p-6 sm:p-8">
            <a href="{{ route('admin.dashboard') }}" class="mb-7 inline-flex items-center">
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="h-9 w-auto">
            </a>

            <h1 class="text-xl font-semibold">Confirm your password</h1>
            <p class="mt-2 text-sm leading-6 text-[#685d66]">
                For your security, enter your admin password before continuing.
            </p>

            <form method="POST" action="{{ route('admin.password.confirm') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                        required autofocus
                        class="block w-full rounded-md border border-[#d8cfd6] bg-white px-3 py-2.5 text-sm focus:border-[#642b59] focus:outline-none focus:ring-2 focus:ring-[#642b59]/20">
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-4 pt-1">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-[#5e3155] hover:underline">Back to dashboard</a>
                    <button type="submit"
                        class="rounded-md bg-[#3b1735] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#4d1f45] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/30">
                        Confirm password
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>
