{{-- Header dropdown list. Rendered on first load and refreshed every few seconds from NotificationController@recent. --}}
@forelse ($notifications as $notification)
    <form method="POST" action="{{ route('buyer.notifications.open', $notification) }}" class="border-b border-[#f1e8f2] last:border-0 {{ ! $notification->read_at ? 'bg-[#fbf6fc]' : '' }}">
        @csrf
        <button type="submit" class="flex w-full items-start gap-2.5 px-4 py-3 text-left transition-colors duration-150 hover:bg-[#f5ecf6]">
            <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full {{ ! $notification->read_at ? 'bg-[#805487]' : 'bg-transparent' }}" aria-hidden="true"></span>
            <span class="min-w-0 flex-1">
                <strong class="block truncate text-[12px] {{ ! $notification->read_at ? 'font-semibold text-[#2b1730]' : 'font-medium text-[#3d2a42]' }}">{{ $notification->title }}</strong>
                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-4 text-[#5b4a60]">{{ $notification->message }}</span>
                <small class="mt-1 block text-[11px] text-[#8a7a8e]">{{ $notification->timeAgo() }}</small>
            </span>
        </button>
    </form>
@empty
    <div class="px-4 py-8 text-center">
        <p class="text-[13px] font-medium text-[#2b1730]">No notifications yet</p>
        <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Order updates and messages will show up here.</p>
    </div>
@endforelse