@if(request()->ajax())
    @include('seller.completed-orders.partials.detail')
@else
    <x-seller.layout title="Delivered Order Details">
        @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/js/seller/operations.js', 'resources/js/seller/delivered-orders.js'])
        <section class="ops-page do-page">
            <header class="do-head">
                <div><h1>Order #{{ $order->number }}</h1><p>Delivered order details.</p></div>
                <a href="{{ route('seller.completed-orders.index') }}" class="do-btn do-btn--soft">Back to delivered orders</a>
            </header>
            <div class="do-card">@include('seller.completed-orders.partials.detail')</div>
        </section>
        @include('seller.operations.drawer')
    </x-seller.layout>
@endif
