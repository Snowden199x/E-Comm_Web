{{--
    Products for Review table + its dialogs. Rendered inside #sc-region (scTable scope);
    needs $products. Dialog state (openProductId, rejectId, warnId) comes from the scope.
--}}
<div class="sc-rows overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($products->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'clipboard-check',
            'tone' => 'green',
            'title' => 'No products to review',
            'text' => 'New seller submissions appear here.',
            'filteredTitle' => 'No products found',
            'filteredText' => 'Try a different search or category.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[820px] text-center text-sm">
                <caption class="sr-only">Products waiting for review. Select a row to open the product details.</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Product</th>
                        <th scope="col" class="px-4 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Category</th>
                        <th scope="col" class="px-4 py-3 font-medium">Submitted</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 text-center font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($products as $product)
                        @php $cover = $product->images->first(); @endphp
                        <tr style="--i: {{ $loop->index }}" @click="openProductId = {{ $product->id }}"
                            class="group cursor-pointer transition-colors duration-150 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-vd-line bg-vd-tint text-vd-plum-soft">
                                        @if ($cover)
                                            <img src="{{ Storage::url($cover->path) }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 ease-vendo group-hover:scale-105">
                                        @else
                                            <x-admin.icon name="image" class="h-5 w-5" />
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <button type="button" aria-haspopup="dialog" @click.stop="openProductId = {{ $product->id }}"
                                            class="block max-w-[260px] truncate rounded text-left font-medium text-vd-ink hover:text-vd-plum hover:underline
                                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                            {{ $product->name }}
                                        </button>
                                        <p class="text-xs text-vd-muted">ID: {{ $product->product_code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="mx-auto max-w-[200px] truncate text-vd-ink">{{ $product->seller->name }}</p>
                                @if (filled($product->seller->sellerDetail->business_name ?? null))
                                    <p class="mx-auto max-w-[200px] truncate text-xs text-vd-muted">{{ $product->seller->sellerDetail->business_name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @include('admin.seller-compliance.partials.category-badge', ['category' => $product->category])
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <p class="text-vd-ink">{{ $product->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-vd-muted">{{ $product->created_at->format('g:i A') }} · {{ $product->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <x-admin.status-pill tone="pending" icon="clock">For review</x-admin.status-pill>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" aria-haspopup="dialog" @click.stop="openProductId = {{ $product->id }}" aria-label="Review {{ $product->name }}"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">Review</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $products])
    @endif
</div>

@foreach ($products as $product)
    @include('admin.seller-compliance.partials.product-review-modal', ['product' => $product])
    @include('admin.seller-compliance.partials.reject-modal', ['product' => $product])
    @include('admin.seller-compliance.partials.warn-modal', ['product' => $product])
@endforeach