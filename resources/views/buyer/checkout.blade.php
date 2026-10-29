{{--
    Buyer checkout.
    Posts exactly what CheckoutController@store already validates:
      items[], checkout_revision, shipping_address, shipping_province_code, shipping_city_code, payment_mode=cod.
    The address chosen on this page fills the three shipping_* fields. There is no saved address book on the
    server yet, so extra addresses a buyer adds live in this browser tab (sessionStorage) until they place the order.

    Message for the seller: one optional note per shop (posted as seller_notes[<seller id>]).
    Checkout stores each note on that shop's order.
--}}
@php
    use Illuminate\Support\Str;

    $buyer = auth()->user();
    $sellerNotesEnabled = (bool) ($sellerNotesEnabled ?? false);
    $fmt = fn ($amount) => '₱' . number_format($amount, 2);
    $itemCount = (int) $cartItems->sum('quantity');
    $subtotal = $cartItems->sum(fn ($item) => $item->quantity * $item->unit_price);
    $sellerGroups = $cartItems->groupBy(fn ($item) => $item->product->seller_id);

    // ---- Address options -------------------------------------------------
    $provinceName = fn ($code) => $locationOptions[$code]['name'] ?? null;
    $cityName = fn ($province, $city) => $locationOptions[$province]['cities'][$city] ?? null;

    $registeredProvince = $defaultProvinceCode ? $provinceName($defaultProvinceCode) : null;
    $registeredCity = ($defaultProvinceCode && $defaultCityCode) ? $cityName($defaultProvinceCode, $defaultCityCode) : null;
    $registeredUsable = filled($defaultAddress) && $registeredProvince && $registeredCity;

    $addresses = [[
        'id' => 'registered',
        'label' => 'Registered address',
        'registered' => true,
        'custom' => false,
        'usable' => (bool) $registeredUsable,
        'line' => (string) $defaultAddress,
        'provinceCode' => (string) $defaultProvinceCode,
        'cityCode' => (string) $defaultCityCode,
        'province' => $registeredProvince,
        'city' => $registeredCity,
    ]];
    $selectedId = $registeredUsable ? 'registered' : null;
    $restoreSaved = true;

    // After a failed submit, keep the address the buyer had picked
    $oldLine = trim((string) old('shipping_address'));
    $oldProvince = (string) old('shipping_province_code');
    $oldCity = (string) old('shipping_city_code');
    $sameAsRegistered = $registeredUsable && $oldLine === trim((string) $defaultAddress)
        && $oldProvince === (string) $defaultProvinceCode && $oldCity === (string) $defaultCityCode;
    if ($oldLine !== '' && $oldProvince !== '' && $oldCity !== '' && ! $sameAsRegistered && $provinceName($oldProvince) && $cityName($oldProvince, $oldCity)) {
        $addresses[] = [
            'id' => 'previous', 'label' => 'Address you entered', 'registered' => false, 'custom' => false, 'usable' => true,
            'line' => $oldLine, 'provinceCode' => $oldProvince, 'cityCode' => $oldCity,
            'province' => $provinceName($oldProvince), 'city' => $cityName($oldProvince, $oldCity),
        ];
        $selectedId = 'previous';
        $restoreSaved = false;
    }

    $checkoutConfig = [
        'addresses' => $addresses,
        'selected' => $selectedId,
        'restoreSaved' => $restoreSaved,
        'storageKey' => 'vendo.checkout.addresses.' . auth()->id(),
        'voucherQuoteUrl' => route('buyer.checkout.voucher-quote'),
        'shippingQuoteUrl' => route('buyer.checkout.shipping-quote'),
        'itemIds' => $cartItems->pluck('id')->all(),
        'sellerIds' => $sellerGroups->keys()->map(fn ($id) => (string) $id)->values()->all(),
        'subtotal' => (float) $subtotal,
        'voucherCodes' => old('voucher_codes', []),
    ];

    $primaryButton = 'inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 ease-vendo hover:bg-[#52245b]';
    $fieldClass = 'mt-1 block w-full rounded-md border-[#e5dce7] bg-white px-3 py-2 text-[13px] text-[#2b1730] placeholder:text-[#b7a9ba] focus:border-[#805487] focus:ring-[#805487] disabled:bg-[#faf7fb] disabled:text-[#b7a9ba]';
@endphp

