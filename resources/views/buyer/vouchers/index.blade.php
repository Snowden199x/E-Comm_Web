@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $primary = 'inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]';
    $fmt = fn ($amount) => '₱' . number_format((float) $amount, fmod((float) $amount, 1) ? 2 : 0);
@endphp

<x-buyer.layout title="Vouchers — Vendo">
    <div class="mx-auto max-w-[1040px] px-3 pb-10 pt-4 sm:px-4">
        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">My Vouchers</h1>
                <p class="mt-0.5 max-w-[640px] text-[13px] leading-5 text-[#7a6a7e]">Shop vouchers you can use at checkout. Pick one voucher per shop order; the discount is applied to the items that match its scope.</p>
            </div>
            <a href="{{ route('buyer.products.index') }}" class="{{ $primary }}">Browse products</a>
        </div>

        <section class="vb-reveal mt-4" style="--i: 1" aria-labelledby="voucher-list-title">
            <h2 id="voucher-list-title" class="sr-only">Available vouchers</h2>

            @if ($vouchers->isEmpty())
                <div class="{{ $card }} px-5 py-12 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9V6h18v3a3 3 0 0 0 0 6v3H3v-3a3 3 0 0 0 0-6z" /><path d="M13 6v12" stroke-dasharray="2 3" /></svg>
                    </span>
                    <p class="mt-3 text-[14px] font-semibold text-[#2b1730]">No vouchers available right now</p>
                    <p class="mx-auto mt-1 max-w-[380px] text-[13px] leading-5 text-[#7a6a7e]">When a Seller you follow activates a voucher, it appears here and in your notifications.</p>
                    <a href="{{ route('buyer.sellers.index') }}" class="mt-4 inline-flex h-10 items-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Browse shops</a>
                </div>
            @else
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach ($vouchers as $voucher)
                        @php
                            $shop = $voucher->seller?->sellerDetail?->business_name ?: ($voucher->seller?->name ?? 'Shop');
                            $started = ! $voucher->starts_at || $voucher->starts_at->isPast();
                            $remaining = $voucher->usage_limit === null ? null : max(0, $voucher->usage_limit - $voucher->used_count);
                            $usedByBuyer = (int) ($usedCounts[$voucher->id] ?? 0);
                            $perBuyerLeft = max(0, (int) $voucher->per_buyer_limit - $usedByBuyer);
                            $usable = $started && ($remaining === null || $remaining > 0) && $perBuyerLeft > 0;
                            $discount = $voucher->type === 'percent'
                                ? rtrim(rtrim(number_format((float) $voucher->value, 2, '.', ''), '0'), '.') . '% off'
                                : $fmt($voucher->value) . ' off';
                            $scope = match ($voucher->scope) {
                                'products' => 'Selected products only',
                                'categories' => 'Selected categories only',
                                default => 'All items in this shop',
                            };
                        @endphp
                        <li class="{{ $card }} relative overflow-hidden"
                            x-data="{
                                copied: false,
                                markCopied() { this.copied = true; setTimeout(() => this.copied = false, 1800); },
                                copy(code) {
                                    if (navigator.clipboard) {
                                        navigator.clipboard.writeText(code).then(() => this.markCopied()).catch(() => this.fallbackCopy(code));
                                    } else { this.fallbackCopy(code); }
                                },
                                fallbackCopy(code) {
                                    const field = document.createElement('textarea');
                                    field.value = code; field.setAttribute('readonly', '');
                                    field.style.position = 'fixed'; field.style.opacity = '0';
                                    document.body.appendChild(field); field.select();
                                    try { document.execCommand('copy'); this.markCopied(); } catch (_) {}
                                    field.remove();
                                },
                            }">
                            <span class="absolute inset-y-0 left-0 w-1.5" style="background: linear-gradient(180deg, #805487, #402143);" aria-hidden="true"></span>
                            <div class="flex h-full flex-col p-4 pl-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-[12px] font-medium text-[#805487]">{{ $shop }}</p>
                                        <p class="mt-0.5 line-clamp-2 text-[14px] font-semibold leading-5 text-[#2b1730]">{{ $voucher->name }}</p>
                                    </div>
                                    <p class="flex-shrink-0 rounded-md bg-[#f6f2f7] px-2.5 py-1 text-[13px] font-semibold text-[#52245b]">{{ $discount }}</p>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 text-[11px] leading-4 text-[#6f5f73]">
                                    <div><dt class="inline text-[#9a8a9d]">Scope:</dt> <dd class="inline">{{ $scope }}</dd></div>
                                    <div><dt class="inline text-[#9a8a9d]">Min. spend:</dt> <dd class="inline">{{ $voucher->min_spend > 0 ? $fmt($voucher->min_spend) : 'None' }}</dd></div>
                                    <div><dt class="inline text-[#9a8a9d]">Valid:</dt> <dd class="inline">
                                        @if (! $started) Starts {{ $voucher->starts_at->format('M j, Y') }}
                                        @elseif ($voucher->ends_at) Until {{ $voucher->ends_at->format('M j, Y') }}
                                        @else No end date @endif
                                    </dd></div>
                                    <div><dt class="inline text-[#9a8a9d]">You can use:</dt> <dd class="inline">{{ $perBuyerLeft }} more {{ \Illuminate\Support\Str::plural('time', $perBuyerLeft) }}</dd></div>
                                    @if ($voucher->max_discount)
                                        <div class="col-span-2"><dt class="inline text-[#9a8a9d]">Max discount:</dt> <dd class="inline">{{ $fmt($voucher->max_discount) }}</dd></div>
                                    @endif
                                    @if ($remaining !== null)
                                        <div class="col-span-2"><dt class="inline text-[#9a8a9d]">Shop-wide uses left:</dt> <dd class="inline">{{ $remaining }}</dd></div>
                                    @endif
                                </dl>

                                @if (! $usable)
                                    <p class="mt-2 text-[11px] font-medium text-[#a32b43]">
                                        @if (! $started) This voucher is not active yet.
                                        @elseif ($remaining !== null && $remaining <= 0) The shop has used up this voucher.
                                        @else You have reached this voucher’s use limit. @endif
                                    </p>
                                @endif

                                <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-dashed border-[#e5dce7] pt-3">
                                    <button type="button" @click="copy(@js($voucher->code))" :disabled="! @js($usable)"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md border border-dashed border-[#805487] bg-[#faf5fa] px-3 font-mono text-[12px] font-semibold uppercase tracking-wide text-[#52245b] transition-colors duration-200 hover:bg-[#f3e8f5] disabled:cursor-not-allowed disabled:border-[#d9cfdc] disabled:bg-[#f7f3f8] disabled:text-[#9a8a9d]">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" /><path d="M5 15V5a2 2 0 0 1 2-2h8" /></svg>
                                        <span x-text="copied ? 'Copied' : @js($voucher->code)">{{ $voucher->code }}</span>
                                    </button>
                                    @if ($voucher->seller)
                                        <a href="{{ route('buyer.sellers.show', $voucher->seller) }}" class="ml-auto inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3 text-[12px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">{{ $usable ? 'Shop now' : 'View shop' }}</a>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">{{ $vouchers->links() }}</div>
            @endif
        </section>

        <p class="mt-4 rounded-md bg-[#f6f2f7] px-3.5 py-3 text-[12px] leading-5 text-[#5b4a60]">Codes are typed or selected at checkout, per shop. Vouchers cannot be stacked, and shipping is quoted separately.</p>
    </div>
</x-buyer.layout>
