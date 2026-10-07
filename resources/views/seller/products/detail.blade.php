{{-- Loaded into the product modal via AJAX; a direct visit (e.g. from a notification) gets the full page shell. --}}
@if(request()->ajax())
    @include('seller.products.partials.detail-body')
@else
    <x-seller.layout title="Product Details">
        @vite(['resources/css/seller/products.css', 'resources/js/seller/products.js'])
        <section class="pi-page">
            <header class="pi-head"><div><h1>Product Details</h1></div><a class="pi-btn" href="{{ route('seller.products.index') }}">← Back to Products</a></header>
            <div class="pi-card pi-static">@include('seller.products.partials.detail-body')</div>
            <div class="pi-toast" id="piToast" role="status" aria-live="polite"></div>
        </section>
    </x-seller.layout>
@endif