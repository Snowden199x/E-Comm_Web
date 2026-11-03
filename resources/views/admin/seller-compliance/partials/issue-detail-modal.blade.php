{{--
    Details popup for one warning or one violation (Warnings and Violations tabs).

    Needs: $item (ProductWarning|ProductViolation), $kind ('warning'|'violation'),
           $warnCount, $violCount (that seller's totals, grouped queries done once per page by the caller).
    Opens when the Alpine scope (scTable) has detailId === $item->id; the row sets it on click.

    Everything shown is already stored on the record or derivable from it; no controller change.
    The "what happens next" thresholds mirror the server rules (every 3rd warning records a violation;
    3 / 6 / 9 violations suspend for 7 days / 30 days / permanently).
--}}
@php
    $isWarning = $kind === 'warning';
    $product = $item->product;
    $seller = $item->seller;
    $suspended = $seller && $seller->account_status === 'suspended';
    $score = $seller ? (int) $seller->compliance_score : null;
    $scoreTone = $score === null ? ['bg-vd-line-strong', 'text-vd-muted']
        : ($score >= 80 ? ['bg-[#2E8B3E]', 'text-vd-success-ink'] : ($score >= 50 ? ['bg-vd-gold', 'text-vd-warning-ink'] : ['bg-vd-danger-ink', 'text-vd-danger-ink']));
    $cover = $product?->images?->first();
    $coverUrl = $cover ? \Illuminate\Support\Facades\Storage::url($cover->path) : null;
    $productStatus = [
        'approved' => ['Approved', 'border-[#2E8B3E] bg-vd-success-bg text-vd-success-ink'],
        'pending' => ['Pending', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
        'rejected' => ['Rejected', 'border-[#9B1C2C] bg-vd-danger-bg text-vd-danger-ink'],
        'warned' => ['Warned', 'border-[#E58A76] bg-[#FDE6DF] text-[#C2452D]'],
    ];
    $pStatus = $product ? ($productStatus[$product->status] ?? [ucfirst(str_replace('_', ' ', (string) $product->status)), 'border-vd-line-strong bg-vd-tint text-vd-muted']) : null;
    $automatic = ! $isWarning && str_starts_with(strtolower((string) $item->reason), 'accumulated');

    // What happens next
    $untilViolation = 3 - ($warnCount % 3);
    $levels = [3 => '7-day suspension', 6 => '30-day suspension', 9 => 'permanent suspension'];
    $nextLevel = null;
    foreach ($levels as $at => $label) {
        if ($violCount < $at) { $nextLevel = [$at, $label]; break; }
    }

    $tone = $isWarning
        ? ['ring' => 'bg-[#FDE6DF] text-[#C2452D]', 'chip' => 'border-[#E58A76] bg-[#FDE6DF] text-[#C2452D]', 'panel' => 'border-[#F2B8A9] bg-[#FDE6DF]/60']
        : ['ring' => 'bg-vd-danger-bg text-vd-danger-ink', 'chip' => 'border-[#9B1C2C] bg-vd-danger-bg text-vd-danger-ink', 'panel' => 'border-vd-danger-line bg-vd-danger-bg/60'];
    $titleId = 'sc-detail-title-' . $kind . '-' . $item->id;
@endphp

<div x-show="detailId === {{ $item->id }}" x-cloak
    x-effect="if (detailId === {{ $item->id }}) { $nextTick(() => document.getElementById('sc-detail-close-{{ $kind }}-{{ $item->id }}')?.focus()) }"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="detailId = null"
    class="fixed inset-0 z-50 flex items-end justify-center bg-vd-ink/50 p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">

    <div @click.stop x-show="detailId === {{ $item->id }}"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="flex max-h-[min(90dvh,780px)] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white border border-vd-line-strong sm:rounded-3xl">

        {{-- Header --}}
        <div class="flex items-start gap-4 border-b border-vd-line px-5 py-4 sm:px-6">
            <span aria-hidden="true" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full {{ $tone['ring'] }}">
                <x-admin.icon :name="$isWarning ? 'triangle-alert' : 'ban'" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="{{ $titleId }}" class="font-display text-lg font-semibold leading-tight text-vd-ink">
                    {{ $isWarning ? 'Warning' : 'Violation' }} #{{ $item->id }}
                </h3>
                <p class="mt-0.5 text-sm text-vd-muted">Issued {{ $item->created_at->format('M d, Y') }} at {{ $item->created_at->format('g:i A') }}</p>
            </div>
            <button type="button" id="sc-detail-close-{{ $kind }}-{{ $item->id }}" @click="detailId = null" aria-label="Close details"
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-vd-muted transition-colors duration-150 hover:bg-vd-lavender hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="thin-scroll flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">

            {{-- Reason --}}
            <section aria-label="Reason">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $tone['chip'] }}">
                        <x-admin.icon :name="$isWarning ? 'triangle-alert' : 'ban'" class="h-3.5 w-3.5 flex-shrink-0" />
                        <span class="[overflow-wrap:anywhere]">{{ $item->reason }}</span>
                    </span>
                    @if ($automatic)
                        <span class="inline-flex items-center rounded-full bg-vd-lavender px-2 py-0.5 text-[11px] font-medium text-vd-orchid">Recorded automatically</span>
                    @endif
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-vd-ink [overflow-wrap:anywhere]">{{ $item->details ?: 'No additional details were written for this ' . $kind . '.' }}</p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Product --}}
                <section aria-label="Product" class="rounded-2xl border border-vd-line p-4">
                    <h4 class="mb-3 text-xs font-medium uppercase tracking-wide text-vd-muted">Product</h4>
                    @if ($product)
                        <div class="flex gap-3">
                            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl bg-vd-tint">
                                @if ($coverUrl)
                                    <img src="{{ $coverUrl }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-vd-plum-soft"><x-admin.icon name="package" class="h-6 w-6" /></span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium leading-snug text-vd-ink [overflow-wrap:anywhere]">{{ $product->name }}</p>
                                <p class="mt-0.5 text-xs text-vd-muted">{{ $product->category->name ?? 'No category' }}</p>
                                <p class="mt-1 text-sm tabular-nums text-vd-ink">₱{{ number_format((float) $product->price, 2) }} · {{ number_format((int) $product->stock) }} in stock</p>
                            </div>
                        </div>
                        <span class="mt-3 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pStatus[1] }}">Product is now: {{ $pStatus[0] }}</span>
                    @else
                        <p class="text-sm text-vd-muted">This product is no longer available.</p>
                    @endif
                </section>

                {{-- Seller --}}
                <section aria-label="Seller" class="rounded-2xl border border-vd-line p-4">
                    <h4 class="mb-3 text-xs font-medium uppercase tracking-wide text-vd-muted">Seller</h4>
                    @if ($seller)
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :user="$seller" size="h-11 w-11" text="text-base" />
                            <div class="min-w-0">
                                <p class="font-medium leading-snug text-vd-ink [overflow-wrap:anywhere]">{{ $seller->name }}</p>
                                <p class="text-xs text-vd-muted [overflow-wrap:anywhere]">{{ $seller->email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-admin.status-pill :tone="$suspended ? 'danger' : 'success'" :icon="$suspended ? 'ban' : 'shield-check'">{{ $suspended ? 'Suspended' : 'Compliant' }}</x-admin.status-pill>
                            <span class="text-xs text-vd-muted">{{ $warnCount }} {{ \Illuminate\Support\Str::plural('warning', $warnCount) }} · {{ $violCount }} {{ \Illuminate\Support\Str::plural('violation', $violCount) }}</span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-baseline justify-between text-xs">
                                <span class="text-vd-muted">Compliance score</span>
                                <span class="text-sm font-semibold tabular-nums {{ $scoreTone[1] }}">{{ $score }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-vd-lavender" role="img" aria-label="Compliance score {{ $score }} percent">
                                <div class="h-full rounded-full {{ $scoreTone[0] }}" style="width: {{ $score }}%"></div>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-vd-muted">This seller account is no longer available.</p>
                    @endif
                </section>
            </div>

            {{-- What this means --}}
            <section aria-label="What this means" class="rounded-2xl border p-4 {{ $tone['panel'] }}">
                <h4 class="text-sm font-semibold text-vd-ink">What this means</h4>
                <ul class="mt-2 space-y-1.5 text-sm text-vd-ink">
                    @if ($isWarning)
                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[#E58A76]"></span>
                            <span><strong class="font-medium">Score impact: none.</strong> A warning does not change the compliance score on its own.</span></li>
                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[#E58A76]"></span>
                            <span>This seller has {{ $warnCount }} {{ \Illuminate\Support\Str::plural('warning', $warnCount) }}. Every 3rd warning records a violation automatically ({{ $untilViolation }} more for the next one).</span></li>
                    @else
                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-vd-danger-ink"></span>
                            <span><strong class="font-medium">Score impact: −10 points.</strong> Each violation lowers the compliance score by 10.</span></li>
                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-vd-danger-ink"></span>
                            <span>
                                This seller has {{ $violCount }} {{ \Illuminate\Support\Str::plural('violation', $violCount) }}.
                                @if ($nextLevel)
                                    At {{ $nextLevel[0] }} violations the account gets a {{ $nextLevel[1] }} ({{ $nextLevel[0] - $violCount }} more).
                                @else
                                    The account is already at the permanent suspension level.
                                @endif
                            </span></li>
                    @endif
                </ul>
            </section>

            @include('admin.seller-compliance.partials.score-note')
        </div>

        {{-- Footer --}}
        <div class="flex flex-col-reverse gap-2 border-t border-vd-line bg-vd-tint px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            @if ($seller)
                <a href="{{ route('admin.seller-compliance.overview', ['search' => $seller->email]) }}"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-full px-4 text-sm font-medium text-vd-plum transition duration-200 hover:bg-vd-lavender
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    <x-admin.icon name="store" class="h-4 w-4" /> Open seller's record
                </a>
            @else
                <span></span>
            @endif
            <button type="button" @click="detailId = null"
                class="inline-flex h-10 items-center justify-center rounded-full bg-vd-plum px-6 text-sm font-medium text-white transition duration-200 hover:bg-vd-ink active:scale-95
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                Close
            </button>
        </div>
    </div>
</div>