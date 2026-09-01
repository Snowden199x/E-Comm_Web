<div x-show="openProductId === {{ $product->id }}" x-cloak x-data="{ imgIndex: 0 }"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    @click.self="openProductId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-3xl my-8" @click.stop>
        <button type="button" @click="openProductId = null"
            class="text-sm text-gray-500 hover:text-gray-700 mb-4">&lsaquo; Back</button>

        <h3 class="font-bold text-lg text-gray-900">Product Details</h3>
        <p class="text-sm text-gray-500 mb-4">Review product information and decide the appropriate action</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                @php $images = $product->images; @endphp
                <div
                    class="relative bg-gray-100 rounded-xl aspect-square flex items-center justify-center overflow-hidden">
                    @forelse ($images as $i => $img)
                        <img x-show="imgIndex === {{ $i }}" src="{{ Storage::url($img->path) }}"
                            class="w-full h-full object-cover">
                    @empty
                        <span class="text-gray-400 text-sm">No image</span>
                    @endforelse
                    @if ($images->count() > 1)
                        <button type="button"
                            @click="imgIndex = (imgIndex - 1 + {{ $images->count() }}) % {{ $images->count() }}"
                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-white rounded-full w-8 h-8 shadow">&lsaquo;</button>
                        <button type="button" @click="imgIndex = (imgIndex + 1) % {{ $images->count() }}"
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-white rounded-full w-8 h-8 shadow">&rsaquo;</button>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="flex gap-2 mt-3">
                        @foreach ($images as $i => $img)
                            <button type="button" @click="imgIndex = {{ $i }}"
                                :class="imgIndex === {{ $i }} ? 'border-[#3b1735]' : 'border-gray-200'"
                                class="w-14 h-14 rounded-lg border-2 overflow-hidden">
                                <img src="{{ Storage::url($img->path) }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="border border-gray-100 rounded-xl p-4 text-sm space-y-2">
                <p class="font-bold text-gray-900">{{ $product->name }}</p>
                <p class="text-gray-500 text-xs">By {{ $product->seller->name }}</p>
                <div><span class="text-gray-500 text-xs">Price</span>
                    <p class="font-semibold">₱{{ number_format($product->price, 2) }}</p>
                </div>
                <div><span class="text-gray-500 text-xs">Stock</span>
                    <p class="font-semibold">{{ $product->stock }} pieces</p>
                </div>
                <div><span class="text-gray-500 text-xs">Category</span><br>
                    @include('admin.seller-compliance.partials.category-badge', [
                        'category' => $product->category,
                    ])
                </div>
                <div><span class="text-gray-500 text-xs">Submitted</span>
                    <p class="font-semibold">{{ $product->created_at->format('M d, Y g:i A') }}</p>
                </div>
                <div><span class="text-gray-500 text-xs">Product ID</span>
                    <p class="font-semibold">#{{ $product->product_code }}</p>
                </div>
                <div><span class="text-gray-500 text-xs">Seller</span>
                    <p class="font-semibold">{{ $product->seller->name }}</p>
                </div>
                <div><span class="text-gray-500 text-xs">Shop</span>
                    <p class="font-semibold">{{ $product->seller->sellerDetail->business_name ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="font-bold text-sm text-gray-900 mb-2">Product Description</p>
                <p class="text-sm text-gray-500">{{ $product->description }}</p>
            </div>
            <div class="border border-gray-100 rounded-xl p-4 text-sm space-y-2">
                <p class="font-bold text-sm text-gray-900 mb-2">Product Information</p>
                <div class="flex justify-between"><span class="text-gray-500">Brand</span><span
                        class="font-medium">{{ $product->brand ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Material</span><span
                        class="font-medium">{{ $product->material ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Sizes</span><span
                        class="font-medium">{{ $product->sizes ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Colors</span><span
                        class="font-medium">{{ $product->colors ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Weight</span><span
                        class="font-medium">{{ $product->weight ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Country of Origin</span><span
                        class="font-medium">{{ $product->country_of_origin ?? '—' }}</span></div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="button" @click="openProductId = null; rejectId = {{ $product->id }}"
                class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">Reject
                Product</button>
            <button type="button" @click="openProductId = null; warnId = {{ $product->id }}"
                class="px-4 py-2 rounded-lg border border-orange-300 text-orange-600 text-sm font-medium hover:bg-orange-50">Issue
                Warning</button>
            <form method="POST" action="{{ route('seller-compliance.products.approve', $product) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Approve
                    Product</button>
            </form>
        </div>
    </div>
</div>
