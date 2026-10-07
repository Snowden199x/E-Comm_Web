<x-seller.layout title="Notifications">
    @vite('resources/css/seller/workspace.css')
    <section class="sw-page">
        <header class="sw-heading"><div><h1>Notifications</h1><p>Updates about your orders, products, reviews and support messages.</p></div></header>
        @if(session('success'))<p class="sw-notice" role="status">{{ session('success') }}</p>@endif
        <div class="sw-toolbar"><div class="sw-tabs"><a href="{{ route('seller.notifications.index', ['filter' => 'all']) }}" class="@if($filter === 'all') is-active @endif">All</a><a href="{{ route('seller.notifications.index', ['filter' => 'unread']) }}" class="@if($filter === 'unread') is-active @endif">Unread @if($unreadCount)<span>{{ $unreadCount }}</span>@endif</a></div>
            @if($unreadCount)<form method="POST" action="{{ route('seller.notifications.read-all') }}">@csrf<button class="sw-button sw-button--outline" type="submit">Mark all as read</button></form>@endif
        </div>
        <section class="sw-card sw-notifications">
            @forelse($notifications as $notification)
                <div class="sw-notification @if(!$notification->read_at) is-unread @endif">
                    <span class="sw-notification__dot" aria-hidden="true"></span>
                    <div><strong>{{ $notification->title }}</strong><p>{{ $notification->message }}</p><small>{{ $notification->created_at->format('M j, Y g:i A') }}</small></div>
                    <div class="sw-notification__actions"><form method="POST" action="{{ route('seller.notifications.open', $notification) }}">@csrf<button class="sw-button sw-button--small" type="submit">View</button></form>
                        @if(!$notification->read_at)<form method="POST" action="{{ route('seller.notifications.read', $notification) }}">@csrf<button class="sw-text-button" type="submit">Mark as read</button></form>@endif</div>
                </div>
            @empty<p class="sw-empty">{{ $filter === 'unread' ? 'You have no unread notifications.' : 'No notifications yet.' }}</p>@endforelse
            <div class="sw-pagination">{{ $notifications->links() }}</div>
        </section>
    </section>
@include('shared.notification-detail', ['notificationSide' => 'seller'])
@include('shared.live-revision', ['endpoint' => route('seller.live', 'notifications'), 'mode' => 'reload'])
</x-seller.layout>
