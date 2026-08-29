@if (session('confirmation'))
    @php
        $type = session('confirmation');
        $titles = [
            'suspended' => 'Account Suspended',
            'deactivated' => 'Account Deactivated',
            'activated' => 'Account Activated',
            'suspension_lifted' => 'Suspension Lifted',
        ];
        $messages = [
            'suspended' => "The user's account has been temporarily suspended",
            'deactivated' => 'The user can no longer access their Vendo account.',
            'activated' => 'The user can now access their Vendo account',
            'suspension_lifted' => 'The user can now access their Vendo account',
        ];
    @endphp
    <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 4000)"
         class="fixed inset-0 bg-black/40 z-[70] flex items-center justify-center p-4"
         @click.self="show = false">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm text-center">
            <img src="{{ asset('assets/icons/user-management/pop-up-icon.svg') }}" alt="" class="w-16 h-16 mx-auto mb-4">
            <h3 class="font-bold text-lg text-gray-900 mb-2">{{ $titles[$type] }}</h3>
            <p class="text-sm text-gray-500">{{ $messages[$type] }}</p>

            <button type="button" @click="show = false" class="mt-5 px-6 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:bg-[#4d1f45]">
                Close
            </button>
        </div>
    </div>
@endif