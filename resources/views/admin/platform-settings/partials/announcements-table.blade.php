<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Announcements</th>
                <th class="pb-3 font-medium">Audience</th>
                <th class="pb-3 font-medium">Date</th>
                <th class="pb-3 font-medium">Status</th>
                <th class="pb-3 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($announcements as $a)
                <tr class="border-b last:border-0">
                    <td class="py-3">
                        <p class="text-gray-900 font-medium">{{ $a->title }}</p>
                        <p class="text-xs text-gray-400">{{ Str::limit($a->message, 50) }}</p>
                    </td>
                    <td class="py-3 text-gray-600">{{ $a->audience }}</td>
                    <td class="py-3 text-gray-600">
                        {{ ($a->scheduled_at ?? $a->created_at)->format('M d, Y g:i A') }}
                    </td>
                    <td class="py-3">
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-700' => $a->status === 'published',
                            'bg-orange-100 text-orange-700' => $a->status === 'scheduled',
                            'bg-gray-100 text-gray-500' => $a->status === 'draft',
                        ])>
                            {{ ucfirst($a->status) }}
                        </span>
                    </td>
                    <td class="py-3">
                        <button type="button" @click="viewAnnouncementId = {{ $a->id }}"
                            class="px-4 py-1.5 rounded-full border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">View</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-400">No announcements yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($announcements->hasPages())
    <div class="flex items-center justify-between mt-4 pt-4 border-t">
        <p class="text-xs text-gray-500">Showing {{ $announcements->count() }} out of {{ $announcements->total() }}
            entries</p>
        <div>{{ $announcements->links() }}</div>
    </div>
@endif
