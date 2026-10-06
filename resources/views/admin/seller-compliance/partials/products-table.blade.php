{{--
    Products for Review table + its dialogs. Rendered inside #sc-region (scTable scope);
    needs $products. Dialog state (openProductId, rejectId, warnId) comes from the scope.
--}}
<div class="sc-rows overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
    @if ($products->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'clipboard-check',
            'tone' => 'green',
            'title' => "You're all caught up",
            'text' => 'No products are waiting for review. New submissions show up here as soon as sellers send them.',
            'filteredTitle' => 'No products found',
            'filteredText' => 'No product waiting for review matches this search or category.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <caption class="sr-only">Products waiting for review. Select a row to open the product details.</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Product</th>
                        <th scope="col" class="px-4 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Category</th>
                        <th scope="col" class="px-4 py-3 font-medium">Submitted</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($products as $product)
                        @php $cover = $product->images->first(); @endphp
                        <tr style="--i: {{ $loop->index }}" @click="openProductId = {{ $product->id }}"
                            class="group cursor-pointer transition-colors duration-150 hover:bg-[#FBF8FB]">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#ece4ec] bg-[#FBF8FB] text-gray-300">
                                        @if ($cover)
                                            <img src="{{ Storage::url($cover->path) }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 ease-vendo group-hover:scale-105">
                                        @else
                                            <x-admin.icon name="image" class="h-5 w-5" />
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <button type="button" aria-haspopup="dialog" @click.stop="openProductId = {{ $product->id }}"
                                            class="block max-w-[260px] truncate rounded text-left font-medium text-[#2B1730] hover:text-[#3b1735] hover:underline
                                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                            {{ $product->name }}
                                        </button>
                                        <p class="text-xs text-gray-500">ID: {{ $product->product_code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="max-w-[200px] truncate text-gray-700">{{ $product->seller->name }}</p>
                                @if (filled($product->seller->sellerDetail->business_name ?? null))
                                    <p class="max-w-[200px] truncate text-xs text-gray-500">{{ $product->seller->sellerDetail->business_name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @include('admin.seller-compliance.partials.category-badge', ['category' => $product->category])
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <p class="text-gray-700">{{ $product->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $product->created_at->format('g:i A') }} · {{ $product->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-green-600/60 bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                    <x-admin.icon name="clock" class="h-3.5 w-3.5" /> For review
                                </span>
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