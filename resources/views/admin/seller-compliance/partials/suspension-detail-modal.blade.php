{{--
    Details popup for one suspended seller (Suspended tab).

    Needs: $seller, $violations (that seller's latest violations, newest first, products loaded),
           $warnCount, $violCount, $productCount.
    Opens when detailId === $seller->id. "Lift suspension" hands over to the existing activate dialog
    (activateId) so the confirmation and the POST stay exactly as before.
--}}
@php
    $score = (int) $seller->compliance_score;
    $scoreTone = $score >= 80 ? ['bg-[#2E8B3E]', 'text-vd-success-ink'] : ($score >= 50 ? ['bg-vd-gold', 'text-vd-warning-ink'] : ['bg-vd-danger-ink', 'text-vd-danger-ink']);
    $permanent = is_null($seller->suspended_until);
    $titleId = 'sc-susp-title-' . $seller->id;

    // The server escalates 3 / 6 / 9 violations to 7 days / 30 days / permanent (see SellerComplianceController).
    $viaViolations = str_contains(strtolower((string) $seller->suspension_reason), 'violation');
    $durationDays = ($seller->suspended_at && $seller->suspended_until)
        ? (int) round($seller->suspended_at->diffInDays($seller->suspended_until, true))
        : null;
@endphp

<div x-show="detailId === {{ $seller->id }}" x-cloak
    x-effect="if (detailId === {{ $seller->id }}) { $nextTick(() => document.getElementById('sc-susp-close-{{ $seller->id }}')?.focus()) }"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="detailId = null"
    class="fixed inset-0 z-50 flex items-end justify-center bg-vd-ink/50 p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">

    <div @click.stop x-show="detailId === {{ $seller->id }}"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="flex max-h-[min(90dvh,800px)] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white border border-vd-line-strong sm:rounded-3xl">

        {{-- Header --}}
        <div class="flex items-start gap-4 border-b border-vd-line px-5 py-4 sm:px-6">
            <x-admin.avatar :user="$seller" size="h-12 w-12" text="text-lg" />
            <div class="min-w-0 flex-1">
                <h3 id="{{ $titleId }}" class="font-display text-lg font-semibold leading-tight text-vd-ink [overflow-wrap:anywhere]">{{ $seller->name }}</h3>
                <p class="text-sm text-vd-muted [overflow-wrap:anywhere]">{{ $seller->email }}</p>
                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-[#9B1C2C] bg-vd-danger-bg px-2.5 py-0.5 text-xs font-semibold text-vd-danger-ink">
                    <x-admin.icon name="ban" class="h-3.5 w-3.5" /> {{ $permanent ? 'Permanently suspended' : 'Suspended' }}
                </span>
            </div>
            <button type="button" id="sc-susp-close-{{ $seller->id }}" @click="detailId = null" aria-label="Close details"
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-vd-muted transition-colors duration-150 hover:bg-vd-lavender hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="thin-scroll flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">

            {{-- Suspension --}}
            <section aria-label="Suspension" class="rounded-2xl border border-vd-danger-line bg-vd-danger-bg/60 p-4">
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-vd-muted">Reason</dt>
                        <dd class="mt-0.5 font-medium text-vd-ink [overflow-wrap:anywhere]">{{ $seller->suspension_reason ?: 'No reason recorded' }}</dd>
                    </div>
                    @if ($seller->suspension_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-vd-muted">Notes</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-vd-ink [overflow-wrap:anywhere]">{{ $seller->suspension_notes }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-vd-muted">Started</dt>
                        <dd class="mt-0.5 text-vd-ink">{{ $seller->suspended_at ? $seller->suspended_at->format('M d, Y · g:i A') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-vd-muted">Ends</dt>
                        <dd class="mt-0.5 text-vd-ink">
                            @if ($permanent)
                                No end date. A person has to lift it.
                            @else
                                {{ $seller->suspended_until->format('M d, Y · g:i A') }}{{ $durationDays ? ' (' . $durationDays . '-day suspension)' : '' }}
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-vd-muted">How it happened</dt>
                        <dd class="mt-0.5 text-vd-ink">
                            {{ $viaViolations ? 'Applied automatically when the seller reached a violation threshold (3, 6 or 9).' : 'Applied by an administrator.' }}
                        </dd>
                    </div>
                </dl>
                @unless ($permanent)
                    <p class="mt-3 border-t border-vd-danger-line pt-3 text-xs text-vd-muted">
                        A timed suspension does not lift on its own. Use “Lift suspension” when the time is up.
                    </p>
                @endunless
            </section>

            {{-- Standing --}}
            <section aria-label="Seller standing" class="rounded-2xl border border-vd-line p-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        ['Products', number_format($productCount), 'text-vd-ink'],
                        ['Compliance score', $score . '%', $scoreTone[1]],
                        ['Warnings', $warnCount, $warnCount > 0 ? 'text-[#C2452D]' : 'text-vd-muted'],
                        ['Violations', $violCount, $violCount > 0 ? 'text-vd-danger-ink' : 'text-vd-muted'],
                    ] as [$label, $value, $tone])
                        <div>
                            <p class="text-xs text-vd-muted">{{ $label }}</p>
                            <p class="text-xl font-semibold tabular-nums {{ $tone }}">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-vd-lavender" role="img" aria-label="Compliance score {{ $score }} percent">
                    <div class="h-full rounded-full {{ $scoreTone[0] }}" style="width: {{ $score }}%"></div>
                </div>
                <div class="mt-2">@include('admin.seller-compliance.partials.score-note')</div>
            </section>

            {{-- Recent violations --}}
            <section aria-label="Recent violations">
                <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-vd-muted">Latest violations</h4>
                @forelse ($violations as $violation)
                    <div class="flex items-start gap-3 border-t border-vd-line py-2.5 first:border-t-0">
                        <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-vd-danger-bg text-vd-danger-ink"><x-admin.icon name="ban" class="h-3.5 w-3.5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-vd-ink [overflow-wrap:anywhere]">{{ $violation->reason }}</p>
                            <p class="text-xs text-vd-muted [overflow-wrap:anywhere]">{{ $violation->product->name ?? 'Product removed' }} · {{ $violation->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-vd-tint px-4 py-5 text-center text-sm text-vd-muted">No violations on record. This suspension was applied by hand.</p>
                @endforelse
            </section>
        </div>

        {{-- Footer --}}
        <div class="flex flex-col-reverse gap-2 border-t border-vd-line bg-vd-tint px-5 py-3.5 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <button type="button" @click="detailId = null"
                class="inline-flex h-10 items-center justify-center rounded-full border border-vd-line-strong bg-white px-5 text-sm font-medium text-vd-ink transition duration-200 hover:bg-vd-tint active:scale-95
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                Close
            </button>
            <button type="button" aria-haspopup="dialog" @click="detailId = null; activateId = {{ $seller->id }}"
                class="inline-flex h-10 items-center justify-center gap-1.5 rounded-full bg-vd-plum px-5 text-sm font-semibold text-white transition duration-200 hover:bg-vd-plum-hover active:scale-95
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                <x-admin.icon name="rotate-ccw" class="h-4 w-4" /> Lift suspension
            </button>
        </div>
    </div>
</div>