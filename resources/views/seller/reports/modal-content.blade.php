<div class="sw-report-preview">
    <div class="sw-report-preview__brand"><img src="{{ asset('assets/branding/vendo-logo@2x.png') }}" alt="Vendo"><div><strong>{{ $periodLabel }} Seller Report</strong><small>{{ $seller->sellerDetail?->business_name ?? $seller->name }} · {{ $start->format('M j, Y g:i A') }} – {{ $end->format('M j, Y g:i A') }}</small></div></div>
    <h3>Sales Summary</h3>
    <div class="sw-report-preview__stats"><div><span>Gross product revenue</span><strong>₱{{ number_format($stats['gross'], 2) }}</strong></div><div><span>Delivered orders</span><strong>{{ number_format($stats['orders']) }}</strong></div><div><span>Units sold</span><strong>{{ number_format($stats['units']) }}</strong></div><div><span>Average order value</span><strong>{{ $stats['average'] === null ? '—' : '₱'.number_format($stats['average'], 2) }}</strong></div><div><span>Shipping charged</span><strong>₱{{ number_format($stats['shipping'], 2) }}</strong></div></div>
    <h3>Sales by {{ $period === 'today' ? 'hour' : ($period === 'year' ? 'month' : 'day') }}</h3>
    <div class="sw-table-wrap"><table class="sw-table"><thead><tr><th>Period</th><th>Product revenue</th></tr></thead><tbody>@foreach($chart as $point)<tr><td>{{ $point['label'] }}</td><td>₱{{ number_format($point['value'], 2) }}</td></tr>@endforeach</tbody></table></div>
    <h3>Top products</h3>
    <div class="sw-table-wrap"><table class="sw-table"><thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead><tbody>@forelse($topProducts as $item)<tr><td>{{ $item->product?->name ?? 'Unavailable product' }}</td><td>{{ $item->units_sold }}</td><td>₱{{ number_format($item->revenue, 2) }}</td></tr>@empty<tr><td colspan="3">No sales in this period.</td></tr>@endforelse</tbody></table></div>
    <p class="sw-footnote">{{ $outcomes['returned'] }} returned · {{ $outcomes['cancelled'] }} cancelled · {{ $outcomes['delivery_failed'] }} failed delivery. Generated {{ $generatedAt->format('M j, Y g:i A') }} (Asia/Manila). Totals include all qualifying orders.</p>
    <a class="sw-button" href="{{ route('seller.reports.download', ['period' => $period]) }}">Download this PDF</a>
</div>
