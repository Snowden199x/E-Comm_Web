@php
    $filtered = collect($filters)->except('page')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $clearUrl = route('seller.feedback.index');
    $replyFilter = $filters['reply'] ?? '';
    $keep = request()->except(['reply', 'page']);
    $tabs = ['' => ['All', $replyCounts['all']], 'pending' => ['Awaiting reply', $replyCounts['pending']], 'replied' => ['Replied', $replyCounts['replied']]];
    $stars = fn (int $n) => str_repeat('★', $n).str_repeat('☆', 5 - $n);
    $thumb = fn ($review) => $review->product?->images->first();
@endphp
<x-seller.layout title="Feedback">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/feedback.css', 'resources/js/seller/operations.js', 'resources/js/seller/delivered-orders.js', 'resources/js/seller/feedback.js'])
    <section class="ops-page do-page fb-page">
        <header class="do-head">
            <div>
                <h1>Feedback</h1>
                <p>Read reviews and reply to your buyers.</p>
            </div>
            @if($replyCounts['pending'] > 0 && $replyFilter !== 'pending')
                <a class="do-btn do-btn--primary" href="{{ route('seller.feedback.index', array_merge($keep, ['reply' => 'pending'])) }}" data-load>
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 01-11.600 7.100L4 20l1-4.600A8 8 0 1121 12z"/></svg>
                    Reply to {{ number_format($replyCounts['pending']) }} {{ $replyCounts['pending'] === 1 ? 'review' : 'reviews' }}
                </a>
            @endif
        </header>

        <div class="do-stats" role="list">
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-butter"><svg class="do-ico fb-ico--star" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 2.400 4.900 5.400.8-3.900 3.800.9 5.400-4.800-2.500-4.800 2.500.9-5.400L4.200 9.700l5.400-.8z"/></svg></span>
                <span class="do-stat__text">Average rating<strong>{{ $stats['average'] === null ? '—' : number_format($stats['average'], 1).' / 5' }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-lilac"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 01-11.600 7.100L4 20l1-4.600A8 8 0 1121 12z"/><path d="M8.500 11h7M8.500 14h4"/></svg></span>
                <span class="do-stat__text">Reviews<strong>{{ number_format($stats['reviews']) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-sky"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.200"/><path d="M3 19c.6-3.400 3-5 6-5s5.400 1.600 6 5M16 5.200a3 3 0 010 5.600M18 14.400c1.700.6 2.800 2 3 4.600"/></svg></span>
                <span class="do-stat__text">Customers<strong>{{ number_format($stats['customers']) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 010 12h-3"/></svg></span>
                <span class="do-stat__text">Reply rate<strong>{{ $stats['response_rate'] }}%</strong></span>
            </div>
        </div>

        @if($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.500v.01"/></svg>
                <div>@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
                <a class="do-btn do-btn--small" href="{{ $clearUrl }}">Clear filters</a>
            </div>
        @endif

        <form method="GET" action="{{ $clearUrl }}" class="do-filters" data-do-filters>
            @if($filters['order'] ?? null)
                <input type="hidden" name="order" value="{{ $filters['order'] }}">
                <span class="do-chip is-active fb-order">Order #VN-{{ str_pad($filters['order'], 6, '0', STR_PAD_LEFT) }}
                    <a href="{{ route('seller.feedback.index', request()->except(['order', 'page'])) }}" aria-label="Remove order filter" data-load><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
                </span>
            @endif
            @if($replyFilter)<input type="hidden" name="reply" value="{{ $replyFilter }}">@endif
            <label class="do-search">
                <span class="ops-sr-only">Search feedback</span>
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.500"/><path d="m16 16 4 4"/></svg>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search order, customer, product, or review" autocomplete="off">
            </label>
            <label>
                <span class="ops-sr-only">Rating</span>
                <select name="rating" data-auto-submit>
                    <option value="">All ratings</option>
                    @for($rating = 5; $rating >= 1; $rating--)<option value="{{ $rating }}" @selected(($filters['rating'] ?? '') == $rating)>{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}</option>@endfor
                </select>
            </label>
            <div class="do-range" role="group" aria-label="Review date range">
                <label>From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" data-range></label>
                <label>To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" data-range></label>
                <button class="do-btn do-btn--soft do-btn--small" type="submit" data-range-apply hidden>Apply dates</button>
            </div>
        </form>

        <nav class="do-chips" aria-label="Filter by reply status">
            @foreach($tabs as $key => [$label, $count])
                <a href="{{ route('seller.feedback.index', $key === '' ? $keep : array_merge($keep, ['reply' => $key])) }}" class="do-chip @if($replyFilter === $key) is-active @endif" @if($replyFilter === $key) aria-current="true" @endif>{{ $label }} <span class="er-count">{{ number_format($count) }}</span></a>
            @endforeach
            @if($filtered)
                <a href="{{ $clearUrl }}" class="do-chip do-chip--clear"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>Clear filters</a>
            @endif
        </nav>

        <section class="do-card do-table-card" aria-label="Customer feedback" data-do-table>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Rating</th><th>Review</th><th>Order</th><th>Date</th><th>Status</th><th><span class="ops-sr-only">Actions</span></th></tr></thead>
                    <tbody>
                    @forelse($rows as $review)
                        @php $image = $thumb($review); @endphp
                        <tr class="do-row">
                            <td><span class="fb-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">{{ $stars($review->rating) }}</span></td>
                            <td>
                                <div class="do-item fb-review">
                                    @if($image)<img src="{{ asset('storage/'.$image->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                                    <span><span class="fb-comment" title="{{ $review->comment }}">{{ $review->comment }}</span><small>{{ $review->buyer?->name ?? 'Buyer removed' }} · {{ $review->product?->name ?? 'Product removed' }}</small></span>
                                </div>
                            </td>
                            <td>#VN-{{ str_pad($review->order_id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $review->created_at->format('M j, Y') }}<small>{{ $review->created_at->diffForHumans() }}</small></td>
                            <td>
                                <span class="do-badge {{ $review->reply ? 'do-badge--mint' : 'do-badge--butter' }}">{{ $review->reply ? 'Replied' : 'Awaiting reply' }}</span>
                                @if($review->report)<small class="fb-reported">Reported</small>@endif
                            </td>
                            <td class="do-actions">
                                <a class="do-btn do-btn--small {{ $review->reply ? 'do-btn--soft' : 'do-btn--primary' }}" href="{{ route('seller.feedback.show', $review) }}" data-panel data-panel-title="Review from {{ $review->buyer?->name ?? 'buyer' }}">
                                    {{ $review->reply ? 'View review' : 'Reply' }}
                                    <svg class="do-ico do-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="do-empty-cell">
                            @if(! $hasAny)
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-butter"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 2.400 4.900 5.400.8-3.900 3.800.9 5.400-4.800-2.500-4.800 2.500.9-5.400L4.200 9.700l5.400-.8z"/></svg></span>
                                    <strong>No reviews yet</strong>
                                    <p>Buyers can review after their order is delivered.</p>
                                    <a class="do-btn do-btn--primary" href="{{ route('seller.completed-orders.index') }}">View delivered orders</a>
                                </div>
                            @elseif($replyFilter === 'pending' && ! ($filters['rating'] ?? null) && ! ($filters['search'] ?? null))
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 3 3 5-6"/></svg></span>
                                    <strong>You're all caught up</strong>
                                    <p>Every review has a reply.</p>
                                    <a class="do-btn do-btn--primary" href="{{ route('seller.feedback.index', $keep) }}" data-load>Show all reviews</a>
                                </div>
                            @else
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.500"/><path d="m16 16 4 4M9 11h4"/></svg></span>
                                    <strong>No reviews match your filters</strong>
                                    <p>Remove a filter or widen the date range.</p>
                                    <a class="do-btn do-btn--primary" href="{{ $clearUrl }}" data-load>Clear filters</a>
                                </div>
                            @endif
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('seller.operations.pagination', ['records' => $rows])
        </section>

        <div class="do-panels">
            <section class="do-card">
                <header class="do-card__head"><h2>Rating breakdown</h2></header>
                @if($stats['reviews'])
                    <ul class="fb-ratings">
                        @foreach($ratings as $rating => $data)
                            <li>
                                <a href="{{ route('seller.feedback.index', array_merge(request()->except(['rating', 'page']), ['rating' => $rating])) }}" class="@if(($filters['rating'] ?? null) == $rating) is-active @endif" data-load aria-label="Show {{ $rating }} star reviews, {{ $data['count'] }} total">
                                    <span class="fb-ratings__label">{{ $rating }} <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 2.400 4.900 5.400.8-3.900 3.800.9 5.400-4.800-2.500-4.800 2.500.9-5.400L4.200 9.700l5.400-.8z"/></svg></span>
                                    <span class="do-bar"><i style="--w: {{ $data['percent'] }}%"></i></span>
                                    <span class="fb-ratings__count">{{ number_format($data['count']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="do-empty do-empty--compact">
                        <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V10M12 20V4M19 20v-7"/></svg></span>
                        <strong>No ratings yet</strong>
                        <p>Your breakdown appears after your first review.</p>
                    </div>
                @endif
            </section>

            <section class="do-card">
                <header class="do-card__head"><h2>Needs your reply</h2></header>
                @forelse($needsReply as $review)
                    @php $image = $thumb($review); @endphp
                    <a class="fb-need" href="{{ route('seller.feedback.show', $review) }}" data-panel data-panel-title="Review from {{ $review->buyer?->name ?? 'buyer' }}">
                        @if($image)<img src="{{ asset('storage/'.$image->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                        <span class="fb-need__body"><strong>{{ $review->product?->name ?? 'Product removed' }}</strong><small>{{ $review->buyer?->name ?? 'Buyer removed' }} · {{ $review->created_at->diffForHumans() }}</small></span>
                        <span class="fb-stars">{{ $review->rating }}★</span>
                    </a>
                @empty
                    <div class="do-empty do-empty--compact">
                        <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 3 3 5-6"/></svg></span>
                        <strong>You're all caught up</strong>
                        <p>New reviews that need a reply show here.</p>
                    </div>
                @endforelse
            </section>
        </div>
    </section>
    @include('seller.operations.drawer')
    @include('shared.live-revision', ['endpoint' => route('seller.live', 'feedback'), 'mode' => 'reload'])
</x-seller.layout>
