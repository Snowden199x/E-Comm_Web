{{--
    Company > Rider Payment. The fixed amount a Rider earns per delivered parcel, taken from the delivery fee.
    Fed by CompanyCashController::riderPay. Saving patches CompanyCashController::updateRiderPay.
    Look: the seller pastel lilac system. Styles: resources/css/logistics/rider-pay-theme.css (pushed last, tokens copied
    from seller/delivered-orders.css). No page script: the live split runs in the Alpine scope below, and uses the same
    integer centavo math with half-up rounding as DeliveryFeeDistributionCalculator, so the preview matches what is saved.
    Colors: Rider = sky, Vendo = lilac, your Company = mint (the same three as the cash page).
    Save is enabled only after the amount changes. Errors: the field message, plus the top banner.
    Loading: shell skeleton on open, spinner on Save changes. Empty states: none (a setting always has a value).
--}}
@php
    $saved = number_format((float) $company->rider_pay_per_parcel, 2, '.', '');
    $rateLabel = rtrim(rtrim(number_format($vendoRate, 2), '0'), '.');
@endphp

@push('lg-head')
    @vite('resources/css/logistics/rider-pay-theme.css')
@endpush

<x-logistics.shell title="Rider Payment" role="company">
    <div class="lgy"
         x-data="{
            saved: @js($saved),
            amount: @js((string) old('rider_pay_per_parcel', $saved)),
            fee: @js((int) round($exampleFee * 100)),
            bp: @js((int) round($vendoRate * 100)),
            max: @js((float) $maxPay),
            error: @js((string) $errors->first('rider_pay_per_parcel')),
            get cents() { const n = Number(this.amount); return this.amount.trim() === '' || Number.isNaN(n) ? 0 : Math.round(n * 100); },
            get dirty() { return Number(this.amount) !== Number(this.saved) || this.amount.trim() === ''; },
            get split() {
                const rider = Math.min(Math.max(0, this.cents), this.fee);
                const remaining = this.fee - rider;
                const vendo = Math.floor((remaining * this.bp + 5000) / 10000);
                return { rider, vendo, company: remaining - vendo, remaining };
            },
            peso(c) { return '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            width(c) { return this.fee ? (c / this.fee * 100) + '%' : '0%'; },
            check(event) {
                const text = this.amount.trim(), n = Number(text);
                if (text === '' || Number.isNaN(n)) this.error = 'Enter the pay as a number, for example 12.50.';
                else if (n < 0) this.error = 'The pay cannot be negative. Enter 0 or more.';
                else if (n > this.max) this.error = 'The pay cannot be more than ' + this.peso(this.max * 100) + ', the lowest delivery fee. Enter that amount or less.';
                else this.error = '';
                if (this.error) { event.preventDefault(); this.$refs.amount.focus(); }
            },
            reset() { this.amount = this.saved; this.error = ''; }
         }">

        <x-logistics.flash />

        <header class="lgy-head">
            <h1>Rider payment</h1>
            <p>The fixed amount a Rider earns for each parcel they deliver, taken from the delivery fee the Buyer pays. Pickup work is the Rider’s salary from your Company and is not part of this setting.</p>
        </header>

        <div class="lgy-grid">
            <section class="lgy-card" aria-labelledby="payTitle">
                <div class="lgy-card__head">
                    <h2 id="payTitle">Pay per delivered parcel</h2>
                    <p>Currently {{ '₱'.number_format((float) $saved, 2) }}.</p>
                </div>
                <form method="POST" action="{{ route('lgp.company.rider-pay.update') }}" class="lgy-form" @submit="check($event)" novalidate>
                    @csrf @method('PATCH')
                    <div class="lgy-field">
                        <label for="riderPay">Amount <span aria-hidden="true">*</span></label>
                        <div class="lgy-input" :class="{ 'has-error': error }">
                            <span aria-hidden="true">₱</span>
                            <input id="riderPay" name="rider_pay_per_parcel" type="number" step="0.01" min="0" max="{{ $maxPay }}" inputmode="decimal" x-model="amount" x-ref="amount" @input="error = ''" :aria-invalid="!!error" aria-describedby="riderPayHint" required>
                        </div>
                        <p class="lgy-err" x-show="error" x-cloak x-text="error" role="alert"></p>
                        <p class="lgy-hint" id="riderPayHint">Between ₱0.00 and ₱{{ number_format($maxPay, 2) }}, the lowest delivery fee, so the fee can always cover it.</p>
                    </div>
                    <div class="lgy-actions">
                        <button type="button" class="lgy-btn lgy-btn--soft" x-show="dirty" x-cloak @click="reset()">Undo changes</button>
                        <button type="submit" class="lgy-btn lgy-btn--primary" :disabled="!dirty">Save changes</button>
                    </div>
                </form>
                <p class="lgy-note"><svg class="lgy-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg> Each order keeps the pay in force when it was placed, so a change applies to new orders only.</p>
            </section>

            <section class="lgy-card" aria-labelledby="splitTitle">
                <div class="lgy-card__head">
                    <h2 id="splitTitle">How a ₱{{ number_format($exampleFee, 2) }} delivery fee is split</h2>
                    <p>The preview updates as you type.</p>
                </div>
                <div class="lgy-bar" role="img" :aria-label="'Rider ' + peso(split.rider) + ', Vendo ' + peso(split.vendo) + ', your Company ' + peso(split.company)">
                    <i class="lgy-bar__rider" :style="{ width: width(split.rider) }"></i>
                    <i class="lgy-bar__vendo" :style="{ width: width(split.vendo) }"></i>
                    <i class="lgy-bar__company" :style="{ width: width(split.company) }"></i>
                </div>
                <dl class="lgy-split">
                    <div><dt>Delivery fee paid by the Buyer</dt><dd>₱{{ number_format($exampleFee, 2) }}</dd></div>
                    <div><dt><i class="lgy-dot lgy-dot--rider"></i>Rider (your setting)</dt><dd x-text="peso(split.rider)">₱{{ number_format($example['rider_cents'] / 100, 2) }}</dd></div>
                    <div class="lgy-sub"><dt>Remaining balance</dt><dd x-text="peso(split.remaining)">₱{{ number_format($example['remaining_cents'] / 100, 2) }}</dd></div>
                    <div><dt><i class="lgy-dot lgy-dot--vendo"></i>Vendo commission <small>{{ $rateLabel }}% of the remaining balance, set by Vendo</small></dt><dd x-text="peso(split.vendo)">₱{{ number_format($example['vendo_cents'] / 100, 2) }}</dd></div>
                    <div class="lgy-total"><dt><i class="lgy-dot lgy-dot--company"></i>Your Company</dt><dd x-text="peso(split.company)">₱{{ number_format($example['logistics_cents'] / 100, 2) }}</dd></div>
                </dl>
            </section>
        </div>
    </div>
</x-logistics.shell>
