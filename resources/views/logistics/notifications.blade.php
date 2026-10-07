<x-logistics.layout title="Notifications">
    <div class="lg-page">
        <div class="lg-page-head">
            <div><h1>Notifications</h1><p>Pickup requests, Main Hub arrivals and rider applications for your center.</p></div>
            @if($unreadCount)
                <form method="POST" action="{{ route('logistics.notifications.read-all') }}">@csrf
                    <button type="submit" class="lg-btn lg-btn--outline">Mark all as read</button>
                </form>
            @endif
        </div>
        <section class="lg-card" aria-label="Notifications">
            @forelse($notifications as $notification)
                <div class="lg-row">
                    <div class="lg-row__main">
                        <div class="lg-person">
                            <span class="lg-avatar" aria-hidden="true">{{ $notification->read_at ? '✓' : '•' }}</span>
                            <span><strong>{{ $notification->title }}</strong><small>{{ $notification->message }}</small></span>
                        </div>
                        <div class="lg-row__meta">{{ $notification->created_at->format('M j, Y') }}<small>{{ $notification->created_at->format('g:i A') }}</small></div>
                        <form method="POST" action="{{ route('logistics.notifications.open', $notification) }}">@csrf
                            <button type="submit" class="lg-btn lg-btn--outline lg-btn--sm">Open</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="lg-empty"><h2>No notifications yet</h2><p>Updates for this Main Hub will appear here.</p></div>
            @endforelse
        </section>
        {{ $notifications->links('logistics.partials.pagination') }}
    </div>
</x-logistics.layout>
