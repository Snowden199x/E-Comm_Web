{{--
    Result dialog after an action redirects back with session('confirmation').
    Needs the scTable scope { confirmation }. Handles: product_rejected, warning_issued,
    product_approved (Products for Review) and suspension_lifted (Suspended Sellers).
--}}
@php
    $results = [
        'product_rejected' => ['Product rejected', 'The product was rejected, a violation was recorded, and the seller has been notified.', 'bg-red-50 text-red-700', 'M7 7l10 10M17 7 7 17'],
        'warning_issued' => ['Warning issued', 'The seller has been notified about this warning.', 'bg-orange-50 text-orange-600', 'M12 6.5v7M12 17.5h.01'],
        'product_approved' => ['Product approved', 'The product is approved for sale and the seller has been notified.', 'bg-green-50 text-green-700', 'm5.5 12.5 4.5 4.5 8.5-9.5'],
        'suspension_lifted' => ['Suspension lifted', 'The seller can sign in and sell again.', 'bg-green-50 text-green-700', 'm5.5 12.5 4.5 4.5 8.5-9.5'],
    ];
@endphp

<div x-show="confirmation" x-cloak role="dialog" aria-modal="true" aria-labelledby="sc-confirmation-title" @click.self="confirmation = null"
    x-effect="if (confirmation) $nextTick(() => $refs.confirmClose && $refs.confirmClose.focus())"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[70] flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

    <div x-show="confirmation" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-sm overflow-hidden rounded-2xl bg-white px-8 pb-6 pt-8 text-center shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        @foreach ($results as $key => [$title, $text, $tone, $glyph])
            {{-- x-if re-creates the node each time, so the pop and draw animations replay --}}
            <template x-if="confirmation === '{{ $key }}'">
                <div>
                    <span class="sc-result mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full {{ $tone }}">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path class="sc-glyph" d="{{ $glyph }}" />
                        </svg>
                    </span>
                    <h3 @if ($loop->first) id="sc-confirmation-title" @endif class="text-lg font-semibold text-[#2B1730]" role="status">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $text }}</p>
                </div>
            </template>
        @endforeach

        <button type="button" x-ref="confirmClose" @click="confirmation = null"
            class="mt-6 h-11 w-full rounded-xl bg-[#3b1735] text-sm font-semibold text-white transition duration-200 hover:bg-[#4d1f45] active:scale-[0.98]
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
            Done
        </button>
    </div>
</div>