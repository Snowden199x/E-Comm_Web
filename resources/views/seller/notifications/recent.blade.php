@forelse($notifications as $notification)
    <form method="POST" action="{{ route('seller.notifications.open', $notification) }}" class="sw-bell-item @if(!$notification->read_at) is-unread @endif">@csrf
        <button type="submit"><strong>{{ $notification->title }}</strong><span>{{ $notification->message }}</span><small>{{ $notification->timeAgo() }}</small></button>
    </form>
@empty<p class="sw-bell-empty">No recent notifications.</p>@endforelse
