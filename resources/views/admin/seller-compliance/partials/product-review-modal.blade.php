{{--
    Product review dialog. Needs $product and the scTable scope { openProductId, rejectId, warnId }.
    Reject / Issue warning swap to their own dialogs; Approve posts to the existing approve route.
--}}
@php
    $titleId = 'product-review-title-' . $product->id;
    $images = $product->images;
    $imageCount = $images->count();

    $packageSize = $product->package_length && $product->package_width && $product->package_height
        ? $product->package_length . ' × ' . $product->package_width . ' × ' . $product->package_height . ' cm'
        : null;
    $productInfo = array_filter([
        'Brand' => $product->brand,
        'Material' => $product->material,
        'Sizes' => $product->sizes,
        'Colors' => $product->colors,
        'Weight' => $product->weight,
        'Country of Origin' => $product->country_of_origin,
        'Condition' => $product->condition ? ucfirst($product->condition) : null,
        'Package weight' => $product->weight_kg ? $product->weight_kg . ' kg' : null,
        'Package size' => $packageSize,
        'Fragile' => $product->is_fragile ? 'Yes' : 'No',
    ], fn ($value) => filled($value));

    $hasExtras = $product->attributeValues->isNotEmpty() || $product->specifications->isNotEmpty()
        || $product->variants->isNotEmpty() || $product->video_path;

    $card = 'rounded-2xl border border-[#ece4ec] bg-white p-4 sm:p-5';
    $label = 'text-xs text-gray-500';
    $value = 'text-sm font-semibold text-[#2B1730]';
@endphp