<x-buyer.layout title="Checkout | Vendo">
    <form action="{{ route('buyer.checkout.store') }}" method="POST"
        x-data="checkoutAddress(@js($checkoutConfig))" @submit="onSubmit($event)"
        class="vb-enter mx-auto max-w-[1200px] px-3 pb-14 pt-4 sm:px-4">
        @csrf
        @foreach ($cartItems as $item)<input type="hidden" name="items[]" value="{{ $item->id }}">@endforeach
        <input type="hidden" name="checkout_revision" value="{{ $checkoutRevision }}">
        <input type="hidden" name="payment_mode" value="cod">
        <input type="hidden" name="shipping_address" :value="current ? current.line : ''">
        <input type="hidden" name="shipping_province_code" :value="current ? current.provinceCode : ''">
        <input type="hidden" name="shipping_city_code" :value="current ? current.cityCode : ''">
        <input type="hidden" name="shipping_latitude" :value="current?.latitude ?? ''">
        <input type="hidden" name="shipping_longitude" :value="current?.longitude ?? ''">
        @foreach ($sellerGroups as $sellerKey => $sellerItems)
            <input type="hidden" name="shipping_quotes[{{ $sellerKey }}]" :value="shippingQuotes[{{ $sellerKey }}]?.shipping_fee || ''">
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-[20px] font-semibold text-[#2b1730]">Checkout</h1>
            <a href="{{ route('buyer.cart.index') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
                Back to cart
            </a>
        </div>

        @foreach (['checkout_revision', 'items', 'quantity'] as $field)
            @error($field)
                <p class="mt-3 rounded-md border border-[#f0c9d0] bg-[#fdf1f3] px-3 py-2.5 text-[13px] text-[#a32b43]" role="alert">{{ $message }}</p>
            @enderror
        @endforeach

        <div class="mt-3 grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="min-w-0 space-y-4">

                <!-- 1. Delivery address -->
                <section aria-labelledby="address-heading" class="rounded-lg border border-[#eee6ef] bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-t-lg bg-[#f6f2f7] px-4 py-3 sm:px-5">
                        <div>
                            <h2 id="address-heading" class="text-[15px] font-semibold text-[#402143]">Delivery address</h2>
                            <p class="text-[12px] text-[#7a6a7e]">Choose where this order should be delivered.</p>
                        </div>
                        <button type="button" @click="openModal()"
                            class="inline-flex h-9 items-center gap-1.5 rounded-md border border-[#805487] bg-white px-3.5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 ease-vendo hover:bg-[#f5ecf6] active:scale-[0.98]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            Add new address
                        </button>
                    </div>

                    <div class="space-y-3 p-4 sm:p-5" role="radiogroup" aria-labelledby="address-heading">
                        <template x-for="a in addresses" :key="a.id">
                            <div class="relative">
                                <label class="block" :class="a.usable ? 'cursor-pointer' : 'cursor-not-allowed'">
                                    <input type="radio" name="address_choice" class="peer sr-only" :value="a.id" x-model="selected" :disabled="!a.usable">
                                    <span class="flex items-start gap-3 rounded-lg border p-4 pr-14 transition-colors duration-200 ease-vendo peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#805487]"
                                        :class="[
                                            selected === a.id ? 'border-[#805487] bg-[#faf5fa] shadow-[inset_0_0_0_1px_#805487]' : 'border-[#e5dce7] bg-white',
                                            a.usable ? (selected === a.id ? '' : 'hover:border-[#c9a9ce]') : 'opacity-70'
                                        ]">
                                        <span class="mt-0.5 grid h-[18px] w-[18px] flex-shrink-0 place-items-center rounded-full border-2 transition-colors duration-200"
                                            :class="selected === a.id ? 'border-[#805487] bg-[#805487]' : 'border-[#c9bccc] bg-white'" aria-hidden="true">
                                            <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="selected === a.id"></span>
                                        </span>
                                        <span class="min-w-0 text-[13px]">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-[#2b1730]" x-text="a.label"></span>
                                                <span x-show="a.registered" class="rounded bg-[#e8c874] px-1.5 py-0.5 text-[11px] font-semibold text-[#402143]">Default</span>
                                            </span>
                                            <template x-if="a.registered">
                                                <span class="mt-0.5 block text-[#5b4a60]">{{ $buyer->name }}@if (filled($buyer->phone_number)), {{ $buyer->phone_number }}@endif</span>
                                            </template>
                                            <template x-if="a.usable">
                                                <span class="mt-0.5 block break-words text-[#3d2a42]" x-text="a.line + ', ' + a.city + ', ' + a.province"></span>
                                            </template>
                                            <template x-if="!a.usable">
                                                <span class="mt-0.5 block text-[#a32b43]">This address is incomplete, so it cannot be used for delivery. Add a new address below, or update it in
                                                    <a href="{{ route('buyer.account.index') }}" class="underline underline-offset-2">Account Management</a>.</span>
                                            </template>
                                        </span>
                                    </span>
                                </label>
                                <div x-show="a.usable" class="ml-14 mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <button type="button" @click="captureLocation(a.id)" :disabled="locatingAddress === a.id"
                                        class="text-[12px] font-medium text-[#52245b] underline underline-offset-2 disabled:opacity-60"
                                        x-text="locatingAddress === a.id ? 'Getting location…' : (a.latitude && a.longitude ? 'Update delivery pin' : 'Set delivery pin')"></button>
                                    <span class="text-[11px] text-[#6b5b70]" x-show="a.latitude && a.longitude">Pin saved</span>
                                </div>
                                <button type="button" x-show="a.custom" x-cloak @click="remove(a.id)" :aria-label="'Remove ' + a.label"
                                    class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-full text-[#8a7a8e] transition-colors duration-200 hover:bg-[#fdf1f3] hover:text-[#a32b43]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12h10l1-12M9 7V4h6v3" /></svg>
                                </button>
                            </div>
                        </template>

                        <p x-show="!current" x-cloak class="rounded-md bg-[#fdf1f3] px-3 py-2 text-[12px] text-[#a32b43]" role="alert">Add a delivery address to place your order.</p>
                        @foreach (['shipping_address', 'shipping_province_code', 'shipping_city_code', 'shipping_latitude', 'shipping_longitude'] as $field)
                            @error($field)<p class="rounded-md bg-[#fdf1f3] px-3 py-2 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
                        @endforeach
                        <p class="text-[11px] text-[#7a6a7e]">A delivery pin helps the assigned Rider find the destination. It is shared with Vendo and the Rider assigned to this order.</p>
                        <p x-show="locationError" x-cloak class="rounded-md bg-[#fdf1f3] px-3 py-2 text-[12px] text-[#a32b43]" role="alert" x-text="locationError"></p>
                    </div>
                </section>

                <!-- 2. Items, grouped by shop -->
                <section aria-labelledby="items-heading" class="rounded-lg border border-[#eee6ef] bg-white">
                    <div class="rounded-t-lg bg-[#f6f2f7] px-4 py-3 sm:px-5">
                        <h2 id="items-heading" class="text-[15px] font-semibold text-[#402143]">Your items</h2>
                        @if ($sellerGroups->count() > 1)
                            <p class="text-[12px] text-[#7a6a7e]">Items from different shops are placed as separate orders.</p>
                        @endif
                    </div>

                    <div class="divide-y divide-[#f1e8f2]">
                        @foreach ($sellerGroups as $sellerKey => $sellerItems)
                            @php
                                $seller = $sellerItems->first()->product->seller;
                                $shopName = $seller?->sellerDetail?->business_name ?: ($seller?->name ?? 'Store');
                                $shopTotal = $sellerItems->sum(fn ($item) => $item->quantity * $item->unit_price);
                            @endphp
                            <div class="px-4 py-4 sm:px-5">
                                <p class="flex items-center gap-2 text-[13px] font-semibold text-[#402143]">
                                    <svg class="h-4 w-4 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l1.6-5h14.8L21 9" /><path d="M3 9a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" /><path d="M5 12v8h14v-8" /></svg>
                                    @if ($seller)
                                        <a href="{{ route('buyer.sellers.show', $seller) }}" class="transition-colors duration-200 hover:text-[#805487]">{{ $shopName }}</a>
                                    @else
                                        {{ $shopName }}
                                    @endif
                                </p>

                                <ul class="mt-3 space-y-3">
                                    @foreach ($sellerItems as $item)
                                        @php
                                            $image = $item->product->images->first();
                                            $options = array_filter([
                                                $item->variant?->label,
                                                $item->color ? 'Color: ' . $item->color : null,
                                                $item->size ? 'Size: ' . $item->size : null,
                                            ]);
                                        @endphp
                                        <li class="flex gap-3">
                                            <a href="{{ route('buyer.products.show', $item->product) }}" class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md border border-[#eee6ef] bg-[#faf7fb]" tabindex="-1" aria-hidden="true">
                                                @if ($image)
                                                    <img src="{{ asset('storage/' . $image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @endif
                                            </a>
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ route('buyer.products.show', $item->product) }}" class="line-clamp-2 text-[13px] leading-[18px] text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $item->product->name }}</a>
                                                @if ($options)<p class="mt-0.5 text-[12px] text-[#8a7a8e]">{{ implode(', ', $options) }}</p>@endif
                                                <p class="mt-1 text-[12px] text-[#7a6a7e]">{{ $fmt($item->unit_price) }} each, quantity {{ $item->quantity }}</p>
                                            </div>
                                            <p class="flex-shrink-0 text-[13px] font-semibold text-[#52245b]">{{ $fmt($item->quantity * $item->unit_price) }}</p>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-dashed border-[#e5dce7] pt-3 text-[12px]">
                                    <span class="text-[#7a6a7e]">Shipping · <span x-show="quotingShipping" x-cloak>Getting route quote…</span><span x-show="shippingQuotes[{{ $sellerKey }}]" x-cloak x-text="shippingQuotes[{{ $sellerKey }}] ? shippingQuotes[{{ $sellerKey }}].hub_count + ' hubs' : ''"></span></span>
                                    <span class="font-semibold text-[#402143]" x-show="shippingQuotes[{{ $sellerKey }}]" x-cloak x-text="shippingQuotes[{{ $sellerKey }}] ? '₱' + Number(shippingQuotes[{{ $sellerKey }}].shipping_fee).toFixed(2) : ''"></span>
                                </div>
                                <p x-show="shippingQuoteError" x-cloak class="mt-1 text-[12px] text-[#a32b43]" role="alert" x-text="shippingQuoteError"></p>

                                @if ($sellerNotesEnabled)
                                    <div class="mt-3" x-data="{ note: @js((string) old('seller_notes.' . $sellerKey, '')) }">
                                        <label for="seller-note-{{ $sellerKey }}" class="text-[12px] font-medium text-[#5b4a60]">Message for the seller <span class="font-normal text-[#6f5f73]">(optional)</span></label>
                                        <textarea id="seller-note-{{ $sellerKey }}" name="seller_notes[{{ $sellerKey }}]" x-model="note" rows="2" maxlength="300"
                                            placeholder="Anything {{ $shopName }} should know, like a preferred delivery time or a gift message"
                                            class="{{ $fieldClass }}"></textarea>
                                        <p class="mt-1 flex justify-between gap-3 text-[11px] text-[#6f5f73]">
                                            <span>The seller sees this with your order.</span>
                                            <span><span x-text="note.length">0</span>/300</span>
                                        </p>
                                        @error('seller_notes.' . $sellerKey)<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                @endif

                                <div class="mt-3 border-t border-dashed border-[#e5dce7] pt-3">
                                    <label for="voucher-{{ $sellerKey }}" class="text-[12px] font-medium text-[#5b4a60]">Shop voucher <span class="font-normal text-[#6f5f73]">(optional)</span></label>
                                    @if (!empty($voucherOptions[$sellerKey] ?? []))
                                        <label for="voucher-choice-{{ $sellerKey }}" class="mt-2 block text-[11px] font-medium text-[#6f5f73]">Shop vouchers matching these items</label>
                                        <select id="voucher-choice-{{ $sellerKey }}" x-model="voucherSelections[{{ $sellerKey }}]"
                                            @change="selectVoucher({{ $sellerKey }}, $event)" class="{{ $fieldClass }} !mt-1">
                                            <option value="">Choose a shop voucher</option>
                                            @foreach ($voucherOptions[$sellerKey] as $voucherOption)
                                                <option value="{{ $voucherOption['code'] }}" @disabled(!$voucherOption['eligible'])>{{ $voucherOption['name'] }} · {{ $voucherOption['code'] }} · {{ $voucherOption['discount_label'] }}@if (!$voucherOption['eligible']) · {{ $voucherOption['reason'] }}@endif</option>
                                            @endforeach
                                        </select>
                                        <ul class="mt-2 space-y-1.5">
                                            @foreach ($voucherOptions[$sellerKey] as $voucherOption)
                                                <li class="rounded-md bg-[#faf7fb] px-2.5 py-2 text-[11px] leading-4 text-[#6f5f73]">
                                                    <span class="font-semibold text-[#52245b]">{{ $voucherOption['code'] }}</span>
                                                    <span> — {{ $voucherOption['scope_label'] }}.</span>
                                                    @if ($voucherOption['min_spend'] > 0)<span> Minimum spend ₱{{ number_format($voucherOption['min_spend'], 2) }}.</span>@endif
                                                    @if ($voucherOption['starts_at'])<span> Starts {{ $voucherOption['starts_at'] }}.</span>@endif
                                                    @if ($voucherOption['ends_at'])<span> Valid until {{ $voucherOption['ends_at'] }}.</span>@endif
                                                    @if ($voucherOption['remaining_uses'] !== null)<span> {{ $voucherOption['remaining_uses'] }} use(s) left.</span>@endif
                                                    <span> Up to {{ $voucherOption['per_buyer_limit'] }} use(s) per Buyer.</span>
                                                    @if (!$voucherOption['eligible'])<span class="block font-medium text-[#a32b43]">{{ $voucherOption['reason'] }}</span>@endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <label for="voucher-{{ $sellerKey }}" class="mt-2 block text-[11px] font-medium text-[#6f5f73]">Or enter a voucher code</label>
                                    <div class="mt-1 flex gap-2">
                                        <input id="voucher-{{ $sellerKey }}" type="text" name="voucher_codes[{{ $sellerKey }}]" maxlength="12" autocomplete="off"
                                            x-model="voucherCodes[{{ $sellerKey }}]" @input="voucherChanged({{ $sellerKey }}, $event)"
                                            placeholder="Enter code" class="{{ $fieldClass }} !mt-0 min-w-0 flex-1 uppercase">
                                        <button type="button" @click="applyVoucher({{ $sellerKey }})" :disabled="applyingVoucher[{{ $sellerKey }}]"
                                            class="rounded-md border border-[#805487] px-3 text-[12px] font-medium text-[#52245b] disabled:opacity-50">Apply</button>
                                    </div>
                                    <p x-show="appliedVoucherCodes[{{ $sellerKey }}]" x-cloak class="mt-1 text-[12px] text-[#2e6b46]"
                                       x-text="'Discount: ₱' + Number(voucherDiscounts[{{ $sellerKey }}] || 0).toFixed(2)"></p>
                                    <p x-show="voucherErrors[{{ $sellerKey }}]" x-cloak class="mt-1 text-[12px] text-[#a32b43]" role="alert" x-text="voucherErrors[{{ $sellerKey }}]"></p>
                                    @error('voucher_codes.' . $sellerKey)<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
                                </div>

                                @if ($sellerGroups->count() > 1)
                                    <p class="mt-3 flex justify-between border-t border-dashed border-[#e5dce7] pt-3 text-[12px] text-[#5b4a60]">
                                        <span>Order from this shop</span><span class="font-semibold text-[#2b1730]">{{ $fmt($shopTotal) }}</span>
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- 3. Payment -->
                <section aria-labelledby="payment-heading" class="rounded-lg border border-[#eee6ef] bg-white">
                    <h2 id="payment-heading" class="rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5">Payment method</h2>
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start gap-3 rounded-lg border border-[#805487] bg-[#faf5fa] p-4 shadow-[inset_0_0_0_1px_#805487]">
                            <span class="mt-0.5 grid h-[18px] w-[18px] flex-shrink-0 place-items-center rounded-full border-2 border-[#805487] bg-[#805487]" aria-hidden="true"><span class="h-1.5 w-1.5 rounded-full bg-white"></span></span>
                            <div class="text-[13px]">
                                <p class="font-semibold text-[#2b1730]">Cash on delivery</p>
                                <p class="mt-0.5 text-[#5b4a60]">Pay in cash when your order is delivered. Other payment methods are not available yet.</p>
                                <p class="mt-2 font-semibold text-[#52245b]" x-show="payableTotal > 0" x-cloak>Pay <span x-text="'₱' + payableTotal.toFixed(2)"></span> in cash to the rider (items plus delivery fee).</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Summary -->
            <aside aria-labelledby="summary-heading" class="rounded-lg border border-[#eee6ef] bg-white lg:sticky lg:top-[124px]">
                <h2 id="summary-heading" class="rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5">Order summary</h2>
                <div class="p-4 sm:p-5">
                    <dl class="space-y-2.5 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Items ({{ number_format($itemCount) }})</dt><dd class="text-[#2b1730]">{{ $fmt($subtotal) }}</dd></div>
                        <div x-show="voucherDiscount > 0" x-cloak class="flex justify-between gap-3 text-[#2e6b46]"><dt>Shop vouchers</dt><dd x-text="'−₱' + voucherDiscount.toFixed(2)"></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Shipping fee</dt><dd class="text-[#2b1730]" x-text="quotingShipping ? 'Calculating…' : '₱' + shippingFeeTotal.toFixed(2)">₱0.00</dd></div>
                        <div class="flex items-baseline justify-between gap-3 border-t border-[#f1e8f2] pt-3"><dt class="font-semibold text-[#2b1730]">Total</dt><dd class="text-[22px] font-semibold leading-none text-[#52245b]" x-text="'₱' + payableTotal.toFixed(2)">{{ $fmt($subtotal) }}</dd></div>
                    </dl>
                    <p class="mt-1.5 text-[11px] text-[#8a7a8e]">₱45 minimum covers up to two hubs. Each additional hub adds ₱10.</p>

                    <div class="mt-4 rounded-md bg-[#f6f2f7] px-3 py-2 text-[12px] text-[#5b4a60]" x-show="current" x-cloak>
                        <p class="text-[#8a7a8e]">Delivering to</p>
                        <p class="mt-0.5 break-words text-[#2b1730]" x-text="current ? current.line + ', ' + current.city + ', ' + current.province : ''"></p>
                    </div>

                    <button type="submit" :disabled="!current || !shippingQuoteReady || quotingShipping || submitting"
                        class="{{ $primaryButton }} mt-4 h-11 w-full text-[14px] hover:shadow-[0_12px_20px_-12px_rgba(64,33,67,0.9)] active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-[#c9bccc] disabled:shadow-none">
                        <span x-text="submitting ? 'Placing order…' : 'Place order'">Place order</span>
                    </button>
                    <p class="mt-2 text-center text-[11px] text-[#8a7a8e]">By placing your order you agree to pay <span x-text="'₱' + payableTotal.toFixed(2)">{{ $fmt($subtotal) }}</span> on delivery.</p>
                </div>
            </aside>
        </div>

        <!-- Add address dialog -->
        <div x-show="modal" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="address-dialog-title" @keydown.escape.window="closeModal()"
            x-effect="document.body.style.overflow = modal ? 'hidden' : ''">
            <div x-show="modal" @click="closeModal()"
                x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-[#1b0b1e]/50"></div>

            <div x-show="modal"
                x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-3 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100" x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
                class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:max-w-[480px] sm:rounded-2xl">
                <button type="button" @click="closeModal()" aria-label="Close"
                    class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-full text-[#8a7a8e] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>

                <h2 id="address-dialog-title" class="pr-8 text-[16px] font-semibold text-[#2b1730]">Add a delivery address</h2>
                <p class="mt-1 text-[12px] text-[#7a6a7e]">This address is used for this checkout. Your registered address stays on your account.</p>

                <div class="mt-4 space-y-3.5">
                    <div>
                        <label for="addr-label" class="text-[12px] font-medium text-[#5b4a60]">Label <span class="font-normal text-[#9a8a9d]">(optional)</span></label>
                        <input id="addr-label" type="text" x-ref="label" x-model="draft.label" maxlength="40" placeholder="Home, office, parents' house"
                            @keydown.enter.prevent="save()" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label for="addr-line" class="text-[12px] font-medium text-[#5b4a60]">House number, street, barangay and ZIP code</label>
                        <textarea id="addr-line" x-model="draft.line" rows="3" maxlength="300" class="{{ $fieldClass }}"
                            :aria-invalid="!!errors.line" aria-describedby="addr-line-error"></textarea>
                        <p id="addr-line-error" class="mt-1 text-[12px] text-[#a32b43]" x-show="errors.line" x-text="errors.line"></p>
                    </div>
                    <div class="grid gap-3.5 sm:grid-cols-2">
                        <div>
                            <label for="addr-province" class="text-[12px] font-medium text-[#5b4a60]">Province / Metro Manila</label>
                            <select id="addr-province" x-model="draft.provinceCode" @change="draft.cityCode = ''" class="{{ $fieldClass }}" :aria-invalid="!!errors.province">
                                <option value="">Select province</option>
                                @foreach ($locationOptions as $code => $province)
                                    <option value="{{ $code }}">{{ $province['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[12px] text-[#a32b43]" x-show="errors.province" x-text="errors.province"></p>
                        </div>
                        <div>
                            <label for="addr-city" class="text-[12px] font-medium text-[#5b4a60]">City / municipality</label>
                            <select id="addr-city" x-model="draft.cityCode" :disabled="!draft.provinceCode" class="{{ $fieldClass }}" :aria-invalid="!!errors.city">
                                <option value="">Select city</option>
                                <template x-for="[code, name] in cities" :key="code">
                                    <option :value="code" x-text="name"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-[12px] text-[#a32b43]" x-show="errors.city" x-text="errors.city"></p>
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="captureDraftLocation()" :disabled="locatingAddress === 'draft'"
                            class="inline-flex h-9 items-center rounded-md border border-[#805487] px-3.5 text-[12px] font-medium text-[#52245b] hover:bg-[#f5ecf6] disabled:opacity-60"
                            x-text="locatingAddress === 'draft' ? 'Getting location…' : (draft.latitude && draft.longitude ? 'Update delivery pin' : 'Use this device’s location')"></button>
                        <p class="mt-1 text-[11px] text-[#7a6a7e]">Use this only when the device is at the delivery address. The pin is sent to Vendo and the assigned Rider.</p>
                        <p x-show="locationError" x-cloak class="mt-1 text-[12px] text-[#a32b43]" role="alert" x-text="locationError"></p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2.5">
                    <button type="button" @click="closeModal()"
                        class="inline-flex h-10 items-center rounded-md border border-[#e5dce7] px-5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">Cancel</button>
                    <button type="button" @click="save()" class="{{ $primaryButton }}">Use this address</button>
                </div>
            </div>
        </div>
    </form>

    <script>
    window.vendoLocations = @js($locationOptions);
    window.checkoutAddress = (config) => ({
        addresses: config.addresses,
        selected: config.selected,
        storageKey: config.storageKey,
        locations: window.vendoLocations,
        modal: false,
        submitting: false,
        voucherCodes: config.voucherCodes || {},
        shippingQuotes: {},
        shippingQuoteError: '',
        quotingShipping: false,
        locatingAddress: null,
        locationError: '',
        voucherSelections: config.voucherCodes || {},
        appliedVoucherCodes: {},
        voucherDiscounts: {},
        voucherErrors: {},
        applyingVoucher: {},
        errors: {},
        draft: { label: '', line: '', provinceCode: '', cityCode: '', latitude: null, longitude: null },

        init() {
            // Bring back addresses added earlier in this tab (kept for two hours, like other form drafts)
            try {
                const saved = JSON.parse(sessionStorage.getItem(this.storageKey) || 'null');
                if (saved && saved.savedAt > Date.now() - 2 * 60 * 60 * 1000 && Array.isArray(saved.addresses)) {
                    saved.addresses.forEach(a => {
                        const existing = this.addresses.find(x => x.id === a.id);
                        if (existing) { existing.latitude = a.latitude ?? existing.latitude; existing.longitude = a.longitude ?? existing.longitude; }
                        else this.addresses.push(a);
                    });
                    if (config.restoreSaved && saved.selected && this.addresses.some(a => a.id === saved.selected && a.usable)) {
                        this.selected = saved.selected;
                    }
                }
            } catch (_) {}
            if (!this.addresses.some(a => a.id === this.selected && a.usable)) {
                this.selected = (this.addresses.find(a => a.usable) || {}).id || null;
            }
            for (const [sellerId, code] of Object.entries(this.voucherCodes)) {
                if (code) this.applyVoucher(sellerId);
            }
            // Browser back/forward restores the page from cache: allow another submit
            window.addEventListener('pageshow', () => { this.submitting = false; });
            this.$watch('selected', () => { this.persist(); this.refreshShippingQuote(); });
            if (this.current) this.refreshShippingQuote();
            if (!this.current) this.$nextTick(() => this.openModal());
        },

        get current() { return this.addresses.find(a => a.id === this.selected && a.usable) || null; },
        get cities() { return Object.entries((this.locations[this.draft.provinceCode] || {}).cities || {}); },
        get voucherDiscount() { return Object.values(this.voucherDiscounts).reduce((sum, amount) => sum + Number(amount || 0), 0); },
        get shippingFeeTotal() { return Object.values(this.shippingQuotes).reduce((sum, quote) => sum + Number(quote.shipping_fee || 0), 0); },
        get shippingQuoteReady() { return config.sellerIds.every(id => this.shippingQuotes[id]) && !this.shippingQuoteError; },
        get payableTotal() { return Math.max(0, Number(config.subtotal) - this.voucherDiscount + this.shippingFeeTotal); },

        async refreshShippingQuote() {
            const address = this.current;
            this.shippingQuotes = {};
            this.shippingQuoteError = '';
            if (!address) return;
            const selectedId = this.selected;
            this.quotingShipping = true;
            try {
                const response = await fetch(config.shippingQuoteUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ items: config.itemIds, shipping_province_code: address.provinceCode,
                        shipping_city_code: address.cityCode }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Could not calculate shipping for this address.');
                if (this.selected !== selectedId) return;
                this.shippingQuotes = data.quotes || {};
            } catch (error) {
                if (this.selected === selectedId) this.shippingQuoteError = error.message;
            } finally {
                if (this.selected === selectedId) this.quotingShipping = false;
            }
        },

        voucherChanged(sellerId, event = null) {
            const code = String(event ? event.target.value : this.voucherCodes[sellerId] || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (event) event.target.value = code;
            this.voucherCodes[sellerId] = code;
            this.voucherSelections[sellerId] = '';
            this.voucherDiscounts[sellerId] = 0;
            this.appliedVoucherCodes[sellerId] = '';
            this.voucherErrors[sellerId] = '';
        },

        selectVoucher(sellerId, event) {
            const code = String(event.target.value || '');
            this.voucherSelections[sellerId] = code;
            this.voucherCodes[sellerId] = code;
            this.voucherDiscounts[sellerId] = 0;
            this.appliedVoucherCodes[sellerId] = '';
            this.voucherErrors[sellerId] = '';
            if (this.voucherCodes[sellerId]) this.applyVoucher(sellerId);
        },

        async applyVoucher(sellerId) {
            const code = String(this.voucherCodes[sellerId] || '').trim();
            if (!code) { this.voucherChanged(sellerId); return; }
            this.applyingVoucher[sellerId] = true;
            this.voucherErrors[sellerId] = '';
            try {
                const response = await fetch(config.voucherQuoteUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ items: config.itemIds, seller_id: sellerId, code }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Could not apply this voucher.');
                if (this.voucherCodes[sellerId] !== code) return;
                this.voucherDiscounts[sellerId] = Number(data.discount);
                this.appliedVoucherCodes[sellerId] = code;
            } catch (error) {
                this.voucherErrors[sellerId] = error.message;
            } finally {
                this.applyingVoucher[sellerId] = false;
            }
        },

        persist() {
            try {
                sessionStorage.setItem(this.storageKey, JSON.stringify({
                    savedAt: Date.now(),
                    selected: this.selected,
                    addresses: this.addresses
                        .filter(a => a.custom || (a.latitude && a.longitude))
                        .map(a => a.custom ? a : { id: a.id, latitude: a.latitude, longitude: a.longitude }),
                }));
            } catch (_) {}
        },

        openModal() {
            this.errors = {};
            this.draft = { label: '', line: '', provinceCode: '', cityCode: '', latitude: null, longitude: null };
            this.modal = true;
            this.$nextTick(() => this.$refs.label && this.$refs.label.focus());
        },
        closeModal() { this.modal = false; },

        async locate(target, addressId = null) {
            this.locationError = '';
            if (!navigator.geolocation) {
                this.locationError = 'This browser does not provide device location.';
                return;
            }
            this.locatingAddress = target;
            try {
                const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true, timeout: 15000, maximumAge: 0,
                }));
                const coordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                if (target === 'draft') Object.assign(this.draft, coordinates);
                else {
                    const address = this.addresses.find(a => a.id === addressId);
                    if (address) Object.assign(address, coordinates);
                    this.persist();
                }
            } catch (error) {
                this.locationError = error.code === 1
                    ? 'Allow location access in your browser to set the delivery pin.'
                    : 'Could not get the device location. Try again where the delivery address is.';
            } finally {
                this.locatingAddress = null;
            }
        },

        captureLocation(addressId) { return this.locate(addressId, addressId); },
        captureDraftLocation() { return this.locate('draft'); },

        save() {
            const d = this.draft;
            const line = d.line.replace(/\s*\n\s*/g, ', ').trim();
            const errors = {};
            if (!line) errors.line = 'Enter your house number, street, barangay and ZIP code.';
            if (!d.provinceCode) errors.province = 'Select a province.';
            if (!d.cityCode) errors.city = 'Select a city or municipality.';
            this.errors = errors;
            if (Object.keys(errors).length) return;

            const province = this.locations[d.provinceCode];
            const address = {
                id: 'custom-' + Date.now(),
                label: d.label.trim() || 'New address',
                registered: false, custom: true, usable: true,
                line, provinceCode: String(d.provinceCode), cityCode: String(d.cityCode),
                latitude: d.latitude, longitude: d.longitude,
                province: province.name, city: province.cities[d.cityCode],
            };
            this.addresses.push(address);
            this.selected = address.id;
            this.persist();
            this.closeModal();
        },

        remove(id) {
            this.addresses = this.addresses.filter(a => a.id !== id);
            if (this.selected === id) this.selected = (this.addresses.find(a => a.usable) || {}).id || null;
            this.persist();
        },

        onSubmit(event) {
            if (!this.current) { event.preventDefault(); this.openModal(); return; }
            if (!this.shippingQuoteReady || this.quotingShipping) {
                event.preventDefault();
                if (!this.shippingQuoteError) this.shippingQuoteError = 'Wait for a valid Logistics quote before placing the order.';
                return;
            }
            for (const [sellerId, code] of Object.entries(this.voucherCodes)) {
                if (code && code !== this.appliedVoucherCodes[sellerId]) {
                    event.preventDefault();
                    this.voucherErrors[sellerId] = 'Apply this voucher to check the total before placing the order.';
                    document.getElementById('voucher-' + sellerId)?.focus();
                    return;
                }
            }
            if (this.submitting) { event.preventDefault(); return; }
            this.submitting = true;
        },
    });
    </script>
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'cart'), 'mode' => 'notice'])
</x-buyer.layout>
