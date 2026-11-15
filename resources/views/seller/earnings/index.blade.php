@php
    $clearUrl = route('seller.earnings.index');
    $money = fn (int $cents) => '₱'.number_format($cents / 100, 2);
@endphp
<x-seller.layout title="Earnings">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/earnings.css', 'resources/js/seller/delivered-orders.js'])
    <section class="ops-page do-page er-page">
        <header class="do-head">
            <div>
                <h1>Earnings</h1>
                <p>Your pay for each delivered order, after vouchers and commission.</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <a class="do-btn do-btn--soft" href="{{ route('seller.earnings.pickup-fees') }}">
                    <svg class="do-ico do-ico--slide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/></svg>
                    Return pickup fees
                </a>
                <a class="do-btn do-btn--soft" href="{{ route('seller.completed-orders.index') }}">
                    <svg class="do-ico do-ico--slide" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg>
                    View delivered orders
                </a>
            </div>
        </header>

        <div class="do-stats er-stats" role="list">
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg></span>
                <span class="do-stat__text">Credited<strong>{{ $money($creditedCents) }}</strong><small>Added to your earnings</small></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-lilac"><svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><circle cx="7.500" cy="7.500" r="2.500"/><circle cx="16.500" cy="16.500" r="2.500"/></svg></span>
                <span class="do-stat__text">Commission paid<strong>{{ $money($commissionCents) }}</strong><small>On credited orders</small></span>
            </div>
        </div>

        <details class="er-how">
            <summary><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>How earnings work<svg class="do-ico er-how__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
            <p>Your earnings = item price − vouchers you funded − Vendo commission. Each order is credited automatically as soon as it is delivered. You don't need bank details. Return pickup fees charged to you are listed separately on <a href="{{ route('seller.earnings.pickup-fees') }}" style="font-weight:600;text-decoration:underline">Return pickup fees</a>.</p>
        </details>

        <section class="do-card do-table-card" aria-label="Earnings by order" data-do-table>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Order</th><th>Delivered</th><th>Items</th><th>Voucher</th><th>Commission</th><th>Your earnings</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($orders as $row)
                        @php $order = $row['order']; @endphp
                        <tr class="er-row">
                            <td>
                                <strong>#{{ $order->number }}</strong>
                                @if($order->tracking_number)
                                    <span class="do-track"><small>{{ $order->tracking_number }}</small>
                                        <button type="button" class="do-copy" data-copy="{{ $order->tracking_number }}" data-copy-label="Tracking number" aria-label="Copy tracking number"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg></button>
                                    </span>
                                @endif
                            </td>
                            <td>{{ $order->delivered_at?->format('M j, Y') ?? 'No date' }}<small>{{ $order->delivered_at?->diffForHumans() ?? '' }}</small></td>
                            <td>{{ $money($row['items_cents']) }}</td>
                            <td>{{ $row['voucher_cents'] > 0 ? '−'.$money($row['voucher_cents']) : '—' }}</td>
                            <td>−{{ $money($row['commission_cents']) }}<small>{{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}% of items</small></td>
                            <td><strong class="er-net">{{ $money($row['net_cents']) }}</strong></td>
                            <td><span class="do-badge do-badge--mint">Credited</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="do-empty-cell">
                            <div class="do-empty">
                                <span class="do-empty__icon do-tone-butter"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.600"/><path d="M6.500 9.500v.01M17.500 14.500v.01"/></svg></span>
                                <strong>No earnings yet</strong>
                                <p>Earnings credit automatically after your first order is delivered.</p>
                                <a class="do-btn do-btn--primary" href="{{ route('seller.shipments.index') }}">View shipments</a>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('seller.operations.pagination', ['records' => $orders])
        </section>
    </section>
</x-seller.layout>
