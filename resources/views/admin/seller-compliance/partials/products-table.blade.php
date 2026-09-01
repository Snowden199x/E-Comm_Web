<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Product</th>
                <th class="pb-3 font-medium">Seller</th>
                <th class="pb-3 font-medium">Categories</th>
                <th class="pb-3 font-medium">Date Submitted</th>
                <th class="pb-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr class="border-b last:border-0 cursor-pointer hover:bg-gray-50"
                    @click="openProductId = {{ $product->id }}">
                    <td class="py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                                @if ($product->images->first())
                                    <img src="{{ Storage::url($product->images->first()->path) }}"
                                        class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div>
                                <p class="text-gray-900">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400">ID: {{ $product->product_code }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 text-gray-600">
                        {{ $product->seller->name }}<br>
                        <span
                            class="text-xs text-gray-400">{{ $product->seller->sellerDetail->business_name ?? '' }}</span>
                    </td>
                    <td class="py-3">
                        @include('admin.seller-compliance.partials.category-badge', [
                            'category' => $product->category,
                        ])
                    </td>
                    <td class="py-3 text-gray-600">{{ $product->created_at->format('M d, Y g:i A') }}</td>
                    <td class="py-3">
                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">For
                            Review</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-400">No products for review.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($products->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $products->count() }} out of {{ $products->total() }} entries
            </p>
            <div>{{ $products->links() }}</div>
        </div>
    @endif
</div>

@foreach ($products as $product)
    @include('admin.seller-compliance.partials.product-review-modal', ['product' => $product])
    @include('admin.seller-compliance.partials.reject-modal', ['product' => $product])
    @include('admin.seller-compliance.partials.warn-modal', ['product' => $product])
@endforeach
