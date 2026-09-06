<div x-show="editAnnouncementId === {{ $a->id }}" x-cloak
    x-data="{ status: '{{ $a->status }}' }"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="editAnnouncementId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Edit Announcement</h3>
            <button type="button" @click="editAnnouncementId = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('platform-settings.announcements.update', $a) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="text-sm font-medium text-gray-900 mb-1 block">Title<span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ $a->title }}" required
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-900 mb-1 block">Message<span class="text-red-500">*</span></label>
                <textarea name="message" rows="3" required
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">{{ $a->message }}</textarea>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-900 mb-1 block">Audience<span class="text-red-500">*</span></label>
                <select name="audience" required
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                    @foreach (['All Users', 'Buyers & Sellers', 'Buyers Only', 'Sellers Only', 'Couriers Only'] as $opt)
                        <option value="{{ $opt }}" {{ $a->audience === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-900 mb-1 block">Status<span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <label class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm cursor-pointer has-[:checked]:border-gray-500 has-[:checked]:bg-gray-50">
                        <input type="radio" name="status" value="draft" x-model="status" class="hidden"> Draft
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm cursor-pointer has-[:checked]:border-orange-400 has-[:checked]:bg-orange-50">
                        <input type="radio" name="status" value="scheduled" x-model="status" class="hidden"> Scheduled
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm cursor-pointer has-[:checked]:border-green-400 has-[:checked]:bg-green-50">
                        <input type="radio" name="status" value="published" x-model="status" class="hidden"> Published
                    </label>
                </div>
            </div>

            <div x-show="status === 'scheduled'" x-cloak>
                <label class="text-sm font-medium text-gray-900 mb-1 block">Schedule Date & Time</label>
                <input type="datetime-local" name="scheduled_at" value="{{ $a->scheduled_at?->format('Y-m-d\TH:i') }}"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="editAnnouncementId = null" class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Save Changes</button>
            </div>
        </form>
    </div>
</div>