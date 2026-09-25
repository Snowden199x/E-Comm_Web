@if(request()->ajax())
    @include('seller.completed-orders.partials.detail')
@else
    <x-seller.layout title="Delivered Order Details">
        @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
        <section class="ops-page"><header class="ops-page-head"><div><h1>Delivered Order Details</h1><p>Order #{{ $order->number }}</p></div><a href="{{ route('seller.completed-orders.index') }}" class="ops-button">Back to delivered orders</a></header><div class="ops-card">@include('seller.completed-orders.partials.detail')</div></section>
        @include('seller.operations.drawer')
    </x-seller.layout>
@endif
