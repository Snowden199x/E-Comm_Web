<x-seller.layout title="Report Preview">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/reports.css', 'resources/js/seller/delivered-orders.js'])
    <section class="ops-page do-page rp-page">
        <header class="do-head">
            <div><h1>{{ $periodLabel }} report</h1><p>{{ $seller->sellerDetail?->business_name ?? $seller->name }} · {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</p></div>
            <div class="rp-actions">
                <a class="do-btn do-btn--soft" href="{{ route('seller.reports.index', ['period' => $period]) }}">Back to reports</a>
                <a class="do-btn do-btn--primary" href="{{ route('seller.reports.download', ['period' => $period]) }}">Download PDF</a>
            </div>
        </header>
        @include('seller.reports.summary')
    </section>
</x-seller.layout>
