@php
    $fmt = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $icons = [
        'damaged' => 'M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8M9 6l6 4',
        'wrong_item' => 'M7 7h11l-3-3M17 17H6l3 3M18 7v0M6 17v0',
        'missing_item' => 'M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M9 17h6',
        'not_as_described' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
        'other' => 'M6 12h.01M12 12h.01M18 12h.01',
    ];
    $closingSoon = $daysLeft !== null && $daysLeft <= 2;
@endphp

<x-buyer.layout :title="'Return ' . $order->number . ' | Vendo'">
    @vite(['resources/css/buyer/support-theme.css'])

    <div class="sp-page sp-page--narrow" x-data="{
            reason: @js((string) old('reason')), details: @js((string) old('details')),
            reasonError: @js((string) $errors->first('reason')), detailsError: @js((string) $errors->first('details')),
            check(event) {
                this.reasonError = this.reason ? '' : 'No reason chosen. Pick the reason that fits best.';
                this.detailsError = (this.reason === 'other' && this.details.trim().length === 0) ? 'Details are empty. Tell the seller what happened, because you chose Other.' : '';
                if (this.reasonError || this.detailsError) {
                    event.preventDefault();
                    this.$store.banner.show({ type: 'error', title: 'Request not sent', text: 'Fix the highlighted fields, then select Send return request again.' });
                    (this.reasonError ? this.$refs.reasons.querySelector('input') : this.$refs.details).focus();
                }
            },
        }"
        x-init="@if ($errors->any()) $store.banner.show({ type: 'error', title: 'Request not sent', text: 'Fix the highlighted fields, then select Send return request again.' }); @endif">

        <nav class="sp-crumb" aria-label="Breadcrumb">
            <a href="{{ route('buyer.orders.index') }}">My orders</a><span>/</span>
            <a href="{{ route('buyer.orders.show', $order) }}">Order {{ $order->number }}</a><span>/</span><span>Return</span>
        </nav>

        <header class="sp-head">
            <div>
                <h1>Return order {{ $order->number }}</h1>
                <p>The seller reviews every return request. You are notified when they decide.</p>
            </div>
        </header>

        <div class="sp-stats sp-stats--two">
            <div class="sp-stat">
                <span class="sp-stat__icon sp-tone-mint"><svg class="sp-ico sp-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/></svg></span>
                <span class="sp-stat__text">Refund if approved<strong>{{ $fmt($order->payable_total) }}</strong><span>The amount you paid on delivery</span></span>
            </div>
            @if ($windowClosesAt)
                <div class="sp-stat">
                    <span class="sp-stat__icon {{ $closingSoon ? 'sp-tone-butter' : 'sp-tone-lilac' }}"><svg class="sp-ico sp-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span class="sp-stat__text">Requests close<strong>{{ $windowClosesAt->format('M j, Y') }}</strong><span>@if ($daysLeft !== null){{ $daysLeft === 0 ? 'Last day to send a request' : $daysLeft.' '.\Illuminate\Support\Str::plural('day', $daysLeft).' left' }}@endif</span></span>
                </div>
            @endif
        </div>

        <form action="{{ route('buyer.orders.return.store', $order) }}" method="POST" class="sp-card sp-form" novalidate @submit="check($event)">
            @csrf

            <fieldset class="sp-field" :class="reasonError ? 'has-error' : ''" style="border:0;padding:0;margin:0">
                <legend class="sp-label" style="margin-bottom:8px">Why are you returning it?</legend>
                <div class="sp-reasons" x-ref="reasons">
                    @foreach ($reasons as $key => $text)
                        <label class="sp-reason">
                            <input type="radio" name="reason" value="{{ $key }}" x-model="reason" @change="reasonError = ''" required>
                            <span class="sp-reason__icon"><svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icons[$key] ?? $icons['other'] }}"/></svg></span>
                            <span class="sp-reason__text">{{ $text }}</span>
                            <span class="sp-reason__tick"><svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
                        </label>
                    @endforeach
                </div>
                <p class="sp-error sp-fieldset-error" x-text="reasonError" x-show="reasonError" x-cloak>{{ $errors->first('reason') }}</p>
            </fieldset>

            <div class="sp-field" :class="detailsError ? 'has-error' : ''">
                <label for="details">Details <small x-text="reason === 'other' ? '(required)' : '(optional)'">(optional)</small></label>
                <textarea id="details" name="details" rows="4" maxlength="450" x-ref="details" x-model="details" @input="detailsError = ''"
                    placeholder="Say what you found, for example which item and what is wrong with it."></textarea>
                <div class="sp-hint"><span class="sp-error" x-text="detailsError" x-show="detailsError" x-cloak>{{ $errors->first('details') }}</span><span class="tabular-nums"><span x-text="details.length">0</span> / 450</span></div>
            </div>

            <div class="sp-note">
                <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                <p>If the seller approves, a rider collects the item and the refund is recorded. If they decline, you see their reason on the order.</p>
            </div>

            <div class="sp-actions">
                <button type="submit" class="sp-btn sp-btn--primary">
                    <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                    Send return request
                </button>
                <a href="{{ route('buyer.orders.show', $order) }}" class="sp-btn sp-btn--soft">Keep the order</a>
            </div>
        </form>
    </div>
</x-buyer.layout>
