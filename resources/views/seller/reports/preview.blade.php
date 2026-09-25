<x-seller.layout title="Report Preview">
    @vite('resources/css/seller/workspace.css')
    <section class="sw-page">
        <header class="sw-heading"><div><a class="sw-link" href="{{ route('seller.reports.index', ['period' => $period]) }}">← Reports</a><h1>{{ $periodLabel }} Report</h1><p>{{ $seller->sellerDetail?->business_name ?? $seller->name }} · {{ $start->format('M j, Y g:i A') }} – {{ $end->format('M j, Y g:i A') }}</p></div><a class="sw-button" href="{{ route('seller.reports.download', ['period' => $period]) }}">Download PDF</a></header>
        @include('seller.reports.summary')
        <p class="sw-footnote">Generated {{ $generatedAt->format('M j, Y g:i A') }} (Asia/Manila). Revenue is the product subtotal; shipping is shown separately. The order list shows up to 100 entries.</p>
    </section>
</x-seller.layout>
