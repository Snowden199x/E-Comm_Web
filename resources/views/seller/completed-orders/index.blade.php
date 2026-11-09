@php
    $filtered = collect($filters)->except('page')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $payLabel = fn ($p) => strlen($p) <= 5 ? strtoupper($p) : \Illuminate\Support\Str::headline($p);
    $clearUrl = route('seller.completed-orders.index');
    $outcomeTotal = $outcomes['delivered'] + $outcomes['cancelled'] + $outcomes['delivery_failed'];
    $pct = fn ($n) => $outcomeTotal ? round($n / $outcomeTotal * 100) : 0;
    $deliveredEnd = $outcomeTotal ? round($outcomes['delivered'] / $outcomeTotal * 100, 1) : 0;
    $cancelledEnd = $outcomeTotal ? round(($outcomes['delivered'] + $outcomes['cancelled']) / $outcomeTotal * 100, 1) : 0;
    $chart = $outcomeTotal
        ? "conic-gradient(var(--do-mint-500) 0 {$deliveredEnd}%, var(--do-rose-500) {$deliveredEnd}% {$cancelledEnd}%, var(--do-lilac-500) {$cancelledEnd}% 100%)"
        : 'conic-gradient(var(--do-lilac-100) 0 100%)';
    $maxRevenue = max(1, (float) ($topProducts->max('revenue') ?? 0));
