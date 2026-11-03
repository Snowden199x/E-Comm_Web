{{--
    Activate dialog. Needs $user and parent scope { activateId }.
    Serves two cases with the same endpoint: lifting a suspension early, or reactivating a deactivated account.
--}}
@php
    $titleId = 'activate-title-' . $user->id;
    $isSuspended = $user->account_status === 'suspended';
@endphp

<div x-show="activateId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="activateId = null" @keydown.escape.window="if (activateId === {{ $user->id }}) activateId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-vd-ink/50 p-4">

    <div @click.stop class="w-full max-w-md overflow-hidden rounded-2xl border border-vd-line bg-white">
        <form method="POST" action="{{ route('admin.user-management.activate', $user) }}"
            x-data="{ busy: false }" @submit="busy = true"
            x-effect="if (activateId !== {{ $user->id }}) busy = false">
            @csrf

            <div class="flex items-start gap-3 p-5 pb-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-vd-success-line bg-vd-success-bg text-vd-success-ink">
                    <x-admin.icon name="check-circle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 id="{{ $titleId }}" class="text-base font-semibold text-vd-ink">{{ $isSuspended ? 'Lift suspension' : 'Activate account' }}</h3>
                    <p class="mt-0.5 text-sm text-vd-muted [overflow-wrap:anywhere]">{{ $isSuspended ? 'The suspension ends now. ' : '' }}{{ $user->name }} can sign in again.</p>
                </div>
                <button type="button" @click="activateId = null" aria-label="Close"
                    class="-mr-1.5 -mt-1.5 flex h-8 w-8 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum">
                    <x-admin.icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="flex gap-3 border-t border-vd-line bg-vd-tint p-4">
                <button type="button" @click="activateId = null"
                    class="h-10 flex-1 rounded-xl border border-vd-line-strong bg-white text-sm font-semibold text-vd-ink transition-colors duration-200 hover:bg-vd-paper">
                    {{ $isSuspended ? 'Keep suspended' : 'Keep deactivated' }}
                </button>
                <button type="submit" :disabled="busy"
                    class="h-10 flex-1 rounded-xl border text-sm font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-60 border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink">
                    <span x-text="busy ? 'Working…' : @js($isSuspended ? 'Lift suspension' : 'Activate account')">{{ $isSuspended ? 'Lift suspension' : 'Activate account' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
