{{--
    Reject-registration dialog.
    Contract (unchanged, also used by the Dashboard): $user, $showExpr, $closeExpr.
    Keeps its own form state so it works in any parent Alpine scope.
--}}
@php
    $show = $showExpr ?? 'rejectOpen';
    $close = $closeExpr ?? 'rejectOpen = false';
    $titleId = 'reject-title-' . $user->id;

    $reasons = [
        'Incomplete Application',
        'Invalid Identification',
        'Information Mismatch',
        'Document Verification Failed',
        'Fraudulent Information',
        'Does Not Meet Requirements',
        'Other (please specify)',
    ];
@endphp

<div x-show="{{ $show }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="{{ $close }}" @keydown.escape.window="{{ $show }} && ({{ $close }})"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

    <div x-show="{{ $show }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        <form method="POST" action="{{ route('admin.registrations.disapprove', $user) }}"
            x-data="{ reason: '', details: '', busy: false }" @submit="busy = true"
            x-effect="if (!({{ $show }})) { reason = ''; details = ''; busy = false }"
            class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="flex items-start gap-3 px-6 pb-4 pt-6">
                <img src="{{ asset('assets/icons/registration/rejection-icon.svg') }}" alt="" class="h-11 w-11 flex-shrink-0">
                <div class="min-w-0 flex-1">
                    <h3 id="{{ $titleId }}" class="text-lg font-semibold text-[#2B1730]">Reject registration</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Choose why {{ $user->name }}'s application can't move forward.</p>
                </div>
                <button type="button" @click="{{ $close }}" aria-label="Close"
                    class="-mr-1.5 -mt-1.5 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 pb-2 thin-scroll">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-[#2B1730]">Reason for rejection <span class="text-red-500" aria-hidden="true">*</span></legend>
                    <div class="space-y-2">
                        @foreach ($reasons as $option)
                            <label class="relative block cursor-pointer">
                                <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required class="peer absolute inset-0 opacity-0">
                                <span :class="reason === @js($option)
                                        ? 'border-[#3b1735] bg-[#F7F1F7] text-[#2B1730]'
                                        : 'border-[#e2d6e5] text-gray-700 hover:bg-[#FBF8FB]'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm transition duration-150
                                           peer-focus-visible:ring-2 peer-focus-visible:ring-[#3b1735]/40">
                                    <i :class="reason === @js($option) ? 'border-[#3b1735]' : 'border-[#cdbbd2]'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full border-2 transition duration-150">
                                        <b :class="reason === @js($option) ? 'scale-100' : 'scale-0'"
                                            class="h-2 w-2 rounded-full bg-[#3b1735] transition duration-200 ease-vendo"></b>
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-[#2B1730]" for="reject-details-{{ $user->id }}">
                    Additional details
                    <span class="font-normal text-gray-400" x-show="!reason.startsWith('Other')">(optional)</span>
                    <span class="text-red-500" x-show="reason.startsWith('Other')" x-cloak aria-hidden="true">*</span>
                </label>
                <textarea id="reject-details-{{ $user->id }}" name="additional_details" x-model="details" maxlength="500" rows="3"
                    :required="reason.startsWith('Other')"
                    placeholder="Add anything the applicant should know"
                    class="mt-2 w-full resize-none rounded-xl border border-[#ddd0e0] p-3 text-sm text-[#2B1730] placeholder:text-gray-400
                           transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-gray-400" x-text="details.length + '/500'">0/500</p>
            </div>

            <div class="flex gap-3 border-t border-[#ece4ec] bg-[#FBF8FB] px-6 py-4">
                <button type="button" @click="{{ $close }}"
                    class="h-11 flex-1 rounded-xl border border-[#d9ccdc] bg-white text-sm font-semibold text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98]">
                    Cancel
                </button>
                <button type="submit" :disabled="busy"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 text-sm font-semibold text-white transition duration-200
                           hover:bg-red-700 active:scale-[0.98] disabled:cursor-wait disabled:opacity-70">
                    <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    <span x-text="busy ? 'Rejecting…' : 'Reject application'">Reject application</span>
                </button>
            </div>
        </form>
    </div>
</div>