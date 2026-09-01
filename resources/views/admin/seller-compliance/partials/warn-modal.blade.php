<div x-show="warnId === {{ $product->id }}" x-cloak x-data="{ reason: '', details: '' }"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="warnId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative" @click.stop>
        <button type="button" @click="warnId = null"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>

        <div class="text-center mb-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-orange-100 flex items-center justify-center mb-3">
                <span class="text-orange-600 text-2xl">!</span>
            </div>
            <h3 class="font-bold text-lg text-gray-900">Issue Warning to Seller</h3>
            <p class="text-sm text-gray-500">The seller will be notified about this warning</p>
        </div>

        <form method="POST" action="{{ route('seller-compliance.products.warn', $product) }}">
            @csrf
            <p class="text-sm font-medium text-gray-900 mb-2">Reason for Warning<span class="text-red-500">*</span></p>
            <div class="space-y-2 mb-4">
                @foreach (['Product does not match the registered category', 'Prohibited product', 'Inappropriate or misleading product content', 'Misleading product information', 'Unauthorized or restricted item', 'Other (please specify)'] as $option)
                    <label
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm cursor-pointer">
                        <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required>
                        {{ $option }}
                    </label>
                @endforeach
            </div>

            <label class="text-sm font-medium text-gray-900 mb-2 block">Details<span
                    class="text-red-500">*</span></label>
            <textarea name="details" x-model="details" maxlength="500" required rows="3"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"></textarea>
            <p class="text-xs text-gray-400 text-right mb-4" x-text="details.length + '/500'"></p>

            <div class="flex gap-3">
                <button type="button" @click="warnId = null"
                    class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Cancel</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700">Issue
                    Warning</button>
            </div>
        </form>
    </div>
</div>