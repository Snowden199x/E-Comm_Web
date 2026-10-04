{{--
    Approve-registration confirmation.
    Approval sends the applicant an email, so it asks once before doing it.
    Contract (same as reject-modal): $user, $showExpr, $closeExpr.
--}}
@php
    $show = $showExpr ?? 'approveOpen';
    $close = $closeExpr ?? 'approveOpen = false';
    $titleId = 'approve-title-' . $user->id;
    $roleLabel = strtolower(str_replace('_', ' ', $user->role));
@endphp

<div x-show="{{ $show }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="{{ $close }}" @keydown.escape.window="{{ $show }} && ({{ $close }})"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

    <div x-show="{{ $show }}" @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

        <form method="POST" action="{{ route('admin.registrations.approve', $user) }}"
            x-data="{ busy: false }" @submit="busy = true"
            x-effect="if (!({{ $show }})) busy = false">
            @csrf

            <div class="px-6 pb-5 pt-7 text-center">
                <img src="{{ asset('assets/icons/registration/registration-approved-icon.svg') }}" alt="" class="mx-auto mb-4 h-14 w-14">
                <h3 id="{{ $titleId }}" class="text-lg font-semibold text-[#2B1730]">Approve this registration?</h3>
                <p class="mt-1.5 text-sm text-gray-500">
                    {{ $user->name }} will be able to sign in as a {{ $roleLabel }}. We'll email {{ $user->email }} to let them know.
                </p>
            </div>

            <div class="flex gap-3 border-t border-[#ece4ec] bg-[#FBF8FB] px-6 py-4">
                <button type="button" x-ref="cancel" @click="{{ $close }}"
                    class="h-11 flex-1 rounded-xl border border-[#d9ccdc] bg-white text-sm font-semibold text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98]">
                    Cancel
                </button>
                <button type="submit" :disabled="busy"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#3b1735] text-sm font-semibold text-white transition duration-200
                           hover:bg-[#4d1f45] active:scale-[0.98] disabled:cursor-wait disabled:opacity-70">
                    <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    <span x-text="busy ? 'Approving…' : 'Approve registration'">Approve registration</span>
                </button>
            </div>
        </form>
    </div>
</div>