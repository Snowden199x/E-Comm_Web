@forelse($notifications as $notification)
    <form method="POST" action="{{ route('admin.notifications.open', $notification) }}" class="border-b border-gray-100 last:border-0">
        @csrf
        <button type="submit" class="block w-full px-4 py-3 text-left hover:bg-[#f8f1f8]">
            <span class="flex items-center gap-2"><strong class="min-w-0 flex-1 truncate text-xs text-gray-900">{{ $notification->title }}</strong>@if(!$notification->read_at)<span class="h-2 w-2 rounded-full bg-[#7a4281]" aria-label="Unread"></span>@endif</span>
            <span class="mt-1 block text-xs leading-5 text-gray-600 [overflow-wrap:anywhere]">{{ $notification->message }}</span>
            <small class="mt-1 block text-[11px] text-gray-400">{{ $notification->timeAgo() }}</small>
        </button>
    </form>
@empty
    <p class="px-4 py-6 text-center text-xs text-gray-500">No notifications yet.</p>
@endforelse
