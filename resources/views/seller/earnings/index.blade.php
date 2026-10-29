<x-seller.layout title="Earnings">
    @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
    <section class="ops-page">
        <header class="ops-page-head"><div><h1>Earnings</h1><p>What you earn from each delivered order: the item price, minus any voucher you funded and Vendo's commission. Earnings are credited here automatically once Vendo has received the cash for the parcel. No bank details are needed.</p></div></header>
        <div class="ops-stats">
            <div class="ops-stat"><span class="ops-stat-icon">⏳</span><span>Pending<strong>₱{{ number_format($pendingCents / 100, 2) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">✓</span><span>Credited<strong>₱{{ number_format($creditedCents / 100, 2) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">%</span><span>Commission on credited orders<strong>₱{{ number_format($commissionCents / 100, 2) }}</strong></span></div>
        </div>

        <form method="GET" action="{{ route('seller.earnings.index') }}" class="ops-filters">
            <label><span class="ops-sr-only">Status</span>
                <select name="status">
                    <option value="">All orders</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                    <option value="credited" @selected(($filters['status'] ?? '') === 'credited')>Credited</option>
                    <option value="earlier" @selected(($filters['status'] ?? '') === 'earlier')>Earlier orders</option>
                </select>
            </label>
            <button class="ops-button" type="submit">Apply</button>
            @if(request()->query())<a class="ops-text-link" href="{{ route('seller.earnings.index') }}">Clear</a>@endif
        </form>

        @if($errors->any())<p class="ops-alert" role="alert">{{ $errors->first() }}</p>@endif
        @if($earlierCount > 0)<p class="ops-alert" role="status">{{ number_format($earlierCount) }} earlier order(s) were placed before cash tracking and are not counted in the totals.</p>@endif

        <section class="ops-card ops-table-card" aria-label="Earnings by order">
            <div class="ops-table-scroll"><table class="ops-table"><thead><tr><th>Order ID</th><th>Delivered</th><th>Items</th><th>Voucher</th><th>Vendo commission</th><th>Your earnings</th><th>Status</th></tr></thead><tbody>
                @forelse($orders as $row)
                    <tr>
                        <td><strong>#{{ $row['order']->number }}</strong><small>{{ $row['order']->tracking_number }}</small></td>
                        <td>{{ $row['order']->delivered_at?->format('M j, Y') ?? '—' }}</td>
                        <td>₱{{ number_format($row['items_cents'] / 100, 2) }}</td>
                        <td>{{ $row['voucher_cents'] > 0 ? '−₱'.number_format($row['voucher_cents'] / 100, 2) : '—' }}</td>
                        <td>−₱{{ number_format($row['commission_cents'] / 100, 2) }}<small>{{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}% of items</small></td>
                        <td><strong>₱{{ number_format($row['net_cents'] / 100, 2) }}</strong></td>
                        <td>
                            @if($row['state'] === 'credited')<span class="ops-badge ops-badge--delivered">Credited</span>
                            @elseif($row['state'] === 'pending')<span class="ops-badge ops-badge--in">Pending</span><small>{{ $moneyStatuses[$row['order']->money_status] ?? '' }}</small>
                            @else Earlier order<small>Placed before cash tracking</small>@endif
                        </td>
                    </tr>
                @empty<tr><td colspan="7" class="ops-empty">No delivered orders yet.</td></tr>@endforelse
            </tbody></table></div>
            @include('seller.operations.pagination', ['records' => $orders])
        </section>
    </section>
</x-seller.layout>
