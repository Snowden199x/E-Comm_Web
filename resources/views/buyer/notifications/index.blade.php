<x-buyer.layout title="Notifications | Vendo">
    <div class="mx-auto max-w-4xl p-4 sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-bold text-gray-900">Notifications</h1><p class="text-sm text-gray-500">Updates about your orders, messages, and reviews.</p></div>
            @if($unreadCount)<form method="POST" action="{{ route('buyer.notifications.read-all') }}">@csrf<button class="rounded-lg border border-[#5b2963] px-4 py-2 text-sm font-medium text-[#5b2963]" type="submit">Mark all as read</button></form>@endif
        </div>
        <nav class="mb-4 flex gap-3 text-sm" aria-label="Notification filter"><a href="{{ route('buyer.notifications.index', ['filter' => 'all']) }}" class="rounded-lg px-3 py-2 @if($filter === 'all') bg-[#5b2963] text-white @else bg-white text-[#5b2963] @endif">All</a><a href="{{ route('buyer.notifications.index', ['filter' => 'unread']) }}" class="rounded-lg px-3 py-2 @if($filter === 'unread') bg-[#5b2963] text-white @else bg-white text-[#5b2963] @endif">Unread @if($unreadCount)({{ $unreadCount }})@endif</a></nav>
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            @forelse($notifications as $notification)
                <article class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 p-4 last:border-0 @if(!$notification->read_at) bg-[#fbf5fb] @endif"><div class="min-w-0 flex-1"><h2 class="text-sm font-semibold text-gray-900">{{ $notification->title }}</h2><p class="mt-1 text-sm text-gray-600">{{ $notification->message }}</p><time class="mt-2 block text-xs text-gray-500" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y g:i A') }}</time></div><div class="flex gap-2"><form method="POST" action="{{ route('buyer.notifications.open', $notification) }}">@csrf<button class="rounded-lg bg-[#5b2963] px-3 py-2 text-xs font-semibold text-white" type="submit">View</button></form>@if(!$notification->read_at)<form method="POST" action="{{ route('buyer.notifications.read', $notification) }}">@csrf<button class="rounded-lg border px-3 py-2 text-xs text-gray-700" type="submit">Mark read</button></form>@endif</div></article>
            @empty<p class="p-6 text-sm text-gray-500">{{ $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.' }}</p>@endforelse
        </div>
        <div class="mt-5">{{ $notifications->links() }}</div>
    </div>
@include('shared.notification-detail', ['notificationSide' => 'buyer'])
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'notifications'), 'mode' => 'reload'])
</x-buyer.layout>
