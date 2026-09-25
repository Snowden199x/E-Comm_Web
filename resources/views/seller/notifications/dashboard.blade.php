@forelse ($notifications as $notification)
    <li>
        <div>
            <p class="sd-notif__title"><a href="{{ route('seller.notifications.index') }}">{{ $notification->title }}</a></p>
            <p class="sd-notif__desc">{{ $notification->message }}</p>
            <p class="sd-notif__date">{{ $notification->created_at->format('M j, Y') }}</p>
        </div>
    </li>
@empty
    <li>No notifications yet.</li>
@endforelse
