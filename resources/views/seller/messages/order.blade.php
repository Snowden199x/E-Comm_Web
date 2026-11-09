<x-seller.layout title="Order Messages">
    @vite(['resources/css/shared/marketplace-chat.css', 'resources/css/shared/user-report.css', 'resources/css/seller/messages.css'])
    <div class="mc-page mc-page--seller">
        @include('shared.marketplace-thread')
    </div>
</x-seller.layout>
