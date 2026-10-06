{{-- Rendered on page load AND re-injected every 3 seconds by the layout's bell poll: keep it animation-free so it never flickers. --}}
@forelse ($notifications as $notification)
    <li class="db-notif">
        <span class="db-notif__icon @if(! $notification->read_at) is-unread @endif" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/></svg>
        </span>
        <div class="db-notif__text">
            <p class="db-notif__title"><a href="{{ route('seller.notifications.index') }}">{{ $notification->title }}</a></p>
            <p class="db-notif__desc">{{ $notification->message }}</p>
            <p class="db-notif__time">{{ $notification->timeAgo() }}</p>
        </div>
    </li>
@empty
    <li class="db-empty db-empty--compact">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/></svg>
        <strong>You're all caught up</strong>
        <span>New alerts about orders and products will show here.</span>
    </li>
@endforelse