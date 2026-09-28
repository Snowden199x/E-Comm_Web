@if (config('services.google.web_client_id'))
    <div class="w-full" data-google-signin
        data-client-id="{{ config('services.google.web_client_id') }}"
        data-exchange-url="{{ route('auth.google.exchange') }}"
        data-csrf="{{ csrf_token() }}"
        data-role="{{ $role }}">
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
