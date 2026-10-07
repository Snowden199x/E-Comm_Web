<x-admin.layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-2xl font-bold text-gray-900">Notifications</h2><p class="text-sm text-gray-500">{{ $unreadCount }} unread</p></div>
            @if($unreadCount)<form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button type="submit" class="rounded-lg border border-[#714476] px-3 py-2 text-sm text-[#52245b]">Mark all as read</button></form>@endif
        </div>
        <nav class="mb-4 flex gap-3 text-sm" aria-label="Notification filters">
            <a class="@if($filter === 'all') font-bold text-[#52245b] @else text-gray-500 @endif" href="{{ route('admin.notifications.index', ['filter' => 'all']) }}">All</a>
            <a class="@if($filter === 'unread') font-bold text-[#52245b] @else text-gray-500 @endif" href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}">Unread</a>
        </nav>
        <div class="rounded-2xl bg-white p-5 shadow-sm space-y-3">
            @forelse($notifications as $notification)
                <article class="flex flex-wrap items-start gap-3 rounded-lg border p-3 @if(!$notification->read_at) border-[#d7bcda] bg-[#fcf8fc] @else border-gray-100 @endif">
                    <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}" alt="" class="mt-0.5 h-5 w-5">
                    <div class="min-w-0 flex-1"><p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p><p class="text-sm text-gray-500">{{ $notification->message }}</p><small class="text-xs text-gray-400">{{ $notification->timeAgo() }}</small></div>
                    <div class="flex gap-2 text-xs">
                        <form method="POST" action="{{ route('admin.notifications.open', $notification) }}">@csrf<button type="submit" class="rounded-lg bg-[#52245b] px-3 py-1.5 text-white">View</button></form>
                        @if(!$notification->read_at)<form method="POST" action="{{ route('admin.notifications.read', $notification) }}">@csrf<button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5">Mark as read</button></form>@endif
                    </div>
                </article>
            @empty<p class="py-8 text-center text-sm text-gray-400">{{ $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.' }}</p>@endforelse
        </div>
        @if($notifications->hasPages())<div class="mt-4">{{ $notifications->links() }}</div>@endif
    </div>
    @include('shared.notification-detail', ['notificationSide' => 'admin'])
@include('shared.live-revision', ['endpoint' => route('admin.live', 'notifications'), 'mode' => 'reload'])
</x-admin.layout>
