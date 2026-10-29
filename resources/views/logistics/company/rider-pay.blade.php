<x-logistics.shell title="Rider Payment" role="company">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Rider Payment</h1><p>The fixed amount a Rider earns for each parcel they deliver, taken from the delivery fee the Buyer pays. Pickup work is the Rider's salary from your Company and is not part of this setting.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section class="lg-card" aria-labelledby="riderPayTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="riderPayTitle">Pay per delivered parcel</h2></div>
            <form method="POST" action="{{ route('lgp.company.rider-pay.update') }}" class="lg-card__body lg-form-grid">
                @csrf @method('PATCH')
                <div class="lg-field">
                    <label for="riderPay">Amount (₱)</label>
                    <input id="riderPay" name="rider_pay_per_parcel" type="number" step="0.01" min="0" max="{{ $maxPay }}" class="lg-input" value="{{ old('rider_pay_per_parcel', number_format((float) $company->rider_pay_per_parcel, 2, '.', '')) }}" required>
                    <div class="lg-cell-sub">Between ₱0.00 and ₱{{ number_format($maxPay, 2) }}, the lowest delivery fee, so the fee can always cover it.</div>
                </div>
                <div class="is-wide"><button type="submit" class="lg-btn">Save rider pay</button></div>
            </form>
        </section>

        <section class="lg-card" aria-labelledby="exampleTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="exampleTitle">How a ₱{{ number_format($exampleFee, 2) }} delivery fee is split</h2></div>
            <div class="lg-table-wrap"><table class="lg-table"><tbody>
                <tr><td>Delivery fee paid by the Buyer</td><td>₱{{ number_format($exampleFee, 2) }}</td></tr>
                <tr><td>Rider (your setting)</td><td>₱{{ number_format($example['rider_cents'] / 100, 2) }}</td></tr>
                <tr><td>Remaining balance</td><td>₱{{ number_format($example['remaining_cents'] / 100, 2) }}</td></tr>
                <tr><td>Vendo commission ({{ rtrim(rtrim(number_format($vendoRate, 2), '0'), '.') }}% of the remaining balance, set by Vendo)</td><td>₱{{ number_format($example['vendo_cents'] / 100, 2) }}</td></tr>
                <tr><td><strong>Your Company</strong></td><td><strong>₱{{ number_format($example['logistics_cents'] / 100, 2) }}</strong></td></tr>
            </tbody></table></div>
            <div class="lg-card__body"><p class="lg-cell-sub">Each order keeps the rider pay in force when it was placed, so a change here applies to new orders only.</p></div>
        </section>
    </div>
</x-logistics.shell>
