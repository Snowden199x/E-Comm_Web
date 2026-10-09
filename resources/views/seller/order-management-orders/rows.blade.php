@php
    // Rendered for both the first page load and the JSON refreshes used by the filters and pagination.
    $filtered = request('status', 'all') !== 'all'
        || request()->filled('search') || request()->filled('date_from') || request()->filled('date_to');

    $emptyCopy = [
        'new' => ['No new orders right now', 'New orders from buyers appear here as soon as they check out.'],
        'pack' => ['Nothing to pack', 'Orders you accept will wait here until they are packed.'],
        'pickup' => ['No parcels waiting for pickup', 'Orders marked ready for pickup show up here until a rider scans them.'],
        'pending' => ['No deliveries in progress', 'Parcels on their way to buyers will be listed here.'],
        'completed' => ['No delivered orders yet', 'Orders appear here once a rider scans them as delivered.'],
        'cancelled' => ['No cancelled orders', 'Good news. Declined and cancelled orders would be listed here.'],
    ];
    [$emptyTitle, $emptyText] = $filtered
        ? ['No orders match your filters', 'Try a different search term or date range, or clear the filters to see every order.']
        : ($emptyCopy[request('status', 'all')] ?? ['No orders yet', 'When buyers purchase your products, their orders will appear here for you to accept and prepare.']);
@endphp
@forelse ($orders as $order)
    @php
        $buyerName = $order->buyer?->name ?? 'Buyer unavailable';
        $initials = \Illuminate\Support\Str::of($buyerName)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        // Thumbnails only render when the controller eager-loads items.product.images (optional, see handoff notes).
        $thumbs = $order->relationLoaded('items')
            ? $order->items->map(fn ($item) => $item->product?->images->first())->filter()->take(3)
            : collect();
    @endphp
    <tr class="omo-row" data-id="{{ $order->id }}" data-status="{{ $order->seller_group }}" data-customer="{{ $buyerName }}" data-order="{{ $order->number }}" data-date="{{ $order->created_at->toDateString() }}" style="--i: {{ $loop->index }}">
        <td class="omo-order-id">{{ $order->number }}</td>
        <td>
            <span class="omo-customer">
                <span class="omo-customer__avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                <span class="omo-customer__name">{{ $buyerName }}</span>
                @if(trim((string) ($order->buyer_note ?? '')) !== '')
                    <span class="omo-note-flag" title="{{ $buyerName }} left a message for you" aria-label="Buyer left a message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/></svg></span>
                @endif
            </span>
        </td>
        <td>
            <span class="omo-items-cell">
                @if($thumbs->isNotEmpty())
                    <span class="omo-thumbs" aria-hidden="true">@foreach($thumbs as $image)<img src="{{ asset('storage/'.$image->path) }}" alt="" loading="lazy">@endforeach</span>
                @endif
                <span>{{ $order->items_sum_quantity ?? 0 }} {{ ($order->items_sum_quantity ?? 0) === 1 ? 'item' : 'items' }}</span>
            </span>
        </td>
        <td class="omo-total">₱{{ number_format($order->total_amount, 2) }}<small class="omo-paytag omo-paytag--{{ strtolower($order->payment_mode ?? 'cod') === 'cod' ? 'cod' : 'paid' }}">{{ strtoupper($order->payment_mode ?? 'COD') }}@if($order->voucher_code ?? null) · Voucher @endif</small></td>
        <td><span class="omo-pill omo-pill--{{ $order->seller_group }}"><i aria-hidden="true"></i>{{ $order->status_label }}</span></td>
        <td class="omo-date">{{ $order->created_at->format('M j, Y') }}<small>{{ $order->created_at->format('g:i A') }}</small></td>
        <td><button type="button" class="omo-view-btn" data-order="{{ $order->number }}" data-id="{{ $order->id }}" aria-label="View order {{ $order->number }}">View</button></td>
    </tr>
@empty
    <tr class="omo-empty-row">
        <td colspan="7" class="omo-empty">
            <div class="omo-empty__art" aria-hidden="true">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22l20-10 20 10v22L32 54 12 44z"/><path d="M12 22l20 10 20-10M32 32v22"/>
                    @if($filtered)<circle cx="48" cy="46" r="9" fill="var(--card, #fff)"/><path d="M44 42l8 8M52 42l-8 8"/>@endif
                </svg>
            </div>
            <h3>{{ $emptyTitle }}</h3>
            <p>{{ $emptyText }}</p>
            @if($filtered)
                <button type="button" class="omo-btn omo-btn--primary omo-empty__btn" data-omo-clear>Clear filters</button>
            @elseif(request('status', 'all') === 'all')
                <a class="omo-btn omo-btn--ghost omo-empty__btn" href="{{ route('seller.products.index') }}">Check your products</a>
            @endif
        </td>
    </tr>
@endforelse