@if(request()->ajax())
    @include('seller.feedback.partials.detail')
@else
    <x-seller.layout title="Review">
        @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/feedback.css', 'resources/js/seller/operations.js', 'resources/js/seller/delivered-orders.js', 'resources/js/seller/feedback.js'])
        <section class="ops-page do-page fb-page">
            <header class="do-head">
                <div><h1>Review</h1><p>From order #{{ $review->order?->number ?? $review->order_id }}</p></div>
                <a class="do-btn do-btn--soft" href="{{ route('seller.feedback.index') }}">Back to feedback</a>
            </header>
            <div class="do-card">@include('seller.feedback.partials.detail')</div>
        </section>
        @include('seller.operations.drawer')
    </x-seller.layout>
@endif
