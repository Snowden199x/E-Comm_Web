@forelse($notifications as $notification)
    <form method="POST" action="{{ route('admin.notifications.open', $notification) }}" class="border-b border-vd-line last:border-0">
        @csrf
        <button type="submit" class="block w-full px-4 py-3 text-left transition-colors duration-200 hover:bg-vd-tint">
            <span class="flex items-center gap-2"><strong class="min-w-0 flex-1 truncate text-xs text-vd-ink">{{ $notification->title }}</strong>@if(!$notification->read_at)<span class="h-2 w-2 rounded-full bg-vd-plum" aria-label="Unread"></span>@endif</span>
            <span class="mt-1 block text-xs leading-5 text-vd-muted [overflow-wrap:anywhere]">{{ $notification->message }}</span>
            <small class="mt-1 block text-[11px] text-vd-muted">{{ $notification->timeAgo() }}</small>
        </button>
    </form>
@empty
    <x-admin.empty-state size="sm" icon="bell" title="No notifications" text="Registrations, complaints and messages appear here." />
@endforelse
