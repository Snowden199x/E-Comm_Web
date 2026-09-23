<x-buyer.layout>
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">

        <!-- Category strip -->
        <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
            <div class="flex gap-6 overflow-x-auto">
                @forelse ($categories as $category)
                    <a href="{{ route('buyer.categories') }}" class="flex flex-col items-center gap-2 shrink-0 w-20">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <img src="{{ asset('assets/icons/dashboard/dashboard-menu.svg') }}" class="w-6 h-6">
                        </div>
                        <span class="text-xs text-gray-700 text-center line-clamp-2">{{ $category->name }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No categories yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Featured products -->
        <h2 class="text-lg font-bold text-gray-900 mb-4">Just For You</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @forelse ($products as $product)
                <a href="{{ route('buyer.products.show', $product) }}"
                    class="bg-white rounded-2xl p-3 shadow-sm hover:shadow-md transition">
                    <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->path) : asset('images/products/tote-bag.jpg') }}"
                        class="w-full h-28 object-cover rounded-lg mb-2">
                    <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->name }}</p>
                    <p class="text-sm font-bold text-[#3b1735] mt-1">₱{{ number_format($product->price, 2) }}</p>
                </a>
            @empty
                <p class="col-span-full text-center text-gray-400">No products yet.</p>
            @endforelse
        </div>

    </div>
</x-buyer.layout>