@endphp
<x-seller.layout title="Delivered Orders">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/js/seller/operations.js', 'resources/js/seller/delivered-orders.js'])
    <section class="ops-page do-page">
        <header class="do-head">
            <div>
                <h1>Delivered orders</h1>
                <p>Review finished deliveries and what they earned.</p>
            </div>
            <a class="do-btn do-btn--soft" href="{{ route('seller.shipments.index') }}">
                <svg class="do-ico do-ico--slide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.8 1.8 0 100-3.6A1.8 1.8 0 007 19zm10 0a1.8 1.8 0 100-3.6 1.8 1.8 0 000 3.6z"/></svg>
                View shipments
            </a>
        </header>

        <div class="do-stats" role="list">
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg></span>
                <span class="do-stat__text">Delivered orders<strong>{{ number_format($stats['orders']) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-butter"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5v.01M17.5 14.5v.01"/></svg></span>
                <span class="do-stat__text">Product revenue<strong>₱{{ number_format($stats['revenue'], 2) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-sky"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3 19c.6-3.4 3-5 6-5s5.4 1.6 6 5M16 5.2a3 3 0 010 5.6M18 14.4c1.7.6 2.8 2 3 4.6"/></svg></span>
                <span class="do-stat__text">Customers<strong>{{ number_format($stats['customers']) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-lilac"><svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 16a8 8 0 1116 0"/><path d="m12 16 4-5"/><circle cx="12" cy="16" r="1.2"/></svg></span>
                <span class="do-stat__text">Delivery rate<strong>{{ $stats['completion_rate'] }}%</strong></span>
            </div>
        </div>

        @if($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                <div>@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
                <a class="do-btn do-btn--small" href="{{ $clearUrl }}">Clear filters</a>
            </div>
        @endif
        @if(session('success'))<p hidden data-flash="{{ session('success') }}"></p>@endif

        <form method="GET" action="{{ $clearUrl }}" class="do-filters" data-do-filters>
            <label class="do-search">
                <span class="ops-sr-only">Search delivered orders</span>
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search order, buyer, product, or tracking no." maxlength="100" autocomplete="off">
            </label>
            <label>
                <span class="ops-sr-only">Courier</span>
                <select name="courier" data-auto-submit>
                    <option value="">All couriers</option>
                    <option value="0" @selected(($filters['courier'] ?? '') === '0')>Unassigned</option>
                    @foreach($couriers as $courier)<option value="{{ $courier->id }}" @selected(($filters['courier'] ?? '') == $courier->id)>{{ $courier->name }}</option>@endforeach
                </select>
            </label>
            <label>
                <span class="ops-sr-only">Payment method</span>
                <select name="payment" data-auto-submit>
                    <option value="">All payments</option>
                    @foreach($payments as $payment)<option value="{{ $payment }}" @selected(($filters['payment'] ?? '') === $payment)>{{ $payLabel($payment) }}</option>@endforeach
                </select>
            </label>
            <div class="do-range" role="group" aria-label="Delivery date range">
                <label>From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" data-range></label>
                <label>To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" data-range></label>
                <button class="do-btn do-btn--soft do-btn--small" type="submit" data-range-apply hidden>Apply dates</button>
            </div>
        </form>

        <div class="do-chips" aria-label="Quick date ranges">
            @foreach($presets as $preset)
                <a href="{{ $preset['url'] }}" class="do-chip @if($preset['active']) is-active @endif" @if($preset['active']) aria-current="true" @endif>{{ $preset['label'] }}</a>
            @endforeach
            @if($filtered)
                <a href="{{ $clearUrl }}" class="do-chip do-chip--clear">
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    Clear filters
                </a>
            @endif
        </div>

        <section class="do-card do-table-card" aria-label="Delivered orders" data-do-table>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Delivered</th><th>Courier</th><th><span class="ops-sr-only">Actions</span></th></tr></thead>
                    <tbody>
                    @forelse($orders as $order)
                        @php
                            $first = $order->items->first();
                            $more = $order->items->count() - 1;
                            $image = $first?->product?->images->first();
                            $track = $order->carrier_tracking_number ?: $order->tracking_number;
                        @endphp
                        <tr class="do-row">
                            <td>
                                <strong>#{{ $order->number }}</strong>
                                @if($track)
                                    <span class="do-track"><small>{{ $track }}</small>
                                        <button type="button" class="do-copy" data-copy="{{ $track }}" data-copy-label="Tracking number" aria-label="Copy tracking number">
                                            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg>
                                        </button>
                                    </span>
                                @endif
                            </td>
                            <td>{{ $order->buyer?->name ?? 'Buyer removed' }}</td>
                            <td>
                                <div class="do-item">
                                    @if($image)<img src="{{ asset('storage/'.$image->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                                    <span><span class="do-item__name">{{ $first?->product?->name ?? 'Product removed' }}</span><small>{{ $more > 0 ? '+'.$more.' more · ' : '' }}{{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('unit', $order->items->sum('quantity')) }}</small></span>
                                </div>
                            </td>
                            <td><strong>₱{{ number_format($order->payable_total, 2) }}</strong>@if($order->payment_mode)<small><span class="do-pay">{{ $payLabel($order->payment_mode) }}</span></small>@endif</td>
                            <td>{{ $order->delivered_at?->format('M j, Y') ?? 'No date' }}<small>{{ $order->delivered_at?->diffForHumans() ?? '' }}</small></td>
                            <td>{{ $order->carrier_name ?? '—' }}<small>{{ $order->courier?->name ?? 'No rider' }}</small></td>
                            <td class="do-actions"><a class="do-btn do-btn--soft do-btn--small" href="{{ route('seller.completed-orders.show', $order) }}" data-panel data-panel-title="Order #{{ $order->number }}">
                                View details
                                <svg class="do-ico do-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                            </a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="do-empty-cell">
                            @if($filtered)
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4M9 11h4"/></svg></span>
                                    <strong>No orders match your filters</strong>
                                    <p>Remove a filter or widen the date range.</p>
                                    <a class="do-btn do-btn--primary" href="{{ $clearUrl }}">Clear filters</a>
                                </div>
                            @else
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><path d="m6.5 11.5 1.8 1.8 3-3.2"/></svg></span>
                                    <strong>No delivered orders yet</strong>
                                    <p>Orders show here once the courier marks them delivered.</p>
                                    <a class="do-btn do-btn--primary" href="{{ route('seller.shipments.index') }}">View shipments</a>
                                </div>
                            @endif
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('seller.operations.pagination', ['records' => $orders])
        </section>

        <div class="do-panels">
            <section class="do-card">
                <header class="do-card__head"><h2>Delivery results</h2></header>
                @if($outcomeTotal)
                    <div class="do-chart">
                        <div class="do-donut" style="--chart: {{ $chart }}" role="img" aria-label="{{ $stats['completion_rate'] }}% of orders delivered"><span><strong>{{ $stats['completion_rate'] }}%</strong><small>Delivered</small></span></div>
                        <ul class="do-key">
                            @foreach([['delivered', 'Delivered', 'mint'], ['cancelled', 'Cancelled', 'rose'], ['delivery_failed', 'Failed delivery', 'lilac']] as [$key, $label, $tone])
                                <li><i class="do-dot do-dot--{{ $tone }}"></i><span><strong>{{ $label }}</strong><small>{{ number_format($outcomes[$key]) }} orders · {{ $pct($outcomes[$key]) }}%</small></span></li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="do-empty do-empty--compact">
                        <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 109 9h-9z"/><path d="M15 3.5A9 9 0 0120.5 9H15z"/></svg></span>
                        <strong>No results to chart</strong>
                        <p>Results appear after your first delivery.</p>
                    </div>
                @endif
            </section>

            <section class="do-card">
                <header class="do-card__head"><h2>Top products</h2></header>
                @forelse($topProducts as $item)
                    @php $thumb = $item->product?->images->first(); @endphp
                    <div class="do-top">
                        <span class="do-top__rank">{{ $loop->iteration }}</span>
                        @if($thumb)<img src="{{ asset('storage/'.$thumb->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                        <span class="do-top__body">
                            <strong>{{ $item->product?->name ?? 'Product removed' }}</strong>
                            <small>{{ number_format($item->units_sold) }} {{ \Illuminate\Support\Str::plural('unit', $item->units_sold) }} sold</small>
                            <span class="do-bar"><i style="--w: {{ round($item->revenue / $maxRevenue * 100) }}%"></i></span>
                        </span>
                        <strong class="do-top__amount">₱{{ number_format($item->revenue, 2) }}</strong>
                    </div>
                @empty
                    <div class="do-empty do-empty--compact">
                        <span class="do-empty__icon do-tone-butter"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>
                        <strong>No product sales yet</strong>
                        <p>{{ $filtered ? 'Try a wider date range.' : 'Your best sellers appear here.' }}</p>
                    </div>
                @endforelse
            </section>
        </div>
    </section>
    @include('seller.operations.drawer')
    @include('shared.live-revision', ['endpoint' => route('seller.live', 'shipments'), 'mode' => 'reload'])
</x-seller.layout>
