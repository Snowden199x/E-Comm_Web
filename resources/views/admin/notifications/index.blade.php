<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Notifications</h2>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm space-y-3">
            @forelse ($notifications as $notification)
                <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-100">
                    <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}" alt=""
                        class="w-5 h-5 mt-0.5">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                        <p class="text-sm text-gray-500">{{ $notification->message }}</p>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->timeAgo() }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-8">No notifications yet.</p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-admin-layout>