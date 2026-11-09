@php
    $unit = $period === 'today' ? 'hour' : ($period === 'year' ? 'month' : 'day');
    $peak = collect($chart)->max('value');
    $hasSales = $peak > 0;
    $step = max(1, (int) ceil(count($chart) / 10));
    $short = fn ($v) => $v >= 1000000 ? '₱'.round($v / 1000000, 1).'M' : ($v >= 1000 ? '₱'.round($v / 1000, 1).'K' : '₱'.round($v));
    $topMax = max(1, (float) ($topProducts->max('revenue') ?? 0));
    $periodUrls = ['today' => 'today', 'week' => 'this week', 'month' => 'this month', 'year' => 'this year'];
    $cards = [
        ['Product revenue', '₱'.number_format($stats['gross'], 2), $deltas['gross'], 'butter', 'money'],
        ['Delivered orders', number_format($stats['orders']), $deltas['orders'], 'mint', 'check'],
        ['Units sold', number_format($stats['units']), $deltas['units'], 'sky', 'box'],
        ['Average order', $stats['average'] === null ? '—' : '₱'.number_format($stats['average'], 2), $deltas['average'], 'lilac', 'trend'],
    ];
@endphp
<div class="do-stats rp-stats" role="list">
    @foreach($cards as [$label, $value, $delta, $tone, $icon])
        <div class="do-stat" role="listitem">
            <span class="do-stat__icon do-tone-{{ $tone }}">
                @if($icon === 'money')<svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.600"/></svg>
                @elseif($icon === 'check')<svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 3 3 5-6"/></svg>
                @elseif($icon === 'box')<svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg>
                @else<svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8M15 7h6v6"/></svg>@endif
            </span>
            <span class="do-stat__text">{{ $label }}<strong>{{ $value }}</strong>
                @if($delta === null)<small class="rp-delta">No data {{ $compareLabel }}</small>
                @elseif($delta === 0)<small class="rp-delta">Same as {{ $compareLabel }}</small>
                @else<small class="rp-delta {{ $delta > 0 ? 'is-up' : 'is-down' }}"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $delta > 0 ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M5 12l7 7 7-7' }}"/></svg>{{ abs($delta) }}% vs {{ $compareLabel }}</small>@endif
            </span>
        </div>
    @endforeach
</div>

<div class="rp-outcomes" aria-label="Other outcomes in this period">
    <span>Other outcomes</span>
    <span class="do-badge do-badge--rose">Cancelled {{ number_format($outcomes['cancelled']) }}</span>
    <span class="do-badge do-badge--lilac">Failed delivery {{ number_format($outcomes['delivery_failed']) }}</span>
    @if(($outcomes['returned'] ?? 0) > 0)<span class="do-badge do-badge--butter">Returned {{ number_format($outcomes['returned']) }}</span>@endif
</div>

