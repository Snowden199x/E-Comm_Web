{{--
    Reject product dialog. Needs $product and the scTable scope { rejectId }.
    Posts reason + details to the existing admin.seller-compliance.products.reject route.
--}}
@php
    $titleId = 'reject-title-' . $product->id;
    $reasons = ['Prohibited product', 'Product does not match the registered category', 'Inappropriate product content', 'Misleading product information', 'Violation of platform policies', 'Other (please specify)'];
@endphp

<div x-show="rejectId === {{ $product->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="rejectId = null"
    x-effect="if (rejectId === {{ $product->id }}) $nextTick(() => document.getElementById('reject-first-{{ $product->id }}')?.focus())"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

    <div x-show="rejectId === {{ $product->id }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        <form method="POST" action="{{ route('admin.seller-compliance.products.reject', $product) }}"
            data-draft-key="admin-{{ auth('admin')->id() }}-reject-product-{{ $product->id }}"
            x-data="{ reason: '', details: '', busy: false }" @submit="busy = true" class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="px-6 pb-4 pt-7 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-700">
                    <x-admin.icon name="shield-x" class="h-7 w-7" stroke="1.7" />
                </span>
                <h3 id="{{ $titleId }}" class="text-lg font-semibold text-[#2B1730]">Reject product</h3>
                <p class="mt-1 text-sm text-gray-500">A violation is recorded for the seller, and the seller is notified of the rejection.</p>
            </div>

            <div class="thin-scroll flex-1 overflow-y-auto px-6 pb-2">
                <p class="mb-3 truncate rounded-xl bg-[#FBF8FB] px-3.5 py-2 text-[13px] text-gray-600">
                    <span class="text-gray-500">Product:</span> <span class="font-medium text-[#2B1730]">{{ $product->name }}</span>
                </p>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-[#2B1730]">Reason for rejection <span class="text-red-500" aria-hidden="true">*</span></legend>
                    <div class="space-y-2">
                        @foreach ($reasons as $option)
                            <label class="relative block cursor-pointer">
                                <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required
                                    @if ($loop->first) id="reject-first-{{ $product->id }}" @endif class="peer absolute inset-0 opacity-0">
                                <span :class="reason === @js($option)
                                        ? 'border-red-700 bg-red-50 text-[#2B1730]'
                                        : 'border-[#e2d6e5] text-gray-700 hover:bg-[#FBF8FB]'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm transition duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-red-600/40">
                                    <i :class="reason === @js($option) ? 'border-red-700' : 'border-[#cdbbd2]'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full border-2 transition duration-150">
                                        <b :class="reason === @js($option) ? 'scale-100' : 'scale-0'" class="h-2 w-2 rounded-full bg-red-700 transition duration-200 ease-vendo"></b>
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-[#2B1730]" for="reject-details-{{ $product->id }}">
                    Details <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <textarea id="reject-details-{{ $product->id }}" name="details" x-model="details" maxlength="500" required rows="3"
                    placeholder="Tell the seller what needs to change"
                    class="mt-2 w-full resize-none rounded-xl border border-[#ddd0e0] p-3 text-sm text-[#2B1730] placeholder:text-gray-400
                           transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-gray-400" x-text="details.length + '/500'">0/500</p>
            </div>

            <div class="flex gap-3 border-t border-[#ece4ec] bg-[#FBF8FB] px-6 py-4">
                <button type="button" @click="rejectId = null"
                    class="h-11 flex-1 rounded-xl border border-[#d9ccdc] bg-white text-sm font-semibold text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    Cancel
                </button>
                <button type="submit" :disabled="busy"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white transition duration-200 active:scale-[0.98]
                           disabled:cursor-wait disabled:opacity-70 bg-red-700 hover:bg-red-800 focus-visible:ring-2 focus-visible:ring-red-600/40 focus-visible:ring-offset-2">
                    <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    Reject product
                </button>
            </div>
        </form>
    </div>
</div>