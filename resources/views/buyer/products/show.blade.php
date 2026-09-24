<x-buyer.layout>
    @php
        $colors = array_values(array_filter(array_map('trim', explode(',', $product->colors ?? ''))));
        $sizes = array_values(array_filter(array_map('trim', explode(',', $product->sizes ?? ''))));
        $photos = $product->images;
    @endphp
    <div class="max-w-7xl mx-auto p-4 sm:p-5 lg:p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-6">
            <div x-data="{ photo: 0, touchX: null }" tabindex="0"
                @keydown.arrow-left.prevent="photo = (photo - 1 + {{ max(1, $photos->count()) }}) % {{ max(1, $photos->count()) }}"
                @keydown.arrow-right.prevent="photo = (photo + 1) % {{ max(1, $photos->count()) }}"
                @touchstart.passive="touchX = $event.changedTouches[0].screenX"
                @touchend.passive="if (touchX !== null && Math.abs($event.changedTouches[0].screenX - touchX) > 40) photo = ($event.changedTouches[0].screenX < touchX ? photo + 1 : photo - 1 + {{ max(1, $photos->count()) }}) % {{ max(1, $photos->count()) }}; touchX = null"
                aria-label="Product photo gallery. Swipe or use the arrow keys to browse photos.">
                @if($photos->isNotEmpty())
                    <div class="relative rounded-lg overflow-hidden bg-gray-100">
                        @foreach($photos as $image)
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $product->name }} photo {{ $loop->iteration }} of {{ $photos->count() }}"
                                class="w-full h-80 object-contain" x-show="photo === {{ $loop->index }}" @if(!$loop->first) x-cloak @endif>
                        @endforeach
                        @if($photos->count() > 1)
                            <button type="button" @click="photo = (photo - 1 + {{ $photos->count() }}) % {{ $photos->count() }}"
                                class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 rounded-full w-9 h-9 shadow text-xl" aria-label="Previous product photo">‹</button>
                            <button type="button" @click="photo = (photo + 1) % {{ $photos->count() }}"
                                class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 rounded-full w-9 h-9 shadow text-xl" aria-label="Next product photo">›</button>
                        @endif
                    </div>
                    @if($photos->count() > 1)
                        <div class="flex gap-2 mt-3 overflow-x-auto" aria-label="Product photos">
                            @foreach($photos as $image)
                                <button type="button" @click="photo = {{ $loop->index }}" :aria-pressed="photo === {{ $loop->index }}"
                                    :class="photo === {{ $loop->index }} ? 'border-[#3b1735]' : 'border-gray-200'"
                                    class="w-16 h-16 shrink-0 rounded-lg border-2 overflow-hidden" aria-label="Show photo {{ $loop->iteration }}">
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="w-full h-80 rounded-lg bg-gray-100 grid place-items-center text-gray-500">No product photo yet</div>
                @endif
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h2>
                <p class="text-xl font-bold text-[#3b1735] mt-2">₱{{ number_format($product->price, 2) }}</p>
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
                        @if($colors)
                            <label class="block text-sm font-medium text-gray-700">Color
                                <select name="color" required class="block w-full max-w-xs mt-1 border rounded-lg px-3 py-2 bg-white">
                                    <option value="">Choose a color</option>
                                    @foreach($colors as $color)<option value="{{ $color }}" @selected(old('color') === $color)>{{ $color }}</option>@endforeach
                                </select>
                            </label>
                        @endif
                        @if($sizes)
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
    </div>
</x-buyer.layout>
