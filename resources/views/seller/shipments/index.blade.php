@php
    use App\Models\Ecommerce\Order;
    use Illuminate\Support\Str;

    // "Returned" is intentionally not offered: the seller flow no longer surfaces returns (owner decision, 7 Oct 2026).
    $statCards = [
        'all' => ['Total Shipments', 'total-shipments-icon.svg'],
        'to_ship' => ['To Ship', 'to-ship-icon.svg'],
        'in_transit' => ['In Transit', 'in-transit-icon.svg'],
        'delivered' => ['Delivered', 'delivered-icon.svg'],
        'cancelled' => ['Cancelled', 'cancelled-icon.svg'],
    ];
    $tabs = ['all' => 'All', 'to_ship' => 'To Ship', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
    $pillFor = ['to_ship' => 'pack', 'in_transit' => 'pending', 'delivered' => 'completed', 'cancelled' => 'cancelled'];

    $activeStatus = $filters['status'] ?? '';
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $dateActive = $dateFrom !== '' || $dateTo !== '';
    $hasFilters = $activeStatus !== '' || $dateActive || ! empty($filters['search']) || ! empty($filters['courier']);
    $today = now()->toDateString();
    $datePresets = [
        'All dates' => ['', ''],
        'Today' => [$today, $today],
        'Last 7 days' => [now()->subDays(6)->toDateString(), $today],
        'This month' => [now()->startOfMonth()->toDateString(), $today],
    ];
    $dateLabel = $dateActive
        ? (($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M j') : 'Start').' – '.($dateTo ? \Carbon\Carbon::parse($dateTo)->format('M j') : 'Today'))
        : 'All dates';
    $urlWith = fn (array $extra) => route('seller.shipments.index', array_filter(array_merge(request()->except('page'), $extra), fn ($v) => $v !== null && $v !== ''));

    $emptyCopy = [
        'to_ship' => ['Nothing to ship', 'Orders you accept wait here until a rider collects them.'],
        'in_transit' => ['No parcels in transit', 'Parcels on their way to your buyers will be listed here.'],
        'delivered' => ['No delivered shipments yet', 'Shipments appear here once a rider scans them as delivered.'],
        'cancelled' => ['No cancelled shipments', 'Good news. Cancelled shipments would be listed here.'],
    ];
    $onlyStatus = $activeStatus !== '' && ! $dateActive && empty($filters['search']) && empty($filters['courier']);
    [$emptyTitle, $emptyText] = ($hasFilters && ! $onlyStatus)
        ? ['No shipments match your filters', 'Try a different search term or date range, or clear the filters to see every shipment.']
        : ($emptyCopy[$activeStatus] ?? ['No shipments yet', 'Orders you accept will appear here so you can follow them to your buyer’s door.']);
    $perPageOptions = $perPageOptions ?? null; // supplied by the controller once per-page choice is supported
@endphp
<x-seller.layout title="Shipments">
    @vite(['resources/css/seller/order-management-orders.css', 'resources/css/seller/operations.css', 'resources/css/seller/shipments.css', 'resources/js/seller/operations.js', 'resources/js/seller/shipments.js'])

    <div class="omo-content sh-page">
        <section class="omo-head">
            <div>
                <h1 class="omo-head__title">Shipments</h1>
                <p class="omo-head__sub">Follow each accepted order from packing to your buyer’s door.</p>
            </div>
            <span class="omo-live" title="This page refreshes automatically when a shipment changes"><i aria-hidden="true"></i><span>Live</span></span>
        </section>

        <nav class="omo-stats sh-stats" aria-label="Shipment summary">
            @foreach($statCards as $key => [$label, $icon])
                @php $isActive = $key === 'all' ? $activeStatus === '' : $activeStatus === $key; @endphp
                <a class="omo-stat @if($isActive) is-active @endif" style="--i: {{ $loop->index }}" href="{{ $urlWith(['status' => $key === 'all' ? null : $key]) }}" @if($isActive) aria-current="true" @endif>
                    <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/'.$icon) }}" alt=""></span>
                    <span class="omo-stat__text">
                        <span class="omo-stat__label">{{ $label }}</span>
                        <span class="omo-stat__value">{{ number_format($counts[$key]) }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        <section class="sh-board" aria-label="Shipments">
            {{-- Tabs on the left, search and filters on the right --}}
            <header class="sh-board__head">
                <nav class="sh-tabs" aria-label="Shipment status">
                    @foreach($tabs as $key => $label)
                        @php $isActive = $key === 'all' ? $activeStatus === '' : $activeStatus === $key; @endphp
                        <a class="sh-tab @if($isActive) is-active @endif" href="{{ $urlWith(['status' => $key === 'all' ? null : $key]) }}" @if($isActive) aria-current="page" @endif>
                            {{ $label }}@if($key !== 'all')<span>{{ number_format($counts[$key]) }}</span>@endif
                        </a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('seller.shipments.index') }}" class="sh-controls" id="shFilters">
                    @if($activeStatus !== '')<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                    @if($dateActive)<input type="hidden" name="date_from" value="{{ $dateFrom }}"><input type="hidden" name="date_to" value="{{ $dateTo }}">@endif

                    <label class="sh-search">
                        <span class="omo-sr">Search shipments</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search order ID, customer name, or tracking number" autocomplete="off" data-sh-search>
                    </label>

                    <label class="omo-filter-btn sh-select">
                        <span class="omo-sr">Courier</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
                        <select name="courier" data-sh-autosubmit>
                            <option value="">All couriers</option>
                            <option value="unassigned" @selected(($filters['courier'] ?? '') === 'unassigned')>Unassigned</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}" @selected(($filters['courier'] ?? '') == $courier->id)>{{ $courier->name }}</option>
                            @endforeach
                        </select>
                        <svg class="omo-chev" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </label>

                    <details class="omo-filter sh-date" data-sh-date>
                        <summary class="omo-filter-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/></svg>
                            <span>{{ $dateLabel }}</span>
                            <svg class="omo-chev" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="omo-filter-panel omo-date-panel sh-date-panel">
                            <div class="omo-date-presets">
                                @foreach($datePresets as $label => [$from, $to])
                                    <a class="omo-date-preset @if($from === $dateFrom && $to === $dateTo) is-selected @endif" href="{{ $urlWith(['date_from' => $from, 'date_to' => $to]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                            <div class="omo-date-range">
                                <div><label for="shDateFrom">From</label><input type="date" id="shDateFrom" value="{{ $dateFrom }}" data-sh-date-from></div>
                                <div><label for="shDateTo">To</label><input type="date" id="shDateTo" value="{{ $dateTo }}" data-sh-date-to></div>
                            </div>
                            <div class="omo-date-actions">
                                <a class="omo-btn-clear sh-date-clear" href="{{ $urlWith(['date_from' => null, 'date_to' => null]) }}">Clear</a>
                                <button type="button" class="omo-btn-apply" data-sh-date-apply>Apply</button>
                            </div>
                        </div>
                    </details>
                    @if($hasFilters)<a class="omo-clear-filters" href="{{ route('seller.shipments.index') }}">Clear filters</a>@endif
                </form>
            </header>

            <ul class="sh-list">
                @forelse($shipments as $order)
                    @php
                        $group = $order->shipment_group;
                        $buyerName = $order->buyer?->name ?? 'Buyer unavailable';
                        $trackingNo = $order->carrier_tracking_number ?: $order->tracking_number;
                        $carrier = $order->carrier_name ?: ($order->courier?->name ?? 'Unassigned');
                        $needsRider = $order->status === 'ready_for_pickup' && ! $order->courier_id;
                        $failed = $order->status === 'delivery_failed';
                        $qty = (int) ($order->items_sum_quantity ?? 0);
                        $mode = strtoupper($order->payment_mode ?? 'COD');
                        $isCod = $mode === 'COD';
                        $voucherCode = $order->voucher_code ?? null; // present once vouchers exist (backend-needs-2026-10-07.md)
                    @endphp
                    <li class="sh-card sh-card--{{ $group }} @if($failed) sh-card--alert @endif" data-record="{{ $order->id }}" tabindex="0" style="--i: {{ $loop->index }}">
                        <div class="sh-card__lead">
                            <span class="sh-tile" aria-hidden="true">
                                @include('seller.operations.image', ['product' => $order->items->first()?->product])
                                @if($qty > 1)<em>×{{ $qty }}</em>@endif
                            </span>
                            <div class="sh-who">
                                <strong class="sh-no">{{ $order->number }}</strong>
                                <span class="sh-name">{{ $buyerName }}</span>
                                @if($order->buyer?->phone_number)<span class="sh-phone">{{ $order->buyer->phone_number }}</span>@endif
                                <span class="sh-when">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>{{ $order->created_at->format('M j, Y') }}
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>{{ $order->created_at->format('g:i A') }}
                                </span>
                            </div>
                        </div>

                        <div class="sh-card__mid">
                            <div class="sh-mid-top">
                                <span class="omo-pill omo-pill--{{ $pillFor[$group] ?? 'pack' }}"><i aria-hidden="true"></i>{{ Order::SHIPMENT_LABELS[$group] ?? $group }}</span>
                                <span class="sh-carrier" title="Carrier">{{ $carrier }}</span>
                            </div>
                            @if($group === 'delivered')
                                <p class="sh-eta">Delivered on <strong>{{ \Illuminate\Support\Carbon::parse($order->delivered_at ?? $order->updated_at)->format('M j, Y · g:i A') }}</strong></p>
                            @elseif($group === 'cancelled')
                                <p class="sh-eta">This shipment was cancelled</p>
                            @else
                                <p class="sh-eta">Estimated delivery
                                    <strong>{{ ($order->estimated_delivery_from && $order->estimated_delivery_to) ? ($order->estimated_delivery_from->isSameDay($order->estimated_delivery_to) ? $order->estimated_delivery_from->format('M j, Y') : $order->estimated_delivery_from->format('M j').' – '.$order->estimated_delivery_to->format('M j, Y')) : 'Not scheduled yet' }}</strong>
                                </p>
                            @endif
                            @include('seller.shipments.partials.progress', ['order' => $order, 'size' => 'sm'])
                            <p class="sh-trk">
                                Tracking number <code>{{ $trackingNo }}</code>
                                <button type="button" class="sh-copy" data-sh-copy="{{ $trackingNo }}" aria-label="Copy tracking number {{ $trackingNo }}">Copy</button>
                            </p>
                            @if($failed)<span class="sh-flag sh-flag--danger">Delivery attempt failed</span>@endif
                            @if($needsRider)<span class="sh-flag">Waiting for a pickup rider</span>@endif
                        </div>

                        <div class="sh-card__end">
                            <strong class="sh-amount">₱{{ number_format($order->total_amount, 2) }}</strong>
                            <p class="sh-pay">Payment: <b class="{{ $isCod ? 'sh-pay--cod' : 'sh-pay--paid' }}">{{ $mode }}</b></p>
                            <small>{{ $isCod ? 'Payment upon delivery' : 'Paid online' }}</small>
                            @if($voucherCode)<span class="sh-voucher" title="Voucher used on this order">Voucher {{ $voucherCode }}</span>@endif
                            <span class="sh-actions">
                                <a class="omo-view-btn" href="{{ route('seller.shipments.show', $order) }}" data-panel data-panel-title="Shipment Details" data-record-id="{{ $order->id }}" aria-label="View shipment {{ $order->number }}">View</a>
                                @if($order->canPrintShippingLabel())
                                    <a class="omo-view-btn sh-btn-ghost" href="{{ route('seller.orders.waybill', $order) }}" data-waybill data-waybill-title="{{ $order->number }}" aria-label="Print shipping label for {{ $order->number }}">Print label</a>
                                @endif
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="sh-empty">
                        <div class="omo-empty__art" aria-hidden="true">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
                        </div>
                        <h3>{{ $emptyTitle }}</h3>
                        <p>{{ $emptyText }}</p>
                        @if($hasFilters)
                            <a class="omo-btn omo-btn--primary omo-empty__btn" href="{{ route('seller.shipments.index') }}">Clear filters</a>
                        @else
                            <a class="omo-btn omo-btn--ghost omo-empty__btn" href="{{ route('seller.orders.index') }}">Go to Orders</a>
                        @endif
                    </li>
                @endforelse
            </ul>

            @if($shipments->total() > 0)
                <footer class="sh-board__foot">
                    <span>Showing {{ $shipments->count() }} out of {{ number_format($shipments->total()) }} entries</span>
                    <div class="sh-foot-right">
                        @if($shipments->hasPages())
                            <nav class="sh-pager" aria-label="Shipments pagination">
                                @if($shipments->onFirstPage())<span class="is-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
                                @else<a href="{{ request()->fullUrlWithQuery(['page' => $shipments->currentPage() - 1]) }}" aria-label="Previous page">‹</a>@endif
                                @for($page = max(1, $shipments->currentPage() - 2); $page <= min($shipments->lastPage(), $shipments->currentPage() + 2); $page++)
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $page]) }}" class="{{ $page === $shipments->currentPage() ? 'is-active' : '' }}" @if($page === $shipments->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                                @endfor
                                @if($shipments->hasMorePages())<a href="{{ request()->fullUrlWithQuery(['page' => $shipments->currentPage() + 1]) }}" aria-label="Next page">›</a>
                                @else<span class="is-disabled" aria-disabled="true" aria-label="Next page">›</span>@endif
                            </nav>
                        @endif
                        {{-- Appears only when the controller passes $perPageOptions (e.g. [7, 15, 30]); see backend-needs-2026-10-07.md --}}
                        @if($perPageOptions)
                            <label class="sh-perpage">Items per page
                                <select data-sh-perpage>
                                    @foreach($perPageOptions as $option)<option value="{{ $option }}" @selected($shipments->perPage() == $option)>{{ $option }}</option>@endforeach
                                </select>
                            </label>
                        @endif
                    </div>
                </footer>
            @endif
        </section>
    </div>

    @include('seller.operations.drawer')
    @include('shared.live-revision', ['endpoint' => route('seller.live', 'shipments'), 'mode' => 'reload'])
</x-seller.layout>