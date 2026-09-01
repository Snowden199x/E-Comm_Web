<div x-show="confirmation" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="confirmation = null">
    <div class="bg-white rounded-2xl p-8 w-full max-w-sm text-center" @click.stop>
        <template x-if="confirmation === 'product_rejected'">
            <div>
                <div class="w-16 h-16 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
                    <span class="text-red-600 text-3xl">&times;</span>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-1">Product Rejected</h3>
                <p class="text-sm text-gray-500">The product has been rejected and a violation has been recorded.</p>
            </div>
        </template>
        <template x-if="confirmation === 'warning_issued'">
            <div>
                <div class="w-16 h-16 mx-auto rounded-full bg-orange-100 flex items-center justify-center mb-4">
                    <span class="text-orange-600 text-3xl">!</span>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-1">Warning Issued</h3>
                <p class="text-sm text-gray-500">The seller has been notified about this warning.</p>
            </div>
        </template>
        <template x-if="confirmation === 'product_approved'">
            <div>
                <div class="w-16 h-16 mx-auto rounded-full bg-green-100 flex items-center justify-center mb-4">
                    <span class="text-green-600 text-3xl">✓</span>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-1">Product Approved</h3>
                <p class="text-sm text-gray-500">The product is now visible to buyers.</p>
            </div>
        </template>
        <button type="button" @click="confirmation = null"
            class="mt-5 px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium w-full">Close</button>
    </div>
</div>