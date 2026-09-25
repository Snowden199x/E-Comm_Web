<x-buyer.layout>
    <div class="max-w-3xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h2>

        @error('checkout_revision')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>@enderror

        @error('quantity')
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
                {{ $message }}
            </div>
        @enderror

        <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
            @foreach ($cartItems as $item)
                <div class="flex justify-between text-sm py-2 border-b last:border-0">
                    <span>{{ $item->product->name }} x{{ $item->quantity }}</span>
                    <span>₱{{ number_format($item->quantity * $item->product->price, 2) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between font-bold mt-3">
                <span>Total</span>
                <span>₱{{ number_format($cartItems->sum(fn($i) => $i->quantity * $i->product->price), 2) }}</span>
            </div>
        </div>

        <form action="{{ route('buyer.checkout.store') }}" method="POST"
            class="bg-white rounded-2xl p-5 shadow-sm space-y-4">
            @csrf
            <input type="hidden" name="checkout_revision" value="{{ $checkoutRevision }}">
            <div>
                <label class="text-sm font-medium text-gray-700">Delivery Address</label>
                <textarea name="shipping_address" rows="3" required class="w-full border rounded-lg mt-1 p-2 text-sm">{{ old('shipping_address', $defaultAddress) }}</textarea>
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
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'cart'), 'mode' => 'notice'])
</x-buyer.layout>