<div class="rp-grid">
    <section class="do-card">
        <header class="do-card__head"><h2>Sales by {{ $unit }}</h2><p>Product revenue by delivery time.</p></header>
        @if($hasSales)
            <div class="rp-chart" role="img" aria-label="Product revenue by {{ $unit }}. Peak {{ $short($peak) }}.">
                <div class="rp-chart__axis" aria-hidden="true"><span>{{ $short($chartMax) }}</span><span>{{ $short($chartMax / 2) }}</span><span>₱0</span></div>
                <div class="rp-chart__plot" style="--cols: {{ count($chart) }}">
                    @foreach($chart as $point)
                        <div class="rp-col @if($point['value'] > 0 && $point['value'] == $peak) is-peak @endif" tabindex="0" aria-label="{{ $point['label'] }}: ₱{{ number_format($point['value'], 2) }}">
                            <span class="rp-col__tip">{{ $point['label'] }}<b>₱{{ number_format($point['value'], 2) }}</b></span>
                            <i style="--h: {{ $point['value'] > 0 ? max(4, round($point['value'] / $chartMax * 100)) : 0 }}%; --d: {{ min($loop->index * 25, 600) }}ms"></i>
                            <small>{{ $loop->index % $step === 0 ? $point['label'] : '' }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="do-empty do-empty--compact">
                <span class="do-empty__icon do-tone-butter"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V10M12 20V4M19 20v-7"/></svg></span>
                <strong>No sales {{ $periodUrls[$period] }}</strong>
                <p>Revenue shows here once an order is delivered.</p>
                @if($nextPeriod)<a class="do-btn do-btn--soft" href="{{ route('seller.reports.index', ['period' => $nextPeriod]) }}">View {{ $periodUrls[$nextPeriod] }}</a>@endif
            </div>
        @endif
    </section>

    <section class="do-card">
        <header class="do-card__head"><h2>Top products</h2><p>Ranked by units sold.</p></header>
        @forelse($topProducts as $item)
            @php $thumb = $item->product?->images->first(); @endphp
            <div class="do-top">
                <span class="do-top__rank">{{ $loop->iteration }}</span>
                @if($thumb)<img src="{{ asset('storage/'.$thumb->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                <span class="do-top__body">
                    <strong>{{ $item->product?->name ?? 'Product removed' }}</strong>
                    <small>{{ number_format($item->units_sold) }} {{ \Illuminate\Support\Str::plural('unit', $item->units_sold) }} sold</small>
                    <span class="do-bar"><i style="--w: {{ round($item->revenue / $topMax * 100) }}%"></i></span>
                </span>
                <strong class="do-top__amount">₱{{ number_format($item->revenue, 2) }}</strong>
            </div>
        @empty
            <div class="do-empty do-empty--compact">
                <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>
                <strong>No product sales</strong>
                <p>Your best sellers show here.</p>
            </div>
        @endforelse
    </section>
</div>

<section class="do-card do-table-card rp-orders" aria-label="Delivered orders" data-do-table>
    <header class="rp-orders__head">
        <h2>Delivered orders</h2>
        <span>Shipping charged: <strong>₱{{ number_format($stats['shipping'], 2) }}</strong></span>
    </header>
    <div class="ops-table-scroll">
        <table class="do-table">
            <thead><tr><th>Order</th><th>Buyer</th><th>Delivered</th><th>Product subtotal</th><th>Shipping</th><th><span class="ops-sr-only">Actions</span></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="er-row">
                        <td><strong>#{{ $order->number }}</strong></td>
                        <td>{{ $order->buyer?->name ?? 'Buyer removed' }}</td>
                        <td>{{ $order->delivered_at?->format('M j, Y') ?? 'No date' }}<small>{{ $order->delivered_at?->format('g:i A') }}</small></td>
                        <td><strong>₱{{ number_format($order->total_amount, 2) }}</strong></td>
                        <td>₱{{ number_format($order->shipping_fee, 2) }}</td>
                        <td class="do-actions"><a class="do-btn do-btn--soft do-btn--small" href="{{ route('seller.completed-orders.show', $order) }}">View details<svg class="do-ico do-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="do-empty-cell">
                        <div class="do-empty">
                            <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><path d="m6.500 11.500 1.800 1.800 3-3.200"/></svg></span>
                            <strong>No delivered orders {{ $periodUrls[$period] }}</strong>
                            <p>Orders show here after the courier marks them delivered.</p>
                            @if($nextPeriod)<a class="do-btn do-btn--primary" href="{{ route('seller.reports.index', ['period' => $nextPeriod]) }}">View {{ $periodUrls[$nextPeriod] }}</a>
                            @else<a class="do-btn do-btn--primary" href="{{ route('seller.shipments.index') }}">View shipments</a>@endif
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders instanceof \Illuminate\Contracts\Pagination\Paginator && $orders->total() > 0)
        @include('seller.operations.pagination', ['records' => $orders])
    @endif
</section>
