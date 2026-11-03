{{--
    Suspended sellers table + activate dialogs. Rendered inside #sc-region (scTable scope);
    needs $sellers. Dialog state (activateId) comes from the scope. The activate form posts to the
    existing admin.user-management.activate route.
    Clicking a row opens that seller's suspension details popup (suspension-detail-modal).
--}}
@php
    // Details popups: batched queries per page, not per row.
    $sellerIds = $sellers->getCollection()->pluck('id');
    $warnCounts = \App\Models\Compliance\ProductWarning::whereIn('seller_id', $sellerIds)->selectRaw('seller_id, COUNT(*) as c')->groupBy('seller_id')->pluck('c', 'seller_id');
    $violCounts = \App\Models\Compliance\ProductViolation::whereIn('seller_id', $sellerIds)->selectRaw('seller_id, COUNT(*) as c')->groupBy('seller_id')->pluck('c', 'seller_id');
    $productCounts = \App\Models\Ecommerce\Product::whereIn('seller_id', $sellerIds)->selectRaw('seller_id, COUNT(*) as c')->groupBy('seller_id')->pluck('c', 'seller_id');
    $latestViolations = \App\Models\Compliance\ProductViolation::with('product')->whereIn('seller_id', $sellerIds)->latest()->get()->groupBy('seller_id');
@endphp
<div class="sc-rows overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($sellers->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'shield-check',
            'tone' => 'green',
            'title' => 'No suspended sellers',
            'text' => 'Suspended sellers appear here.',
            'filteredTitle' => 'No suspended sellers found',
            'filteredText' => 'Check the spelling or clear the search.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-center text-sm">
                <caption class="sr-only">Suspended sellers. Select a row to see the suspension details.</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Reason</th>
                        <th scope="col" class="px-4 py-3 font-medium">Time remaining</th>
                        <th scope="col" class="px-5 py-3 text-center font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($sellers as $seller)
                        <tr style="--i: {{ $loop->index }}"
                            @click="detailId = {{ $seller->id }}"
                            class="cursor-pointer transition-colors duration-150 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$seller" />
                                    <div class="min-w-0">
                                        <p class="max-w-[220px] truncate font-medium text-vd-ink">{{ $seller->name }}</p>
                                        <p class="max-w-[220px] truncate text-xs text-vd-muted">{{ $seller->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="mx-auto max-w-[260px] text-vd-ink">{{ $seller->suspension_reason ?: '—' }}</p>
                                @if ($seller->suspended_at)
                                    <p class="text-xs text-vd-muted">Since {{ $seller->suspended_at->format('M j, Y') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (is_null($seller->suspended_until))
                                    <span class="inline-flex items-center gap-1.5 rounded-full border-[1.5px] border-[#9B1C2C] bg-[#F9D6DA] px-3 py-0.5 text-xs font-semibold text-[#9B1C2C]">
                                        <x-admin.icon name="infinity" class="h-3.5 w-3.5" /> Permanent
                                    </span>
                                @else
                                    {{-- Countdown. destroy() clears the timer when the region is swapped. --}}
                                    <span class="inline-flex items-center gap-1.5 rounded-full border-[1.5px] border-[#9B1C2C] bg-[#F9D6DA] px-3 py-0.5 text-xs font-semibold tabular-nums text-[#9B1C2C]"
                                        x-data="{
                                            end: new Date(@js($seller->suspended_until->toIso8601String())).getTime(),
                                            text: '',
                                            t: null,
                                            tick() {
                                                const diff = this.end - Date.now();
                                                if (diff <= 0) { this.text = 'Ending now'; return; }
                                                const d = Math.floor(diff / 86400000);
                                                const h = Math.floor((diff % 86400000) / 3600000);
                                                const m = Math.floor((diff % 3600000) / 60000);
                                                const s = Math.floor((diff % 60000) / 1000);
                                                this.text = `${d}d ${h}h ${m}m ${s}s left`;
                                            },
                                            init() { this.tick(); this.t = setInterval(() => this.tick(), 1000); },
                                            destroy() { clearInterval(this.t); },
                                        }">
                                        <x-admin.icon name="hourglass" class="h-3.5 w-3.5" />
                                        <span x-text="text"></span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                <button type="button" aria-haspopup="dialog" @click.stop="detailId = {{ $seller->id }}" aria-label="View {{ $seller->name }}"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">View</button>
                                <button type="button" aria-haspopup="dialog" @click.stop="activateId = {{ $seller->id }}"
                                    aria-label="Activate {{ $seller->name }}"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200
                                           hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                    <x-admin.icon name="rotate-ccw" class="h-4 w-4" /> Activate
                                </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $sellers])
    @endif
</div>

{{-- Details popups come first in the DOM so the activate dialog below stacks on top of them. --}}
@foreach ($sellers as $seller)
    @include('admin.seller-compliance.partials.suspension-detail-modal', [
        'seller' => $seller,
        'violations' => ($latestViolations[$seller->id] ?? collect())->take(5),
        'warnCount' => (int) ($warnCounts[$seller->id] ?? 0),
        'violCount' => (int) ($violCounts[$seller->id] ?? 0),
        'productCount' => (int) ($productCounts[$seller->id] ?? 0),
    ])
@endforeach

@foreach ($sellers as $seller)
    @php $titleId = 'activate-seller-title-' . $seller->id; @endphp
    <div x-show="activateId === {{ $seller->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
        @click.self="activateId = null"
        x-effect="if (activateId === {{ $seller->id }}) $nextTick(() => document.getElementById('activate-cancel-{{ $seller->id }}')?.focus())"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4 backdrop-blur-[2px]">

        <div x-show="activateId === {{ $seller->id }}" @click.stop
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md overflow-hidden rounded-2xl bg-white border border-vd-line-strong">

            <form method="POST" action="{{ route('admin.user-management.activate', $seller) }}" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <div class="px-6 pb-5 pt-7 text-center">
                    <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-vd-success-bg text-vd-success-ink">
                        <x-admin.icon name="rotate-ccw" class="h-7 w-7" stroke="1.7" />
                    </span>
                    <h3 id="{{ $titleId }}" class="text-lg font-semibold text-vd-ink">Lift suspension?</h3>
                    <p class="mt-1.5 text-sm text-vd-muted">
                        The suspension on {{ $seller->name }} will end now, and they can sign in and sell on Vendo again.
                    </p>
                </div>

                <div class="flex gap-3 border-t border-vd-line bg-vd-tint px-6 py-4">
                    <button type="button" id="activate-cancel-{{ $seller->id }}" @click="activateId = null"
                        class="h-11 flex-1 rounded-xl border border-vd-line-strong bg-white text-sm font-semibold text-vd-ink transition duration-200 hover:bg-vd-tint active:scale-[0.98]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                        Cancel
                    </button>
                    <button type="submit" :disabled="busy"
                        class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-vd-plum text-sm font-semibold text-white transition duration-200
                               hover:bg-vd-plum-hover active:scale-[0.98] disabled:cursor-wait disabled:opacity-70
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                        <span x-show="busy" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                        Lift suspension
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach