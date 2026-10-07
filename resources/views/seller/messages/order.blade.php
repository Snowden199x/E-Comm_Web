<x-seller.layout title="Order Messages">
    @vite(['resources/css/shared/marketplace-chat.css', 'resources/css/shared/user-report.css'])
    <div class="mc-page mc-page--seller">
        @include('shared.marketplace-thread')
    </div>
</x-seller.layout>
