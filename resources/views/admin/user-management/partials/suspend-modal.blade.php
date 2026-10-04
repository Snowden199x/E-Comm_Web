{{-- Suspend dialog. Needs $user and parent scope { suspendId }. Posts reasons[] + additional_details. --}}
@php
    $titleId = 'suspend-title-' . $user->id;
    $reasons = ['Policy Violation', 'Suspicious Activity', 'Repeated Violations', 'Security Concern', 'Other'];
@endphp

<div x-show="suspendId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="suspendId = null" @keydown.escape.window="if (suspendId === {{ $user->id }}) suspendId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

    <div x-show="suspendId === {{ $user->id }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        <form method="POST" action="{{ route('admin.user-management.suspend', $user) }}"
            x-data="{ reasons: [], details: '', busy: false }" @submit="busy = true"
            x-effect="if (suspendId !== {{ $user->id }}) { reasons = []; details = ''; busy = false }"
            class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="px-6 pb-4 pt-7 text-center">
                <img src="{{ asset('assets/icons/user-management/warning-icon.svg') }}" alt="" class="mx-auto mb-3 h-14 w-14">
                <h3 id="{{ $titleId }}" class="text-lg font-semibold text-[#2B1730]">Suspend account</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $user->name }} won't be able to sign in until the suspension ends or you lift it.</p>
            </div>

            <div class="flex-1 overflow-y-auto px-6 pb-2 thin-scroll">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-[#2B1730]">Reason for suspension <span class="text-red-500" aria-hidden="true">*</span></legend>
                    <div class="space-y-2">
                        @foreach ($reasons as $option)
                            <label class="relative block cursor-pointer">
                                <input type="checkbox" name="reasons[]" value="{{ $option }}" x-model="reasons" class="peer absolute inset-0 opacity-0">
                                <span :class="reasons.includes(@js($option))
                                        ? 'border-[#3b1735] bg-[#F7F1F7] text-[#2B1730]'
                                        : 'border-[#e2d6e5] text-gray-700 hover:bg-[#FBF8FB]'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm transition duration-150
                                           peer-focus-visible:ring-2 peer-focus-visible:ring-[#3b1735]/40">
                                    <i :class="reasons.includes(@js($option)) ? 'border-[#3b1735]' : 'border-[#cdbbd2]'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full border-2 transition duration-150">
                                        <b :class="reasons.includes(@js($option)) ? 'scale-100' : 'scale-0'"
                                            class="h-2 w-2 rounded-full bg-[#3b1735] transition duration-200 ease-vendo"></b>
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-[#2B1730]" for="suspend-details-{{ $user->id }}">
                    Additional details
                    <span class="font-normal text-gray-400" x-show="!reasons.includes('Other')">(optional)</span>
                    <span class="text-red-500" x-show="reasons.includes('Other')" x-cloak aria-hidden="true">*</span>
                </label>
                <textarea id="suspend-details-{{ $user->id }}" name="additional_details" x-model="details" maxlength="500" rows="3"
                    :required="reasons.includes('Other')"
                    placeholder="Add anything that helps the next admin understand this decision"
                    class="mt-2 w-full resize-none rounded-xl border border-[#ddd0e0] p-3 text-sm text-[#2B1730] placeholder:text-gray-400
                           transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-gray-400" x-text="details.length + '/500'">0/500</p>
            </div>

            <div class="flex gap-3 border-t border-[#ece4ec] bg-[#FBF8FB] px-6 py-4">
                <button type="button" @click="suspendId = null"
                    class="h-11 flex-1 rounded-xl border border-[#d9ccdc] bg-white text-sm font-semibold text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98]">
                    Cancel
                </button>
                <button type="submit" :disabled="reasons.length === 0 || busy"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 text-sm font-semibold text-white transition duration-200
                           hover:bg-red-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    <span x-text="busy ? 'Suspending…' : 'Suspend account'">Suspend account</span>
                </button>
            </div>
        </form>
    </div>
</div>