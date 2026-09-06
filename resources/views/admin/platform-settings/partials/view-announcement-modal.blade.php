<div x-show="viewAnnouncementId === {{ $a->id }}" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="viewAnnouncementId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">{{ $a->title }}</h3>
            <button type="button" @click="viewAnnouncementId = null"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="space-y-3 text-sm">
            <div>
                <p class="text-gray-400 text-xs mb-1">Audience</p>
                <p class="font-medium text-gray-900">{{ $a->audience }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-1">Status</p>
                <span @class([
                    'px-2 py-1 rounded-full text-xs font-medium',
                    'bg-green-100 text-green-700' => $a->status === 'published',
                    'bg-orange-100 text-orange-700' => $a->status === 'scheduled',
                    'bg-gray-100 text-gray-500' => $a->status === 'draft',
                ])>
                    {{ ucfirst($a->status) }}
                </span>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-1">{{ $a->scheduled_at ? 'Scheduled for' : 'Created' }}</p>
                <p class="font-medium text-gray-900">{{ ($a->scheduled_at ?? $a->created_at)->format('M d, Y g:i A') }}
                </p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-1">Message</p>
                <p class="text-gray-800">{{ $a->message }}</p>
            </div>
        </div>
        <div class="flex gap-3 mt-5 pt-4 border-t border-gray-100">
            <button type="button" @click="viewAnnouncementId = null; editAnnouncementId = {{ $a->id }}"
                class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium hover:bg-gray-50">Edit</button>
            <button type="button" @click="viewAnnouncementId = null; deleteAnnouncementId = {{ $a->id }}"
                class="flex-1 px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">Delete</button>
        </div>
    </div>
</div>
