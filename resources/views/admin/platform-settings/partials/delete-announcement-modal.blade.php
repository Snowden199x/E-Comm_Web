<div x-show="deleteAnnouncementId === {{ $a->id }}" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="deleteAnnouncementId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm text-center relative" @click.stop>
        <div class="w-14 h-14 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
            <span class="text-red-600 text-2xl">!</span>
        </div>
        <h3 class="font-bold text-lg text-gray-900 mb-1">Delete "{{ $a->title }}"?</h3>
        <p class="text-sm text-gray-500 mb-5">This action cannot be undone.</p>

        <form method="POST" action="{{ route('platform-settings.announcements.destroy', $a) }}" class="flex gap-3">
            @csrf
            @method('DELETE')
            <button type="button" @click="deleteAnnouncementId = null"
                class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Cancel</button>
            <button type="submit"
                class="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Delete</button>
        </form>
    </div>
</div>