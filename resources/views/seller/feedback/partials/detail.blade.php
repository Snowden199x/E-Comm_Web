@php
    $image = $review->product?->images->first();
    $buyer = $review->buyer?->name ?? 'Buyer removed';
    $templates = $review->rating >= 4
        ? ['Thanks', 'Thank you for your review. We are glad you enjoyed it.', 'Appreciate it', 'Thanks for the kind feedback. We hope to serve you again.']
        : ['Apologize', 'Sorry about this. Message us with your order number and we will make it right.', 'Thank you', 'Thanks for the feedback. We will use it to improve.'];
@endphp
<div class="do-detail">
    <div class="do-detail__head">
        @if($image)<img class="fb-thumb" src="{{ asset('storage/'.$image->path) }}" alt="">@else<span class="do-stat__icon do-tone-butter"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 2.400 4.900 5.400.8-3.900 3.800.9 5.400-4.800-2.500-4.800 2.500.9-5.400L4.200 9.700l5.400-.8z"/></svg></span>@endif
        <div>
            <h3>{{ $review->product?->name ?? 'Product removed' }}</h3>
            <p><span class="fb-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating) }}</span> · {{ $review->created_at->format('M j, Y · g:i A') }}</p>
        </div>
    </div>

    <dl class="do-facts">
        <div><dt>Customer</dt><dd>{{ $buyer }}</dd></div>
        <div><dt>Order</dt><dd><a class="fb-link" href="{{ route('seller.completed-orders.show', $review->order_id) }}">#{{ $review->order?->number ?? $review->order_id }}</a></dd></div>
    </dl>

    <blockquote class="fb-quote">{{ $review->comment }}</blockquote>

    @if($review->visibility === 'hidden')
        <p class="do-alert do-alert--info">This review is hidden while an admin checks it. You can't reply right now.</p>
    @elseif($review->reply)
        <section class="do-block">
            <h4>Your reply</h4>
            <div class="fb-reply">
                <p>{{ $review->reply->body }}</p>
                <small>Sent {{ $review->reply->created_at->format('M j, Y · g:i A') }}</small>
            </div>
        </section>
    @else
        <section class="do-block">
            <h4>Reply to {{ $buyer }}</h4>
            <form method="POST" action="{{ route('seller.feedback.reply', $review) }}" data-operation class="fb-form" data-draft-key="seller-{{ auth()->id() }}-review-reply-{{ $review->id }}" data-draft-ajax>
                @csrf
                <div class="fb-templates" aria-label="Quick replies">
                    @foreach(array_chunk($templates, 2) as [$label, $text])
                        <button type="button" class="do-chip" data-reply-template="{{ $text }}">{{ $label }}</button>
                    @endforeach
                </div>
                <label>
                    <span class="ops-sr-only">Your reply</span>
                    <textarea name="body" required minlength="2" maxlength="2000" rows="4" placeholder="Write your reply. The buyer will be notified." data-reply-field></textarea>
                </label>
                <div class="fb-counter"><span>Buyers see your reply</span><span data-reply-count>0 / 2000</span></div>
                <div class="do-alert do-alert--error" data-form-error role="alert" hidden></div>
                <button class="do-btn do-btn--primary" type="submit">
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/></svg>
                    Send reply
                </button>
            </form>
        </section>
    @endif

    @if($review->visibility !== 'hidden')
        <section class="do-block">
            @if($review->report)
                <p class="do-alert do-alert--info">You reported this review. It stays visible while the team checks it.</p>
            @else
                <details class="fb-report">
                    <summary><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg>Report this review</summary>
                    <form method="POST" action="{{ route('seller.feedback.report', $review) }}" data-operation class="fb-form" data-draft-key="seller-{{ auth()->id() }}-review-report-{{ $review->id }}" data-draft-ajax>
                        @csrf
                        <label>
                            <span class="fb-label">Why are you reporting it?</span>
                            <textarea name="reason" required minlength="10" maxlength="500" rows="3" placeholder="Example: The review is about a different product."></textarea>
                        </label>
                        <div class="do-alert do-alert--error" data-form-error role="alert" hidden></div>
                        <button class="do-btn do-btn--danger" type="submit">Send report</button>
                    </form>
                </details>
            @endif
        </section>
    @endif
</div>
