<x-seller.layout title="Customer Feedback">
    @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
    <section class="ops-page">
        <header class="ops-page-head"><div><h1>Customer Feedback</h1><p>Read product reviews and respond to buyers.</p></div></header>
        <div class="ops-stats">
            <div class="ops-stat"><span class="ops-stat-icon">★</span><span>Average Rating<strong>{{ $stats['average'] === null ? '—' : number_format($stats['average'], 1).'/5' }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">✎</span><span>Total Reviews<strong>{{ number_format($stats['reviews']) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">♙</span><span>Customers<strong>{{ number_format($stats['customers']) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">↩</span><span>Response Rate<strong>{{ $stats['response_rate'] }}%</strong></span></div>
        </div>
        <form method="GET" action="{{ route('seller.feedback.index') }}" class="ops-filters">
            @if($filters['order'] ?? null)<input type="hidden" name="order" value="{{ $filters['order'] }}"><span class="ops-badge ops-badge--delivered">Order #{{ str_pad($filters['order'], 6, '0', STR_PAD_LEFT) }}</span>@endif
            <label class="ops-search"><span class="ops-sr-only">Search feedback</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search order, customer, product, or review"></label>
            <label><span class="ops-sr-only">Rating</span><select name="rating"><option value="">All ratings</option>@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" @selected(($filters['rating'] ?? '') == $rating)>{{ $rating }} star{{ $rating === 1 ? '' : 's' }}</option>@endfor</select></label>
            <label><span class="ops-sr-only">Reply status</span><select name="reply"><option value="">All replies</option><option value="replied" @selected(($filters['reply'] ?? '') === 'replied')>Replied</option><option value="pending" @selected(($filters['reply'] ?? '') === 'pending')>Awaiting reply</option></select></label>
            <label class="ops-nowrap">From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="ops-nowrap">To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
            <button class="ops-button" type="submit">Apply</button>
            @if(request()->query())<a class="ops-text-link" href="{{ route('seller.feedback.index') }}">Clear</a>@endif
        </form>
        @if($errors->any())<p class="ops-alert" role="alert">{{ $errors->first() }}</p>@endif

        <div class="ops-bottom-panels">
            <section class="ops-card"><header class="ops-card-head"><h2>Rating Breakdown</h2><span>{{ number_format($stats['reviews']) }} reviews</span></header>
                @foreach($ratings as $rating => $data)<div class="ops-list-row"><span class="ops-stars ops-nowrap">{{ str_repeat('★', $rating) }}</span><span class="ops-review">{{ $rating }} stars</span><strong>{{ $data['count'] }}</strong><small>{{ $data['percent'] }}%</small></div>@endforeach
            </section>
            <section class="ops-card"><header class="ops-card-head"><h2>Recent Feedback</h2></header>
                @forelse($recent as $review)<a class="ops-list-row" href="{{ route('seller.feedback.show', $review) }}" data-panel data-panel-title="Feedback Details"><span class="ops-category-symbol ops-stars">★</span><span><strong>{{ $review->product?->name ?? 'Product unavailable' }}</strong><small>{{ $review->buyer?->name ?? 'Buyer' }} · {{ $review->created_at->format('M j') }}</small></span><span>{{ $review->rating }}/5</span></a>@empty<p class="ops-muted">No customer feedback yet.</p>@endforelse
            </section>
        </div>

        <section class="ops-card ops-table-card" aria-label="Customer feedback">
            <div class="ops-table-scroll"><table class="ops-table ops-table--shipments"><thead><tr><th>Rating</th><th>Review</th><th>Customer</th><th>Product</th><th>Order</th><th>Date</th><th>Response</th><th>Action</th></tr></thead><tbody>
                @forelse($rows as $review)
                    <tr>
                        <td><span class="ops-stars" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></td>
                        <td class="ops-truncate" title="{{ $review->comment }}">{{ $review->comment }}</td>
                        <td>{{ $review->buyer?->name ?? 'Buyer' }}</td>
                        <td>{{ $review->product?->name ?? 'Unavailable product' }}</td>
                        <td>#VN-{{ str_pad($review->order_id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $review->created_at->format('M j, Y') }}</td>
                        <td><span class="ops-badge {{ $review->reply ? 'ops-badge--delivered' : 'ops-badge--to_ship' }}">{{ $review->reply ? 'Replied' : 'Awaiting reply' }}</span>@if($review->report)<small class="ops-review">Reported</small>@endif</td>
                        <td><a class="ops-button ops-button--small" href="{{ route('seller.feedback.show', $review) }}" data-panel data-panel-title="Feedback Details">View</a></td>
                    </tr>
                @empty<tr><td colspan="8" class="ops-empty">No published reviews match these filters.</td></tr>@endforelse
            </tbody></table></div>
            @include('seller.operations.pagination', ['records' => $rows])
        </section>
    </section>
    @include('seller.operations.drawer')
@include('shared.live-revision', ['endpoint' => route('seller.live', 'feedback'), 'mode' => 'reload'])
</x-seller.layout>
