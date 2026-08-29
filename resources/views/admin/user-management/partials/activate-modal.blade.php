<div x-show="activateId === {{ $user->id }}" x-cloak
     class="fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4"
     @click.self="activateId = null">
    <div class="bg-white rounded-2xl w-full max-w-md" @click.stop>

        <form method="POST" action="{{ route('user-management.activate', $user) }}" class="p-6">
            @csrf

            <div class="flex justify-end">
                <button type="button" @click="activateId = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <div class="text-center mb-6">
                <img src="{{ asset('assets/icons/user-management/warning-icon.svg') }}" alt="" class="w-14 h-14 mx-auto mb-3">
                <h3 class="font-bold text-lg text-gray-900">Activate Account?</h3>
                <p class="text-sm text-gray-500">This account will be restored and the user will be able to access Vendo again</p>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="activateId = null"
                        class="flex-1 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-lg border border-green-300 text-green-700 text-sm font-medium hover:bg-green-50">
                    Activate
                </button>
            </div>
        </form>

    </div>
</div>