@php
    $unit = $period === 'today' ? 'hour' : ($period === 'year' ? 'month' : 'day');
    $sales = collect($chart)->filter(fn ($p) => $p['value'] > 0);
@endphp
<div class="rp-sheet">
    <div class="rp-sheet__brand">
        <img src="{{ asset('assets/branding/vendo-logo@2x.png') }}" alt="Vendo">
        <div>
            <strong>{{ $periodLabel }} seller report</strong>
            <small>{{ $seller->sellerDetail?->business_name ?? $seller->name }} · {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</small>
        </div>
    </div>

    <dl class="rp-sheet__stats">
        <div><dt>Product revenue</dt><dd>₱{{ number_format($stats['gross'], 2) }}</dd></div>
        <div><dt>Delivered orders</dt><dd>{{ number_format($stats['orders']) }}</dd></div>
        <div><dt>Units sold</dt><dd>{{ number_format($stats['units']) }}</dd></div>
        <div><dt>Average order</dt><dd>{{ $stats['average'] === null ? '—' : '₱'.number_format($stats['average'], 2) }}</dd></div>
        <div><dt>Shipping charged</dt><dd>₱{{ number_format($stats['shipping'], 2) }}</dd></div>
    </dl>

    <section>
        <h3>Sales by {{ $unit }}</h3>
        @if($sales->isEmpty())
            <p class="do-note">No sales in this period.</p>
        @else
            <div class="ops-table-scroll"><table class="rp-table"><thead><tr><th>{{ ucfirst($unit) }}</th><th>Product revenue</th></tr></thead><tbody>
                @foreach($sales as $point)<tr><td>{{ $point['label'] }}</td><td>₱{{ number_format($point['value'], 2) }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>

    <section>
        <h3>Top products</h3>
        @if($topProducts->isEmpty())
            <p class="do-note">No product sales in this period.</p>
        @else
            <div class="ops-table-scroll"><table class="rp-table"><thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead><tbody>
                @foreach($topProducts as $item)<tr><td>{{ $item->product?->name ?? 'Product removed' }}</td><td>{{ number_format($item->units_sold) }}</td><td>₱{{ number_format($item->revenue, 2) }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>

    <p class="rp-sheet__foot">{{ $outcomes['cancelled'] }} cancelled · {{ $outcomes['delivery_failed'] }} failed delivery. Generated {{ $generatedAt->format('M j, Y g:i A') }} (Manila time). Revenue is the product subtotal, not your payout.</p>

    <div class="rp-sheet__actions">
        <button type="button" class="do-btn do-btn--soft" data-rp-close>Close preview</button>
        <a class="do-btn do-btn--primary" href="{{ route('seller.reports.download', ['period' => $period]) }}" data-rp-download>
            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
            Download PDF
        </a>
    </div>
</div>
