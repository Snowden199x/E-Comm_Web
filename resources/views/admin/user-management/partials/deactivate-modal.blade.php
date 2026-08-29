<div x-show="deactivateId === {{ $user->id }}" x-cloak
     class="fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4"
     @click.self="deactivateId = null">
    <div class="bg-white rounded-2xl w-full max-w-md" @click.stop>

        <form method="POST" action="{{ route('user-management.deactivate', $user) }}" class="p-6">
            @csrf

            <div class="flex justify-end">
                <button type="button" @click="deactivateId = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <div class="text-center mb-6">
                <img src="{{ asset('assets/icons/user-management/warning-icon.svg') }}" alt="" class="w-14 h-14 mx-auto mb-3">
                <h3 class="font-bold text-lg text-gray-900">Deactivate Account?</h3>
                <p class="text-sm text-gray-500">This will disable the user's account and prevent them from accessing Vendo. The account will remain in the system but will no longer be active.</p>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="deactivateId = null"
                        class="flex-1 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">
                    Deactivate
                </button>
            </div>
        </form>

    </div>
</div>