<div x-show="openProductId === {{ $product->id }}" x-cloak x-data="{ img: 0, count: {{ $imageCount }} }"
    role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}" @click.self="openProductId = null"
    @keydown.arrow-left.window="if (openProductId === {{ $product->id }} && count > 1) img = (img - 1 + count) % count"
    @keydown.arrow-right.window="if (openProductId === {{ $product->id }} && count > 1) img = (img + 1) % count"
    x-effect="if (openProductId === {{ $product->id }}) { img = 0; $nextTick(() => $refs.close && $refs.close.focus()) }"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#2B1730]/50 p-3 backdrop-blur-[2px] sm:p-4">

    <div x-show="openProductId === {{ $product->id }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-[#FBF7F2] shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)] sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 border-b border-[#ece4ec] bg-white px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <h3 id="{{ $titleId }}" class="font-display text-xl font-semibold text-[#2B1730]">Product details</h3>
                <p class="mt-0.5 text-sm text-gray-500">Review the information, then approve, warn or reject.</p>
            </div>
            <button type="button" x-ref="close" @click="openProductId = null" aria-label="Close product details"
                class="-mr-1.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition duration-200 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="x" class="h-5 w-5" stroke="2.2" />
            </button>
        </div>

        {{-- Body --}}
        <div class="thin-scroll flex-1 space-y-4 overflow-y-auto overscroll-contain p-5 sm:p-6">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">

                {{-- Gallery --}}
                <div class="{{ $card }}">
                    <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-[#ece4ec] bg-[#FBF8FB]">
                        @forelse ($images as $i => $image)
                            <img x-show="img === {{ $i }}" src="{{ Storage::url($image->path) }}" alt="{{ $product->name }}, photo {{ $i + 1 }} of {{ $imageCount }}"
                                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-[1.02]" x-transition:enter-end="opacity-100 scale-100"
                                class="absolute inset-0 h-full w-full object-contain">
                        @empty
                            <span class="flex flex-col items-center gap-2 text-sm text-gray-400">
                                <x-admin.icon name="image" class="h-8 w-8" stroke="1.5" /> No photos uploaded
                            </span>
                        @endforelse

                        @if ($imageCount > 1)
                            <button type="button" @click="img = (img - 1 + count) % count" aria-label="Previous photo"
                                class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-[#2B1730] shadow-md transition duration-150 hover:scale-105 active:scale-95
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/50">
                                <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" />
                            </button>
                            <button type="button" @click="img = (img + 1) % count" aria-label="Next photo"
                                class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-[#2B1730] shadow-md transition duration-150 hover:scale-105 active:scale-95
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/50">
                                <x-admin.icon name="chevron-right" class="h-4 w-4" stroke="2.2" />
                            </button>
                            <span class="absolute bottom-2 right-2 rounded-full bg-[#2B1730]/80 px-2.5 py-0.5 text-[11px] font-medium tabular-nums text-white" aria-hidden="true"
                                x-text="(img + 1) + ' / ' + count"></span>
                        @endif
                    </div>

                    @if ($imageCount > 1)
                        <div class="thin-scroll mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $i => $image)
                                <button type="button" @click="img = {{ $i }}" aria-label="Show photo {{ $i + 1 }}" :aria-current="img === {{ $i }}"
                                    :class="img === {{ $i }} ? 'border-[#3b1735] ring-2 ring-[#3b1735]/20' : 'border-[#e2d6e5] opacity-80 hover:opacity-100'"
                                    class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg border-2 transition duration-150">
                                    <img src="{{ Storage::url($image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Summary --}}
                <div class="{{ $card }}">
                    <p class="text-lg font-semibold leading-snug text-[#2B1730]">{{ $product->name }}</p>
                    <p class="mt-0.5 text-[13px] text-gray-500">By {{ $product->seller->name }}</p>

                    <div class="mt-4 flex flex-wrap items-end gap-x-6 gap-y-2 border-b border-[#f3edf4] pb-4">
                        <div>
                            <p class="{{ $label }}">Price</p>
                            <p class="text-2xl font-semibold tracking-tight text-[#2B1730]">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                        @if ($product->compare_at_price)
                            <div>
                                <p class="{{ $label }}">Original price</p>
                                <p class="text-sm font-medium text-gray-500 line-through">₱{{ number_format($product->compare_at_price, 2) }}</p>
                            </div>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3.5">
                        <div><dt class="{{ $label }}">Stock</dt><dd class="{{ $value }}">{{ $product->stock }} pieces</dd></div>
                        <div><dt class="{{ $label }}">Product ID</dt><dd class="{{ $value }}">#{{ $product->product_code }}</dd></div>
                        <div class="col-span-2">
                            <dt class="{{ $label }}">Category</dt>
                            <dd class="mt-1">@include('admin.seller-compliance.partials.category-badge', ['category' => $product->category])</dd>
                        </div>
                        <div><dt class="{{ $label }}">Submitted</dt><dd class="{{ $value }}">{{ $product->created_at->format('M d, Y g:i A') }}</dd></div>
                        <div><dt class="{{ $label }}">Seller</dt><dd class="{{ $value }} break-words">{{ $product->seller->name }}</dd></div>
                        @if (filled($product->seller->sellerDetail?->business_name))
                            <div class="col-span-2"><dt class="{{ $label }}">Shop</dt><dd class="{{ $value }} break-words">{{ $product->seller->sellerDetail->business_name }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            @if (filled($product->description) || $productInfo)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @if (filled($product->description))
                        <section class="{{ $card }}">
                            <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold text-[#3b1735]"><x-admin.icon name="file-text" class="h-[18px] w-[18px]" /> Product description</h4>
                            <p class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-600">{{ $product->description }}</p>
                        </section>
                    @endif
                    @if ($productInfo)
                        <section class="{{ $card }}">
                            <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-[#3b1735]"><x-admin.icon name="package" class="h-[18px] w-[18px]" /> Product information</h4>
                            <dl class="space-y-2 text-sm">
                                @foreach ($productInfo as $infoLabel => $infoValue)
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ $infoLabel }}</dt><dd class="min-w-0 break-words text-right font-medium text-[#2B1730]">{{ $infoValue }}</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                </div>
            @endif

            @if ($hasExtras)
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    @if ($product->attributeValues->isNotEmpty() || $product->specifications->isNotEmpty())
                        <section class="{{ $card }}">
                            <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-[#3b1735]"><x-admin.icon name="sliders" class="h-[18px] w-[18px]" /> Category details &amp; specifications</h4>
                            <dl class="space-y-2">
                                @foreach ($product->attributeValues as $item)
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $item->key)) }}</dt><dd class="min-w-0 break-words text-right font-medium text-[#2B1730]">{{ implode(', ', $item->value) }}</dd></div>
                                @endforeach
                                @foreach ($product->specifications as $spec)
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ $spec->name }}</dt><dd class="min-w-0 break-words text-right font-medium text-[#2B1730]">{{ $spec->value }}</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                    @if ($product->variants->isNotEmpty())
                        <section class="{{ $card }}">
                            <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-[#3b1735]"><x-admin.icon name="tag" class="h-[18px] w-[18px]" /> Variations</h4>
                            <ul class="divide-y divide-[#f3edf4]">
                                @foreach ($product->variants as $variant)
                                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-0.5 py-2 first:pt-0 last:pb-0">
                                        <span class="min-w-0">
                                            <span class="block font-medium text-[#2B1730]">{{ $variant->label }}</span>
                                            <span class="block text-xs text-gray-500">{{ $variant->sku }}</span>
                                        </span>
                                        <span class="text-right text-[13px]">
                                            <span class="block font-semibold text-[#2B1730]">₱{{ number_format($variant->price, 2) }}</span>
                                            <span class="block text-xs text-gray-500">{{ $variant->stock }} in stock</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if ($product->video_path)
                        <section class="{{ $card }} md:col-span-2">
                            <h4 class="mb-3 text-sm font-semibold text-[#3b1735]">Product video</h4>
                            <video controls preload="metadata" class="w-full max-w-md rounded-xl bg-black" src="{{ asset('storage/' . $product->video_path) }}">Video preview unavailable.</video>
                        </section>
                    @endif
                </div>
            @endif
        </div>

        {{-- Actions stay visible while the details scroll --}}
        <div class="flex flex-col-reverse gap-3 border-t border-[#ece4ec] bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <button type="button" @click="openProductId = null; rejectId = {{ $product->id }}"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-red-300 px-5 text-sm font-semibold text-red-700 transition duration-200 hover:bg-red-50 active:scale-[0.98]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/30">
                <x-admin.icon name="shield-x" class="h-4 w-4" /> Reject product
            </button>
            <button type="button" @click="openProductId = null; warnId = {{ $product->id }}"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-orange-300 px-5 text-sm font-semibold text-orange-700 transition duration-200 hover:bg-orange-50 active:scale-[0.98]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/30">
                <x-admin.icon name="triangle-alert" class="h-4 w-4" /> Issue warning
            </button>
            <form method="POST" action="{{ route('admin.seller-compliance.products.approve', $product) }}" x-data="{ busy: false }" @submit="busy = true" class="sm:contents">
                @csrf
                <button type="submit" :disabled="busy"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#3b1735] px-6 text-sm font-semibold text-white transition duration-200 hover:bg-[#4d1f45] active:scale-[0.98]
                           disabled:cursor-wait disabled:opacity-70 sm:w-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    <x-admin.icon name="check" class="h-4 w-4" stroke="2.4" x-show="!busy" /> Approve product
                </button>
            </form>
        </div>
    </div>
</div>