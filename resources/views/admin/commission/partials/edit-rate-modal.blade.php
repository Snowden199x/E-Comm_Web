{{--
    Edit commission rate dialog. Uses the page scope (editRateOpen, newRate, rateValid, ratePreview, monthSales, peso).
    Posts the same `rate` field to admin.commission.update-rate; nothing about the request changed.
--}}
<div x-show="editRateOpen" x-cloak
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="editRateOpen = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]"
    role="dialog" aria-modal="true" aria-labelledby="cm-rate-title">

    <div x-show="editRateOpen" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        <form method="POST" action="{{ route('admin.commission.update-rate') }}" x-data="{ busy: false }" @submit="if (!rateValid) { $event.preventDefault(); return; } busy = true">
            @csrf
            <div class="flex items-start justify-between gap-4 px-6 pb-2 pt-6">
                <div>
                    <h3 id="cm-rate-title" class="font-display text-lg font-semibold text-[#2B1730]">Commission rate</h3>
                    <p class="mt-1 text-sm text-gray-500">The share of each completed sale that Vendo keeps.</p>
                </div>
                <button type="button" @click="editRateOpen = false" aria-label="Close"
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors duration-150 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <div class="space-y-4 px-6 pb-5 pt-3">
                <div>
                    <label for="cm-rate-input" class="mb-1.5 block text-[13px] font-medium text-gray-600">Rate (%)</label>
                    <div class="relative">
                        <input id="cm-rate-input" x-ref="rateInput" type="number" name="rate" step="0.01" min="0" max="100" required inputmode="decimal"
                            x-model="newRate" :aria-invalid="!rateValid"
                            :class="rateValid ? 'border-[#ddd0e0] focus:border-[#3b1735] focus:ring-[#3b1735]/20' : 'border-red-400 focus:border-red-500 focus:ring-red-200'"
                            class="h-12 w-full rounded-xl border bg-white pl-4 pr-10 text-lg font-semibold tabular-nums text-[#2B1730] transition duration-200 focus:outline-none focus:ring-2">
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true">%</span>
                    </div>
                    <p x-show="!rateValid" x-cloak role="alert" class="mt-1.5 text-xs text-red-600">Enter a rate between 0 and 100.</p>

                    <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Quick rates">
                        @foreach ([5, 8, 10, 12, 15] as $preset)
                            <button type="button" @click="newRate = {{ $preset }}" :aria-pressed="Number(newRate) === {{ $preset }}"
                                :class="Number(newRate) === {{ $preset }} ? 'border-[#3b1735] bg-[#F7F1F7] text-[#3b1735]' : 'border-[#ddd0e0] text-gray-600 hover:bg-[#F7F1F7]'"
                                class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors duration-150">{{ $preset }}%</button>
                        @endforeach
                    </div>
                </div>

                {{-- Live preview on the month on screen --}}
                <div class="rounded-xl bg-[#FBF8FB] px-4 py-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-500">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }} commission</span>
                        <span class="font-semibold tabular-nums text-[#2B1730]" x-text="ratePreview === null ? '—' : peso(ratePreview)"></span>
                    </div>
                    <div class="mt-1 flex items-center justify-between gap-3 text-xs text-gray-500">
                        <span>Now</span>
                        <span class="tabular-nums" x-text="peso(monthSales * (currentRate / 100))"></span>
                    </div>
                </div>

                <p class="flex items-start gap-2 rounded-xl border border-[#F3D9A6] bg-[#FDF3E2] px-3 py-2.5 text-[13px] leading-relaxed text-[#8a5614]">
                    <x-admin.icon name="alert-circle" class="mt-0.5 h-4 w-4 flex-shrink-0" />
                    <span>Commission isn't saved per order yet, so the new rate also changes the figures for past months. Check with finance before changing it mid-month.</span>
                </p>
            </div>

            <div class="flex gap-3 border-t border-[#ece4ec] bg-[#FBF8FB] px-6 py-4">
                <button type="button" @click="editRateOpen = false"
                    class="h-11 flex-1 rounded-xl border border-[#d9ccdc] bg-white text-sm font-semibold text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    Cancel
                </button>
                <button type="submit" :disabled="busy || !rateValid || Number(newRate) === currentRate"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#3b1735] text-sm font-semibold text-white transition duration-200
                           hover:bg-[#2B1730] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    Save rate
                </button>
            </div>
        </form>
    </div>
</div>