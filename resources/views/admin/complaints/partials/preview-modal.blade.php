{{--
    Complaint Preview popup (centred, as in the mockup). Opens when a row is clicked.
    Needs: $complaint. Optional: $sample (true for the preview-only sample case).
    Open state is drawerId in the casesPage scope (resources/js/admin/complaints.js).
--}}
@php
    $sample = $sample ?? false;
    $key = $sample ? "'sample'" : (string) $complaint->id;
    $domId = $sample ? 'sample' : $complaint->id;
    $roleLabel = fn ($role) => $role === 'logistics_center' ? 'Logistics' : ucfirst((string) $role);
    $caseNo = 'CMP-' . $complaint->created_at->format('Y') . '-' . str_pad($complaint->id, 5, '0', STR_PAD_LEFT);
    $isReport = $complaint->kind === 'user_report';
@endphp

<div x-show="drawerId === {!! $key !!}" x-cloak
    x-effect="if (drawerId === {!! $key !!}) $nextTick(() => document.getElementById('cs-preview-close-{{ $domId }}')?.focus())"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="drawerId = null"
    class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-[#2B1730]/50 p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="cs-preview-title-{{ $domId }}">

    <div @click.stop x-show="drawerId === {!! $key !!}"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="my-auto w-full max-w-3xl rounded-t-3xl border border-[#cdbbd2] bg-white shadow-2xl sm:rounded-3xl">

        <header class="flex items-center justify-between gap-3 border-b border-[#ece4ec] px-5 py-4 sm:px-7">
            <h3 id="cs-preview-title-{{ $domId }}" class="font-display text-lg font-semibold text-[#2B1730]">
                Complaint Preview
                @if ($sample)
                    <span class="ml-2 rounded-full bg-[#F1E9F1] px-2.5 py-0.5 align-middle text-xs font-medium text-[#5b2963]">Sample, not saved</span>
                @endif
            </h3>
            <button type="button" id="cs-preview-close-{{ $domId }}" @click="drawerId = null" aria-label="Close preview"
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-[#2B1730] transition-colors duration-150 hover:bg-[#F1E9F1]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="x" class="h-5 w-5" />
            </button>
        </header>

        <div class="px-5 pb-2 pt-5 sm:px-7">
            {{-- ID, filed date, status --}}
            <div class="mb-5 flex items-center gap-4">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-[#C98585] text-[#7A1414] sm:h-16 sm:w-16">
                    <x-admin.icon :name="$isReport ? 'flag' : 'alert-circle'" class="h-7 w-7 sm:h-8 sm:w-8" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-display text-xl font-semibold leading-tight text-[#9B111E] sm:text-2xl">{{ $caseNo }}</p>
                    <p class="mt-1 text-xs text-gray-500">Filed on {{ $complaint->created_at->format('M j, Y · g:i A') }}</p>
                </div>
                <div class="flex-shrink-0">@include('admin.complaints.partials.status-badge')</div>
            </div>

            <div class="grid grid-cols-1 border-t border-[#ece4ec] sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                {{-- Left: type, order, amount --}}
                <dl class="space-y-4 py-5 text-sm sm:border-r sm:border-[#ece4ec] sm:pr-6">
                    <div>
                        <dt class="mb-1.5 text-xs text-gray-500">Complaint Type</dt>
                        <dd>@include('admin.complaints.partials.type-badge')</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs text-gray-500">Order ID</dt>
                        <dd class="font-medium text-[#2B1730]">{{ $complaint->order ? 'ORD-' . str_pad($complaint->order->id, 4, '0', STR_PAD_LEFT) : 'Not linked to an order' }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs text-gray-500">Amount</dt>
                        <dd class="font-medium tabular-nums text-[#2B1730]">{{ $complaint->order ? '₱' . number_format($complaint->order->total_amount ?? 0, 2) : '—' }}</dd>
                    </div>
                </dl>

                {{-- Right: description + parties --}}
                <div class="space-y-4 border-t border-[#ece4ec] py-5 text-sm sm:border-t-0 sm:pl-6">
                    <div>
                        <p class="mb-1 text-xs text-gray-500">Description</p>
                        @if (filled($complaint->description))
                            <p class="max-h-40 overflow-y-auto whitespace-pre-wrap font-medium leading-relaxed text-[#2B1730] [overflow-wrap:anywhere]">{{ $complaint->description }}</p>
                        @else
                            <p class="rounded-xl border border-dashed border-[#e2d6e5] p-3 text-gray-500">No description was provided.</p>
                        @endif
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-gray-500">Parties Involved</p>
                        <div class="space-y-2">
                            @foreach ([$complaint->complainant, $complaint->respondent] as $person)
                                <div class="flex items-center gap-3 rounded-xl border border-[#ece4ec] px-3 py-2.5">
                                    <x-admin.avatar :user="$person" size="h-8 w-8" text="text-xs" />
                                    <div class="min-w-0 leading-tight">
                                        <p class="text-[13px] font-semibold text-[#2B1730]">{{ $roleLabel($person->role ?? '') }}</p>
                                        <p class="text-xs text-gray-600 [overflow-wrap:anywhere]">{{ $person->name ?? 'Deleted account' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="flex flex-wrap items-center justify-end gap-2.5 border-t border-[#ece4ec] px-5 py-4 sm:px-7">
            @if ($sample)
                <p class="mr-auto text-xs text-gray-500">Preview only. Real cases open the full case page here.</p>
            @else
                @if (! $isReport && $complaint->status === 'open')
                    <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}">
                        @csrf
                        <input type="hidden" name="status" value="in_review">
                        <button type="submit"
                            class="h-10 rounded-xl border border-[#ddd0e0] px-4 text-sm font-medium text-[#3b1735] transition-colors duration-150 hover:bg-[#F7F1F7]
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                            Mark in progress
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.complaints.show', $complaint) }}"
                    class="inline-flex h-10 items-center rounded-lg bg-[#3b1735] px-5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-[#4d1f45]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    See Full Details
                </a>
            @endif
        </footer>
    </div>
</div>