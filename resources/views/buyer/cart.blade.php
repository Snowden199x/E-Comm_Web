<x-buyer.layout>
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">My Cart</h2>

        <div class="bg-white rounded-2xl shadow-sm divide-y">
            @forelse ($cartItems as $item)
                <div class="flex items-center gap-4 p-4">
                    <img src="{{ $item->product->images->first() ? asset('storage/' . $item->product->images->first()->path) : asset('images/products/tote-bag.jpg') }}"
                        class="w-16 h-16 object-cover rounded-lg">

                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $item->product->name }}</p>
                        @if ($item->color || $item->size)
                            <p class="text-xs text-gray-500">
                                {{ $item->color }} {{ $item->size ? '/ ' . $item->size : '' }}
                            </p>
                        @endif
                        <p class="text-sm font-bold text-[#3b1735]">₱{{ number_format($item->product->price, 2) }}</p>
                        @if ($totalsByProduct[$item->product_id] > $item->product->stock)
                            <p class="text-xs text-red-500 mt-1">
                                Only {{ $item->product->stock }} left in stock — you have
                                {{ $totalsByProduct[$item->product_id] }} total in cart.
                            </p>
                        @endif
                        <p class="text-sm font-bold text-[#3b1735]">₱{{ number_format($item->product->price, 2) }}</p>
                    </div>

                    <form action="{{ route('buyer.cart.update', $item) }}" method="POST"
                        class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                            class="w-16 border rounded-lg text-sm px-2 py-1" onchange="this.form.submit()">
                    </form>

                    <form action="{{ route('buyer.cart.destroy', $item) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="text-red-600 text-sm">Remove</button>
                    </form>
                </div>
            @empty
                <p class="p-6 text-center text-gray-400">Your cart is empty.</p>
            @endforelse
        </div>

        @if ($cartItems->isNotEmpty())
            <a href="{{ route('buyer.checkout.index') }}"
                class="block mt-4 text-center bg-[#3b1735] text-white py-3 rounded-lg font-medium hover:bg-[#4d1f45]">
                Proceed to Checkout
            </a>
        @endif
    </div>
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'cart'), 'mode' => 'notice'])
</x-buyer.layout>
