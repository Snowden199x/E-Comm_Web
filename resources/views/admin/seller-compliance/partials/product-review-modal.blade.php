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

    $card = 'rounded-2xl border border-vd-line bg-white p-4 sm:p-5';
    $label = 'text-xs text-vd-muted';
    $value = 'text-sm font-semibold text-vd-ink';
@endphp

<div x-show="openProductId === {{ $product->id }}" x-cloak x-data="{ img: 0, count: {{ $imageCount }} }"
    role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}" @click.self="openProductId = null"
    @keydown.arrow-left.window="if (openProductId === {{ $product->id }} && count > 1) img = (img - 1 + count) % count"
    @keydown.arrow-right.window="if (openProductId === {{ $product->id }} && count > 1) img = (img + 1) % count"
    x-effect="if (openProductId === {{ $product->id }}) { img = 0; $nextTick(() => $refs.close && $refs.close.focus()) }"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-3 backdrop-blur-[2px] sm:p-4">

    <div x-show="openProductId === {{ $product->id }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-vd-line-strong bg-white sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header --}}
        <div class="px-5 pb-1 pt-5 sm:px-8 sm:pt-6">
            <button type="button" x-ref="close" @click="openProductId = null"
                class="-ml-1 inline-flex h-8 items-center gap-1 rounded-lg px-2 text-[13px] font-medium text-vd-ink transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" /> Back
            </button>
            <h3 id="{{ $titleId }}" class="mt-3 font-display text-lg font-semibold text-vd-ink">Product Details</h3>
            <p class="mt-0.5 text-[13px] text-vd-muted">Review product information and decide the appropriate action.</p>
        </div>

        {{-- Body --}}
        <div class="thin-scroll flex-1 space-y-4 overflow-y-auto overscroll-contain px-5 py-4 sm:px-8">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">

                {{-- Gallery --}}
                <div class="rounded-2xl border border-vd-line bg-vd-tint p-4 sm:p-5">
                    <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-vd-line bg-white">
                        @forelse ($images as $i => $image)
                            <img x-show="img === {{ $i }}" src="{{ Storage::url($image->path) }}" alt="{{ $product->name }}, photo {{ $i + 1 }} of {{ $imageCount }}"
                                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-[1.02]" x-transition:enter-end="opacity-100 scale-100"
                                class="absolute inset-0 h-full w-full object-contain">
                        @empty
                            <span class="flex flex-col items-center gap-2 text-sm text-vd-muted">
                                <x-admin.icon name="image" class="h-8 w-8" stroke="1.5" /> No photos uploaded
                            </span>
                        @endforelse

                        @if ($imageCount > 1)
                            <button type="button" @click="img = (img - 1 + count) % count" aria-label="Previous photo"
                                class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-vd-ink border border-vd-line-strong transition duration-150 hover:scale-105 active:scale-95
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/50">
                                <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" />
                            </button>
                            <button type="button" @click="img = (img + 1) % count" aria-label="Next photo"
                                class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-vd-ink border border-vd-line-strong transition duration-150 hover:scale-105 active:scale-95
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/50">
                                <x-admin.icon name="chevron-right" class="h-4 w-4" stroke="2.2" />
                            </button>
                            <span class="absolute bottom-2 right-2 rounded-full bg-vd-ink/80 px-2.5 py-0.5 text-[11px] font-medium tabular-nums text-white" aria-hidden="true"
                                x-text="(img + 1) + ' / ' + count"></span>
                        @endif
                    </div>

                    @if ($imageCount > 1)
                        <div class="thin-scroll mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $i => $image)
                                <button type="button" @click="img = {{ $i }}" aria-label="Show photo {{ $i + 1 }}" :aria-current="img === {{ $i }}"
                                    :class="img === {{ $i }} ? 'border-vd-plum ring-2 ring-vd-plum/20' : 'border-vd-line-strong opacity-80 hover:opacity-100'"
                                    class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg border-2 transition duration-150">
                                    <img src="{{ Storage::url($image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Summary --}}
                <div class="{{ $card }}">
                    <p class="text-lg font-semibold leading-snug text-vd-ink">{{ $product->name }}</p>
                    <p class="mt-0.5 text-[13px] text-vd-muted">By {{ $product->seller->name }}</p>

                    <div class="mt-4 flex flex-wrap items-end gap-x-6 gap-y-2 border-b border-vd-line pb-4">
                        <div>
                            <p class="{{ $label }}">Price</p>
                            <p class="text-2xl font-semibold tracking-tight text-vd-ink">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                        @if ($product->compare_at_price)
                            <div>
                                <p class="{{ $label }}">Original price</p>
                                <p class="text-sm font-medium text-vd-muted line-through">₱{{ number_format($product->compare_at_price, 2) }}</p>
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
                            <h4 class="mb-2 text-sm font-semibold text-vd-ink">Product Description</h4>
                            <p class="whitespace-pre-line break-words text-sm leading-relaxed text-vd-muted">{{ $product->description }}</p>
                        </section>
                    @endif
                    @if ($productInfo)
                        <section class="{{ $card }}">
                            <h4 class="mb-3 text-sm font-semibold text-vd-ink">Product Information</h4>
                            <dl class="space-y-2 text-sm">
                                @foreach ($productInfo as $infoLabel => $infoValue)
                                    <div class="flex justify-between gap-4"><dt class="text-vd-muted">{{ $infoLabel }}</dt><dd class="min-w-0 break-words text-right font-medium text-vd-ink">{{ $infoValue }}</dd></div>
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
                            <h4 class="mb-3 text-sm font-semibold text-vd-ink">Category Details &amp; Specifications</h4>
                            <dl class="space-y-2">
                                @foreach ($product->attributeValues as $item)
                                    <div class="flex justify-between gap-4"><dt class="text-vd-muted">{{ ucfirst(str_replace('_', ' ', $item->key)) }}</dt><dd class="min-w-0 break-words text-right font-medium text-vd-ink">{{ implode(', ', $item->value) }}</dd></div>
                                @endforeach
                                @foreach ($product->specifications as $spec)
                                    <div class="flex justify-between gap-4"><dt class="text-vd-muted">{{ $spec->name }}</dt><dd class="min-w-0 break-words text-right font-medium text-vd-ink">{{ $spec->value }}</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                    @if ($product->variants->isNotEmpty())
                        <section class="{{ $card }}">
                            <h4 class="mb-3 text-sm font-semibold text-vd-ink">Variations</h4>
                            <ul class="divide-y divide-vd-line">
                                @foreach ($product->variants as $variant)
                                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-0.5 py-2 first:pt-0 last:pb-0">
                                        <span class="min-w-0">
                                            <span class="block font-medium text-vd-ink">{{ $variant->label }}</span>
                                            <span class="block text-xs text-vd-muted">{{ $variant->sku }}</span>
                                        </span>
                                        <span class="text-right text-[13px]">
                                            <span class="block font-semibold text-vd-ink">₱{{ number_format($variant->price, 2) }}</span>
                                            <span class="block text-xs text-vd-muted">{{ $variant->stock }} in stock</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if ($product->video_path)
                        <section class="{{ $card }} md:col-span-2">
                            <h4 class="mb-3 text-sm font-semibold text-vd-ink">Product Video</h4>
                            <video controls preload="metadata" class="w-full max-w-md rounded-xl bg-black" src="{{ asset('storage/' . $product->video_path) }}">Video preview unavailable.</video>
                        </section>
                    @endif
                </div>
            @endif
        </div>

        {{-- Actions stay visible while the details scroll --}}
        <div class="flex flex-col-reverse gap-3 px-5 pb-5 pt-3 sm:flex-row sm:items-center sm:justify-end sm:px-8 sm:pb-6">
            <button type="button" @click="openProductId = null; rejectId = {{ $product->id }}"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-[#9B1C2C] bg-white px-5 text-[13px] font-medium text-[#9B1C2C] transition-all duration-200
                       hover:bg-[#F9D6DA] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#9B1C2C]/30">
                Reject Product
            </button>
            <button type="button" @click="openProductId = null; warnId = {{ $product->id }}"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-[#E58A76] bg-white px-5 text-[13px] font-medium text-[#C2452D] transition-all duration-200
                       hover:bg-[#FDE6DF] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C2452D]/30">
                Issue Warning
            </button>
            <form method="POST" action="{{ route('admin.seller-compliance.products.approve', $product) }}" x-data="{ busy: false }" @submit="busy = true" class="sm:contents">
                @csrf
                <button type="submit" :disabled="busy"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-vd-plum bg-vd-plum px-5 text-[13px] font-medium text-white transition-all duration-200
                           hover:bg-vd-plum-hover active:scale-[0.98] disabled:cursor-wait disabled:opacity-70 sm:w-auto
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                    <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    Approve Product
                </button>
            </form>
        </div>
    </div>
</div>
