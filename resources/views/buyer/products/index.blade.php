<x-buyer.layout>
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Products</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse ($products as $product)
                <a href="{{ route('buyer.products.show', $product) }}"
                    class="bg-white rounded-2xl p-3 shadow-sm hover:shadow-md transition">
                    <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->path) : asset('images/products/tote-bag.jpg') }}"
                        class="w-full h-32 object-cover rounded-lg mb-2">
                    <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->name }}</p>
                    <p class="text-sm font-bold text-[#3b1735] mt-1">₱{{ number_format($product->price, 2) }}</p>
                    @if ($product->stock <= 0)
                        <p class="text-xs text-red-500 mt-1">Out of Stock</p>
                    @endif
                </a>
            @empty
                <p class="col-span-full text-center text-gray-400">No products yet.</p>
            @endforelse
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    </div>
</x-buyer.layout>
