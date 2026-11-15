@use('App\Models\Ecommerce\OrderReturnRequest', 'Ret')
@php
    $money = fn (int $cents) => '₱'.number_format($cents / 100, 2);
    $badge = function (Ret $fee) {
        return match ($fee->return_status) {
            Ret::RETURN_STATUS_DELIVERED_TO_SELLER => ['mint', 'Returned to you'],
            Ret::RETURN_STATUS_PICKED_UP => ['sky', 'Picked up'],
            default => ['butter', 'Waiting for rider'],
        };
    };
@endphp
<x-seller.layout title="Return pickup fees">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/earnings.css', 'resources/js/seller/delivered-orders.js'])
    <section class="ops-page do-page er-page">
        <header class="do-head">
            <div>
                <h1>Return pickup fees</h1>
                <p>The pickup fee for returns where the item was damaged, wrong, missing or not as described.</p>
            </div>
            <a class="do-btn do-btn--soft" href="{{ route('seller.earnings.index') }}">
                <svg class="do-ico do-ico--slide" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                Back to earnings
            </a>
        </header>

        <div class="do-stats er-stats" role="list">
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-butter"><svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5v.01M17.5 14.5v.01"/></svg></span>
                <span class="do-stat__text">Charged<strong>{{ $money($chargedCents) }}</strong><small>{{ $total }} return{{ $total === 1 ? '' : 's' }} · recorded fees</small></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg></span>
                <span class="do-stat__text">Collected<strong>{{ number_format($collected) }}</strong><small>Rider picked up the parcel</small></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-sky"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <span class="do-stat__text">Waiting<strong>{{ number_format($waiting) }}</strong><small>Pickup not done yet</small></span>
            </div>
        </div>

        <div class="do-alert do-alert--info" role="note">
            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
            <p>These fees are recorded on your returns. Vendo has no Seller payout ledger yet, so nothing has been debited from a balance or collected from you.</p>
        </div>

        <section class="do-card do-table-card" aria-label="Return pickup fees">
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Order</th><th>Approved</th><th>Reason</th><th>Rider pickup</th><th>Fee</th></tr></thead>
                    <tbody>
                    @forelse ($fees as $fee)
                        @php [$tone, $label] = $badge($fee); @endphp
                        <tr class="er-row">
                            <td>
                                <strong>#{{ $fee->order?->number }}</strong>
                                <small>{{ $fee->buyer?->name ?? 'Buyer' }}</small>
                            </td>
                            <td>{{ $fee->decided_at?->format('M j, Y') ?? '—' }}<small>{{ $fee->decided_at?->diffForHumans() ?? '' }}</small></td>
                            <td>{{ $fee->reason_label }}</td>
                            <td>
                                <span class="do-badge do-tone-{{ $tone }}">{{ $label }}</span>
                                @if ($fee->pickupRider)<small>Rider: {{ $fee->pickupRider->name }}</small>@endif
                            </td>
                            <td><strong class="er-net">{{ $money((int) $fee->pickup_fee_cents) }}</strong><small>Charged to you</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="do-empty-cell">
                            <div class="do-empty">
                                <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.8 1.8 0 100-3.6A1.8 1.8 0 007 19zm10 0a1.8 1.8 0 100-3.6A1.8 1.8 0 0017 19z"/></svg></span>
                                <strong>No pickup fees</strong>
                                <p>Returns you approve for damaged, wrong, missing or not-as-described items appear here.</p>
                                <a class="do-btn do-btn--primary" href="{{ route('seller.returns.index') }}">View returns</a>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('seller.operations.pagination', ['records' => $fees])
        </section>
    </section>
</x-seller.layout>
