<x-buyer.layout>
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Categories</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($categories as $category)
                <div class="bg-white rounded-2xl p-4 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-2">{{ $category->name }}</h3>
                    <ul class="space-y-1">
                        @foreach ($category->children as $sub)
                            <li>
                                <a href="{{ route('buyer.products.index', ['category_id' => $sub->id]) }}"
                                    class="text-sm text-gray-600 hover:text-[#3b1735]">{{ $sub->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="col-span-full text-center text-gray-400">No categories yet.</p>
            @endforelse
        </div>
    </div>
</x-buyer.layout>
