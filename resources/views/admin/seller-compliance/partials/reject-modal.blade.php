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
    class="fixed inset-0 z-[60] flex items-center justify-center bg-vd-ink/50 p-4 backdrop-blur-[2px]">

    <div x-show="rejectId === {{ $product->id }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white border border-vd-line-strong">

        <form method="POST" action="{{ route('admin.seller-compliance.products.reject', $product) }}"
            data-draft-key="admin-{{ auth('admin')->id() }}-reject-product-{{ $product->id }}"
            x-data="{ reason: '', details: '', busy: false }" @submit="busy = true" class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="px-6 pb-4 pt-7 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-vd-danger-bg text-vd-danger-ink">
                    <x-admin.icon name="shield-x" class="h-7 w-7" stroke="1.7" />
                </span>
                <h3 id="{{ $titleId }}" class="text-lg font-semibold text-vd-ink">Reject product</h3>
                <p class="mt-1 text-sm text-vd-muted">A violation is recorded for the seller, and the seller is notified of the rejection.</p>
            </div>

            <div class="thin-scroll flex-1 overflow-y-auto px-6 pb-2">
                <p class="mb-3 truncate rounded-xl bg-vd-tint px-3.5 py-2 text-[13px] text-vd-muted">
                    <span class="text-vd-muted">Product:</span> <span class="font-medium text-vd-ink">{{ $product->name }}</span>
                </p>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-vd-ink">Reason for rejection <span class="text-vd-danger-ink" aria-hidden="true">*</span></legend>
                    <div class="space-y-2">
                        @foreach ($reasons as $option)
                            <label class="relative block cursor-pointer">
                                <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required
                                    @if ($loop->first) id="reject-first-{{ $product->id }}" @endif class="peer absolute inset-0 opacity-0">
                                <span :class="reason === @js($option)
                                        ? 'border-[#9B1C2C] bg-vd-danger-bg text-vd-ink'
                                        : 'border-vd-line-strong text-vd-ink hover:bg-vd-tint'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm transition duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-vd-plum/40">
                                    <i :class="reason === @js($option) ? 'border-[#9B1C2C]' : 'border-vd-plum-soft'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full border-2 transition duration-150">
                                        <b :class="reason === @js($option) ? 'scale-100' : 'scale-0'" class="h-2 w-2 rounded-full bg-[#9B1C2C] transition duration-200 ease-vendo"></b>
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-vd-ink" for="reject-details-{{ $product->id }}">
                    Details <span class="text-vd-danger-ink" aria-hidden="true">*</span>
                </label>
                <textarea id="reject-details-{{ $product->id }}" name="details" x-model="details" maxlength="500" required rows="3"
                    placeholder="Tell the seller what needs to change"
                    class="mt-2 w-full resize-none rounded-xl border border-vd-line-strong p-3 text-sm text-vd-ink placeholder:text-vd-muted
                           transition duration-200 focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-vd-muted" x-text="details.length + '/500'">0/500</p>
            </div>

            <div class="flex gap-3 border-t border-vd-line bg-vd-tint px-6 py-4">
                <button type="button" @click="rejectId = null"
                    class="h-11 flex-1 rounded-xl border border-vd-line-strong bg-white text-sm font-semibold text-vd-ink transition duration-200 hover:bg-vd-tint active:scale-[0.98]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    Cancel
                </button>
                <button type="submit" :disabled="busy"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white transition duration-200 active:scale-[0.98]
                           disabled:cursor-wait disabled:opacity-70 bg-[#9B1C2C] hover:bg-[#7F1522] focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                    <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    Reject Product
                </button>
            </div>
        </form>
    </div>
</div>