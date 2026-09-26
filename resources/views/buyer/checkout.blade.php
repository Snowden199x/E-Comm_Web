<x-buyer.layout>
    <div class="max-w-3xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h2>

        @error('checkout_revision')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>@enderror
        @error('items')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>@enderror

        @error('quantity')
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
                {{ $message }}
            </div>
        @enderror

        <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
            @foreach ($cartItems->groupBy(fn ($item) => $item->product->seller_id) as $sellerItems)
            <p class="border-b pt-2 text-sm font-semibold text-[#3b1735]">{{ $sellerItems->first()->product->seller?->sellerDetail?->business_name ?: ($sellerItems->first()->product->seller?->name ?? 'Store') }}</p>
            @foreach ($sellerItems as $item)
                <div class="flex justify-between text-sm py-2 border-b last:border-0">
                    <span>{{ $item->product->name }} x{{ $item->quantity }}</span>
                    <span>₱{{ number_format($item->quantity * $item->product->price, 2) }}</span>
                </div>
            @endforeach
            @endforeach
            <div class="flex justify-between font-bold mt-3">
                <span>Total</span>
                <span>₱{{ number_format($cartItems->sum(fn($i) => $i->quantity * $i->product->price), 2) }}</span>
            </div>
        </div>

        <form action="{{ route('buyer.checkout.store') }}" method="POST"
            class="bg-white rounded-2xl p-5 shadow-sm space-y-4">
            @csrf
            @foreach($cartItems as $item)<input type="hidden" name="items[]" value="{{ $item->id }}">@endforeach
            <input type="hidden" name="checkout_revision" value="{{ $checkoutRevision }}">
            <div>
                <label for="shipping-address" class="text-sm font-medium text-gray-700">House, street, barangay and ZIP code</label>
                <textarea id="shipping-address" name="shipping_address" rows="3" required class="w-full border rounded-lg mt-1 p-2 text-sm">{{ old('shipping_address', $defaultAddress) }}</textarea>
                @error('shipping_address')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="shipping-province" class="text-sm font-medium text-gray-700">Province / Metro Manila</label>
                    <select id="shipping-province" name="shipping_province_code" required class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm">
                        <option value="">Select province</option>
                        @foreach ($locationOptions as $code => $province)
                            <option value="{{ $code }}" @selected(old('shipping_province_code', $defaultProvinceCode) === $code)>{{ $province['name'] }}</option>
                        @endforeach
                    </select>
                    @error('shipping_province_code')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="shipping-city" class="text-sm font-medium text-gray-700">City / municipality</label>
                    <select id="shipping-city" name="shipping_city_code" required class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm">
                        <option value="">Select city</option>
                    </select>
                    @error('shipping_city_code')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Payment Method</label>
                <input type="hidden" name="payment_mode" value="cod">
                <p class="mt-1 text-sm text-gray-900 border rounded-lg p-2 bg-gray-50">Cash on Delivery (COD)</p>
            </div>

            <button type="submit"
                class="w-full bg-[#3b1735] text-white py-3 rounded-lg font-medium hover:bg-[#4d1f45]">
                Place Order
            </button>
        </form>
    </div>
<script>
(() => {
    const locations = @js($locationOptions);
    const province = document.getElementById('shipping-province');
    const city = document.getElementById('shipping-city');
    const selectedCity = @js(old('shipping_city_code', $defaultCityCode));
    function updateCities(preserveSelection = false) {
        const previous = preserveSelection ? selectedCity : '';
        city.replaceChildren(new Option('Select city', ''));
        Object.entries(locations[province.value]?.cities ?? {}).forEach(([code, name]) => {
            city.add(new Option(name, code, false, code === previous));
        });
    }
    province.addEventListener('change', () => updateCities());
    updateCities(true);
})();
</script>
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'cart'), 'mode' => 'notice'])
</x-buyer.layout>
