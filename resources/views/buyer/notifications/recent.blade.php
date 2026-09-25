@forelse($notifications as $notification)
    <form method="POST" action="{{ route('buyer.notifications.open', $notification) }}" class="border-b border-gray-100 last:border-0 @if(!$notification->read_at) bg-[#fbf5fb] @endif">@csrf
        <button type="submit" class="block w-full px-4 py-3 text-left hover:bg-[#f2eaf3]"><strong class="block text-xs text-gray-900">{{ $notification->title }}</strong><span class="mt-1 block text-xs text-gray-600">{{ $notification->message }}</span><small class="mt-1 block text-[10px] text-gray-500">{{ $notification->timeAgo() }}</small></button>
    </form>
@empty<p class="p-4 text-xs text-gray-500">No recent notifications.</p>@endforelse
