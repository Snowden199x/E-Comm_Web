<x-buyer.layout>
    @php
        $colors = array_values(array_filter(array_map('trim', explode(',', $product->colors ?? ''))));
        $sizes = array_values(array_filter(array_map('trim', explode(',', $product->sizes ?? ''))));
        $photos = $product->images;
        $photoCount = $photos->count();
        $mediaCount = $photoCount + ($product->video_path ? 1 : 0);
        $videoUrl = $product->video_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->video_path) : null;
        $visibleAttributes = $product->attributeValues->filter(fn ($item) => collect($item->value ?? [])->filter(fn ($value) => filled($value))->isNotEmpty());
        $visibleSpecifications = $product->specifications->filter(fn ($item) => filled($item->name) && filled($item->value));
    @endphp
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-6">
            <div x-data="{ media: 0, touchX: null }" tabindex="0"
                @keydown.arrow-left.prevent="media = (media - 1 + {{ max(1, $mediaCount) }}) % {{ max(1, $mediaCount) }}"
                @keydown.arrow-right.prevent="media = (media + 1) % {{ max(1, $mediaCount) }}"
                @touchstart.passive="touchX = $event.changedTouches[0].screenX"
                @touchend.passive="if (touchX !== null && Math.abs($event.changedTouches[0].screenX - touchX) > 40) media = ($event.changedTouches[0].screenX < touchX ? media + 1 : media - 1 + {{ max(1, $mediaCount) }}) % {{ max(1, $mediaCount) }}; touchX = null"
                aria-label="Product gallery. Swipe or use the arrow keys to browse photos and video.">
                @if($mediaCount > 0)
                    <div class="relative rounded-lg overflow-hidden bg-gray-100">
                        @foreach($photos as $image)
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $product->name }} photo {{ $loop->iteration }} of {{ $photos->count() }}"
                                class="w-full h-80 object-contain" x-show="media === {{ $loop->index }}" @if(!$loop->first) x-cloak @endif>
                        @endforeach
                        @if($product->video_path)
                            <video class="w-full h-80 object-contain bg-black" controls playsinline preload="metadata"
                                x-show="media === {{ $photoCount }}" @if($photoCount > 0) x-cloak @endif
                                src="{{ $videoUrl }}" aria-label="{{ $product->name }} product video">
                                Your browser does not support product video playback.
                            </video>
                        @endif
                        @if($mediaCount > 1)
                            <button type="button" @click="media = (media - 1 + {{ $mediaCount }}) % {{ $mediaCount }}"
                                class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 rounded-full w-9 h-9 shadow text-xl" aria-label="Previous gallery item">‹</button>
                            <button type="button" @click="media = (media + 1) % {{ $mediaCount }}"
                                class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 rounded-full w-9 h-9 shadow text-xl" aria-label="Next gallery item">›</button>
                        @endif
                    </div>
                    @if($mediaCount > 1)
                        <div class="flex gap-2 mt-3 overflow-x-auto" aria-label="Product photos and video">
                            @foreach($photos as $image)
                                <button type="button" @click="media = {{ $loop->index }}" :aria-pressed="media === {{ $loop->index }}"
                                    :class="media === {{ $loop->index }} ? 'border-[#3b1735]' : 'border-gray-200'"
                                    class="w-16 h-16 shrink-0 rounded-lg border-2 overflow-hidden" aria-label="Show photo {{ $loop->iteration }}">
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                            @if($product->video_path)
                                <button type="button" @click="media = {{ $photoCount }}" :aria-pressed="media === {{ $photoCount }}"
                                    :class="media === {{ $photoCount }} ? 'border-[#3b1735]' : 'border-gray-200'"
                                    class="w-16 h-16 shrink-0 rounded-lg border-2 overflow-hidden bg-gray-900 text-white grid place-items-center"
                                    aria-label="Show product video">
                                    <span class="flex flex-col items-center text-[10px] font-medium" aria-hidden="true"><span class="text-lg leading-none">▶</span>Video</span>
                                </button>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="w-full h-80 rounded-lg bg-gray-100 grid place-items-center text-gray-500">No product photo yet</div>
                @endif
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h2>
                <p class="mt-2 text-sm text-gray-600">
                    @if ($ratingSummary?->total_reviews)
                        <span class="text-amber-500" aria-label="{{ number_format($ratingSummary->average_rating, 1) }} out of 5 stars">{{ str_repeat('★', (int) round($ratingSummary->average_rating)) }}{{ str_repeat('☆', 5 - (int) round($ratingSummary->average_rating)) }}</span>
                        {{ number_format($ratingSummary->average_rating, 1) }} · {{ number_format($ratingSummary->total_reviews) }} reviews
                    @else
                        No reviews yet
                    @endif
                </p>
                <p class="text-xl font-bold text-[#3b1735] mt-2">₱{{ number_format($product->price, 2) }}</p>
                @if($product->seller)
                    <div class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                        <div class="min-w-0 flex-1"><span class="block text-xs text-gray-500">Sold by</span><a href="{{ route('buyer.sellers.show', $product->seller) }}" class="font-semibold text-[#5b2963] hover:underline">{{ $product->seller->sellerDetail?->business_name ?: $product->seller->name }}</a></div>
                        <a href="{{ route('buyer.sellers.show', $product->seller) }}" class="rounded-lg border border-[#5b2963] px-3 py-2 text-xs font-semibold text-[#5b2963]">View shop</a>
                        <a href="{{ route('buyer.marketplace-messages.seller.show', $product->seller) }}" class="rounded-lg bg-[#5b2963] px-3 py-2 text-xs font-semibold text-white">Message</a>
                    </div>
                @endif
                <p class="text-sm text-gray-500 mt-1">Stock: {{ $product->stock }}</p>
                @if($product->description)<p class="text-sm text-gray-600 mt-4 whitespace-pre-line">{{ $product->description }}</p>@endif

                <dl class="grid grid-cols-2 gap-3 mt-5 text-sm">
                    @if($product->brand)<div><dt class="text-gray-500">Brand</dt><dd>{{ $product->brand }}</dd></div>@endif
                    @if($product->material)<div><dt class="text-gray-500">Material</dt><dd>{{ $product->material }}</dd></div>@endif
                    @if($product->weight)<div><dt class="text-gray-500">Weight</dt><dd>{{ $product->weight }}</dd></div>@endif
                    @if($product->country_of_origin)<div><dt class="text-gray-500">Country of origin</dt><dd>{{ $product->country_of_origin }}</dd></div>@endif
                </dl>

                @if($errors->any())<p class="text-red-600 text-sm mt-4" role="alert">{{ $errors->first() }}</p>@endif
                @if($product->stock <= 0)
                    <p class="mt-6 inline-block bg-gray-100 text-gray-500 px-6 py-3 rounded-lg font-medium">Out of Stock</p>
                @else
                    <form action="{{ route('buyer.cart.store') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @if($product->has_variations)
                            <label class="block text-sm font-medium text-gray-700">Variation
                                <select name="variant_id" required class="block w-full max-w-sm mt-1 border rounded-lg px-3 py-2 bg-white">
                                    <option value="">Choose a variation</option>
                                    @foreach($product->variants as $variant)
                                        <option value="{{ $variant->id }}" @disabled($variant->stock < 1) @selected((int) old('variant_id') === $variant->id)>
                                            {{ $variant->label }} · ₱{{ number_format($variant->price, 2) }} · {{ $variant->stock }} available
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        @if($colors && ! $product->has_variations)
                            <label class="block text-sm font-medium text-gray-700">Color
                                <select name="color" required class="block w-full max-w-xs mt-1 border rounded-lg px-3 py-2 bg-white">
                                    <option value="">Choose a color</option>
                                    @foreach($colors as $color)<option value="{{ $color }}" @selected(old('color') === $color)>{{ $color }}</option>@endforeach
                                </select>
                            </label>
                        @endif
                        @if($sizes && ! $product->has_variations)
                            <label class="block text-sm font-medium text-gray-700">Size
                                <select name="size" required class="block w-full max-w-xs mt-1 border rounded-lg px-3 py-2 bg-white">
                                    <option value="">Choose a size</option>
                                    @foreach($sizes as $size)<option value="{{ $size }}" @selected(old('size') === $size)>{{ $size }}</option>@endforeach
                                </select>
                            </label>
                        @endif
                        <div class="flex items-center gap-4">
                            <label class="text-sm">Quantity
                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" required
                                    class="block w-20 border rounded-lg text-sm px-2 py-2 mt-1">
                            </label>
                            <button type="submit" class="self-end bg-[#3b1735] text-white px-6 py-3 rounded-lg font-medium hover:bg-[#4d1f45]">Add to Cart</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-[#eee6ef] bg-white p-5 sm:p-6" aria-labelledby="product-details-heading">
                <h3 id="product-details-heading" class="text-lg font-semibold text-gray-900">Category Details</h3>
                @if($visibleAttributes->isNotEmpty())
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        @foreach($visibleAttributes as $item)
                            @php
                                $attributeValue = collect($item->value ?? [])->filter(fn ($value) => filled($value))->implode(', ');
                            @endphp
                            @if($attributeValue !== '')
                                <div>
                                    <dt class="text-gray-500">{{ $attributeLabels[$item->key] ?? \Illuminate\Support\Str::headline($item->key) }}</dt>
                                    <dd class="mt-0.5 text-gray-900">{{ $attributeValue }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @else
                    <p class="mt-3 text-sm text-gray-500">No category details provided for this product.</p>
                @endif

                @if($visibleSpecifications->isNotEmpty())
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <h4 class="text-sm font-semibold text-gray-800">Additional Specifications</h4>
                        <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                            @foreach($visibleSpecifications as $specification)
                                <div>
                                    <dt class="text-gray-500">{{ $specification->name }}</dt>
                                    <dd class="mt-0.5 whitespace-pre-line text-gray-900">{{ $specification->value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-[#eee6ef] bg-white p-5 sm:p-6" aria-labelledby="related-products-heading">
                <h3 id="related-products-heading" class="text-lg font-semibold text-gray-900">You May Also Like</h3>
                @if($recommendedProducts->isNotEmpty())
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                        @foreach($recommendedProducts as $recommendedProduct)
                            @include('buyer.partials.product-card', ['product' => $recommendedProduct, 'compact' => true])
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-500">No other products available right now.</p>
                @endif
            </section>
        </div>

        <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm" aria-labelledby="product-reviews-heading">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 id="product-reviews-heading" class="text-xl font-bold text-gray-900">Customer reviews</h3>
                    <p class="mt-1 text-sm text-gray-500">Reviews from buyers who confirmed receipt of this product.</p>
                </div>
                @if ($ratingSummary?->total_reviews)
                    <p class="text-right text-sm"><strong class="block text-xl">{{ number_format($ratingSummary->average_rating, 1) }}/5</strong>{{ number_format($ratingSummary->total_reviews) }} reviews</p>
                @endif
            </div>
            <div class="mt-5 divide-y">
                @forelse ($reviews as $review)
                    <article class="py-4 first:pt-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="font-medium text-gray-900">{{ $review->buyer?->name ?? 'Buyer' }}</span>
                            <span class="text-amber-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Verified purchase</span>
                            <time class="text-xs text-gray-500" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('M j, Y') }}</time>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $review->comment }}</p>
                        @if ($review->reply)
                            <div class="mt-3 border-l-2 border-[#3b1735] pl-3 text-sm">
                                <p class="font-medium text-gray-800">Seller response</p>
                                <p class="mt-1 whitespace-pre-line text-gray-600">{{ $review->reply->body }}</p>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="py-6 text-sm text-gray-500">No published reviews yet.</p>
                @endforelse
            </div>
            @if ($reviews->hasPages())<div class="mt-4">{{ $reviews->links() }}</div>@endif
        </section>
    </div>
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'notice'])
</x-buyer.layout>
