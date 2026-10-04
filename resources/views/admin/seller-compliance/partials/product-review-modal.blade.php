<div x-show="openProductId === {{ $product->id }}" x-cloak x-data="{ imgIndex: 0 }"
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 overflow-hidden"
    @click.self="openProductId = null">
    <div role="dialog" aria-modal="true" aria-labelledby="adminProductReviewTitle" tabindex="-1"
        class="bg-white rounded-xl p-5 sm:p-6 w-full max-w-3xl max-h-[calc(100dvh-2rem)] overflow-y-auto overscroll-contain"
        @click.stop>
        <button type="button" @click="openProductId = null"
            class="text-sm text-gray-500 hover:text-gray-700 mb-4">&lsaquo; Back</button>

        <h3 id="adminProductReviewTitle" class="font-bold text-lg text-gray-900">Product Details</h3>
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
                @if($product->compare_at_price)
                    <div><span class="text-gray-500 text-xs">Original price</span>
                        <p class="font-semibold">₱{{ number_format($product->compare_at_price, 2) }}</p>
                    </div>
                @endif
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
                @if(filled($product->seller->sellerDetail?->business_name))
                    <div><span class="text-gray-500 text-xs">Shop</span>
                        <p class="font-semibold">{{ $product->seller->sellerDetail->business_name }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            @if(filled($product->description))
                <div class="border border-gray-100 rounded-xl p-4">
                    <p class="font-bold text-sm text-gray-900 mb-2">Product Description</p>
                    <p class="text-sm text-gray-500">{{ $product->description }}</p>
                </div>
            @endif
            @php
                $packageSize = $product->package_length && $product->package_width && $product->package_height
                    ? $product->package_length.' × '.$product->package_width.' × '.$product->package_height.' cm'
                    : null;
                $productInfo = [
                    'Brand' => $product->brand,
                    'Material' => $product->material,
                    'Sizes' => $product->sizes,
                    'Colors' => $product->colors,
                    'Weight' => $product->weight,
                    'Country of Origin' => $product->country_of_origin,
                    'Condition' => $product->condition ? ucfirst($product->condition) : null,
                    'Package weight' => $product->weight_kg ? $product->weight_kg.' kg' : null,
                    'Package size' => $packageSize,
                    'Fragile' => $product->is_fragile ? 'Yes' : 'No',
                ];
                $productInfo = array_filter($productInfo, fn ($value) => filled($value));
            @endphp
            @if($productInfo)
                <div class="border border-gray-100 rounded-xl p-4 text-sm space-y-2">
                    <p class="font-bold text-sm text-gray-900 mb-2">Product Information</p>
                    @foreach($productInfo as $label => $value)
                        <div class="flex justify-between gap-4"><span class="text-gray-500">{{ $label }}</span><span class="text-right font-medium">{{ $value }}</span></div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($product->attributeValues->isNotEmpty() || $product->specifications->isNotEmpty() || $product->variants->isNotEmpty() || $product->video_path)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-sm">
                @if($product->attributeValues->isNotEmpty() || $product->specifications->isNotEmpty())
                    <div class="border border-gray-100 rounded-xl p-4 space-y-2">
                        <h4 class="font-bold text-gray-900">Category details &amp; specifications</h4>
                        @foreach($product->attributeValues as $item)
                            <p><span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $item->key)) }}:</span> {{ implode(', ', $item->value) }}</p>
                        @endforeach
                        @foreach($product->specifications as $spec)
                            <p><span class="text-gray-500">{{ $spec->name }}:</span> {{ $spec->value }}</p>
                        @endforeach
                    </div>
                @endif
                @if($product->variants->isNotEmpty())
                    <div class="border border-gray-100 rounded-xl p-4 space-y-2">
                        <h4 class="font-bold text-gray-900">Variations</h4>
                        @foreach($product->variants as $variant)
                            <p>{{ $variant->label }} · {{ $variant->sku }} · ₱{{ number_format($variant->price, 2) }} · {{ $variant->stock }} in stock</p>
                        @endforeach
                    </div>
                @endif
                @if($product->video_path)
                    <div class="border border-gray-100 rounded-xl p-4">
                        <h4 class="font-bold text-gray-900 mb-2">Product video</h4>
                        <video controls preload="metadata" class="w-full max-w-sm rounded-lg" src="{{ asset('storage/'.$product->video_path) }}">Video preview unavailable.</video>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <button type="button" @click="openProductId = null; rejectId = {{ $product->id }}"
                class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">Reject
                Product</button>
            <button type="button" @click="openProductId = null; warnId = {{ $product->id }}"
                class="px-4 py-2 rounded-lg border border-orange-300 text-orange-600 text-sm font-medium hover:bg-orange-50">Issue
                Warning</button>
            <form method="POST" action="{{ route('admin.seller-compliance.products.approve', $product) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Approve
                    Product</button>
            </form>
        </div>
    </div>
</div>
