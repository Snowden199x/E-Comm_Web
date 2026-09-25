@if(request()->ajax())
    @include('seller.feedback.partials.detail')
@else
    <x-seller.layout title="Feedback Details">
        @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
        <section class="ops-page"><header class="ops-page-head"><div><h1>Feedback Details</h1><p>Review from order #{{ $review->order_id }}</p></div><a class="ops-button" href="{{ route('seller.feedback.index') }}">Back to feedback</a></header><div class="ops-card">@include('seller.feedback.partials.detail')</div></section>
        @include('seller.operations.drawer')
    </x-seller.layout>
@endif
