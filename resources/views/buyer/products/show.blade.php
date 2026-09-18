<x-buyer-layout>
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->path) : asset('images/products/tote-bag.jpg') }}"
                    class="w-full h-80 object-cover rounded-lg">
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h2>
                <p class="text-xl font-bold text-[#3b1735] mt-2">₱{{ number_format($product->price, 2) }}</p>
                <p class="text-sm text-gray-500 mt-1">Stock: {{ $product->stock }}</p>

                @if ($product->colors)
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-1">Color</p>
                        <div class="flex gap-2 flex-wrap">
                            @foreach (explode(',', $product->colors) as $color)
                                <span class="px-3 py-1 border rounded-lg text-sm">{{ trim($color) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($product->sizes)
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-1">Size</p>
                        <div class="flex gap-2 flex-wrap">
                            @foreach (explode(',', $product->sizes) as $size)
                                <span class="px-3 py-1 border rounded-lg text-sm">{{ trim($size) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <p class="text-sm text-gray-600 mt-4">{{ $product->description }}</p>

                @error('quantity')
                    <p class="text-red-600 text-sm mt-4">{{ $message }}</p>
                @enderror

                @if ($product->stock <= 0)
                    <p class="mt-6 inline-block bg-gray-100 text-gray-500 px-6 py-3 rounded-lg font-medium">Out of Stock
                    </p>
                @else
                    <form action="{{ route('buyer.cart.store') }}" method="POST" class="mt-6 flex items-center gap-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}"
                            class="w-20 border rounded-lg text-sm px-2 py-2">
                        <button type="submit"
                            class="bg-[#3b1735] text-white px-6 py-3 rounded-lg font-medium hover:bg-[#4d1f45]">
                            Add to Cart
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>
</x-buyer-layout>
