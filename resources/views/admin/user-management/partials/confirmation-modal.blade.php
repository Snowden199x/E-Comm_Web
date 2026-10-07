{{-- Shown once after suspend / deactivate / activate (session 'confirmation'). Auto-closes after 5 seconds. --}}
@if (session('confirmation'))
    @php
        $type = session('confirmation');
        $titles = [
            'suspended' => 'Account suspended',
            'deactivated' => 'Account deactivated',
            'activated' => 'Account activated',
            'suspension_lifted' => 'Suspension lifted',
        ];
        $messages = [
            'suspended' => "The user's account has been temporarily suspended.",
            'deactivated' => 'The user can no longer access their Vendo account.',
            'activated' => 'The user can now access their Vendo account.',
            'suspension_lifted' => 'The user can now access their Vendo account.',
        ];
    @endphp

    <div x-data="{ show: false, run: false }"
        x-init="$nextTick(() => { show = true; setTimeout(() => run = true, 60); }); setTimeout(() => show = false, 5000)"
        x-show="show" x-cloak role="dialog" aria-modal="true" aria-labelledby="um-result-title"
        @click.self="show = false" @keydown.escape.window="show = false"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-[#2B1730]/50 p-4 backdrop-blur-[2px]">

        <div x-show="show" @click.stop
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm overflow-hidden rounded-2xl bg-white text-center shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)]">

            <div class="px-6 pb-6 pt-8">
                <img src="{{ asset('assets/icons/user-management/pop-up-icon.svg') }}" alt="" class="mx-auto mb-4 h-16 w-16">
                <h3 id="um-result-title" class="text-lg font-semibold text-[#2B1730]">{{ $titles[$type] ?? 'Done' }}</h3>
                <p class="mt-1.5 text-sm text-gray-500">{{ $messages[$type] ?? 'The account was updated.' }}</p>
                <button type="button" @click="show = false"
                    class="mt-6 h-11 w-full rounded-xl bg-[#3b1735] text-sm font-semibold text-white transition duration-200 hover:bg-[#4d1f45] active:scale-[0.98]">
                    Done
                </button>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-0.5 bg-[#ece4ec]" aria-hidden="true">
                <div class="h-full origin-left bg-[#7d5580] transition-transform duration-[5000ms] ease-linear"
                    :class="run ? 'scale-x-0' : 'scale-x-100'"></div>
            </div>
        </div>
    </div>
@endif