@php($signInRole = $role ?? null)
@if (config('services.google.web_client_id'))
    {{-- A page that serves more than one account type omits the role and lets the
         server read it from the verified email; a role-scoped page keeps sending
         it so an account cannot sign in through the wrong login page. --}}
    <div class="w-full" data-google-signin
        data-client-id="{{ config('services.google.web_client_id') }}"
        data-exchange-url="{{ route('auth.google.exchange', absolute: false) }}"
        data-csrf="{{ csrf_token() }}"
        @if ($signInRole) data-role="{{ $signInRole }}" @endif>
        <div data-google-button class="flex min-h-11 w-full justify-center"></div>
        <p data-google-error class="mt-2 hidden text-center text-sm text-red-700" role="alert"></p>
    </div>
    <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
    @vite('resources/js/auth/google-signin.js')
@else
    <div class="w-full rounded-md border border-gray-200 bg-white px-3 py-3 text-center text-sm text-gray-500">
        Continue with Google (OAuth client ID not configured)
    </div>
@endif
