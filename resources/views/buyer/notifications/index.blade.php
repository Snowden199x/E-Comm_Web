{{--
    Buyer notifications.
    Uses what NotificationController@index already passes ($notifications paginator, $unreadCount, $filter,
    $selectedNotification, $policy). Routes and forms are unchanged: notifications.open, .read, .read-all.
    The icon for each row is chosen from the notification type, with a general bell as the fallback.
--}}
@php
    use Illuminate\Support\Str;

    $kind = function ($notification) {
        $type = Str::lower((string) $notification->type);
        return match (true) {
            Str::contains($type, ['warning', 'violation', 'suspend']) => 'warning',
            Str::contains($type, ['announcement', 'policy']) => 'announcement',
            Str::contains($type, ['review', 'rating']) => 'review',
            Str::contains($type, ['message', 'chat']) => 'message',
            Str::contains($type, ['order', 'shipment', 'delivery', 'cancel']) => 'order',
            default => 'general',
        };
    };
    $tone = [
        'order' => 'bg-[#f5ecf6] text-[#52245b]',
        'message' => 'bg-[#e8f1fb] text-[#1f5a99]',
        'review' => 'bg-[#fbf3dc] text-[#7a5a0c]',
        'announcement' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'warning' => 'bg-[#fdf1f3] text-[#a32b43]',
        'general' => 'bg-[#f3eef4] text-[#6d5d71]',
    ];

    // Group the current page by day
    $groups = $notifications->getCollection()->groupBy(function ($n) {
        if ($n->created_at->isToday()) return 'Today';
        if ($n->created_at->isYesterday()) return 'Yesterday';
        if ($n->created_at->isCurrentWeek()) return 'This week';
        return 'Earlier';
    });
    $tabClass = fn ($active) => $active ? 'bg-[#402143] text-white' : 'text-[#5b4a60] hover:bg-[#f5ecf6]';
@endphp

<x-buyer.layout title="Notifications | Vendo">
    <div class="vb-enter mx-auto max-w-[860px] px-3 pb-14 pt-4 sm:px-4">

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">Notifications</h1>
                <p class="mt-0.5 text-[13px] text-[#7a6a7e]">Updates about your orders, messages, and reviews.</p>
            </div>
            @if ($unreadCount)
                <form method="POST" action="{{ route('buyer.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-9 items-center rounded-md border border-[#805487] px-4 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Mark all as read</button>
                </form>
            @endif
        </div>

        <nav class="mt-4 inline-flex gap-1 rounded-lg border border-[#eee6ef] bg-white p-1" aria-label="Notification filter">
            <a href="{{ route('buyer.notifications.index', ['filter' => 'all']) }}" @if ($filter === 'all') aria-current="page" @endif
                class="flex h-9 items-center rounded-md px-4 text-[13px] font-medium transition-colors duration-200 {{ $tabClass($filter === 'all') }}">All</a>
            <a href="{{ route('buyer.notifications.index', ['filter' => 'unread']) }}" @if ($filter === 'unread') aria-current="page" @endif
                class="flex h-9 items-center gap-1.5 rounded-md px-4 text-[13px] font-medium transition-colors duration-200 {{ $tabClass($filter === 'unread') }}">
                Unread
                @if ($unreadCount)<span class="rounded-full px-1.5 text-[11px] leading-[18px] {{ $filter === 'unread' ? 'bg-white/20' : 'bg-[#e8c874] text-[#402143]' }}">{{ $unreadCount }}</span>@endif
            </a>
        </nav>

        @if ($notifications->isEmpty())
            <div class="mt-3 rounded-lg border border-[#eee6ef] bg-white">
                @if ($filter === 'unread')
                    <x-buyer.empty-state icon="chat" title="You are all caught up" text="There are no unread notifications.">
                        <a href="{{ route('buyer.notifications.index', ['filter' => 'all']) }}" class="inline-flex h-10 items-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">See all notifications</a>
                    </x-buyer.empty-state>
                @else
                    <x-buyer.empty-state icon="chat" title="No notifications yet" text="Order updates, seller messages and announcements will appear here.">
                        <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Start shopping</a>
                    </x-buyer.empty-state>
                @endif
            </div>
        @else
            @foreach ($groups as $day => $items)
                <section class="mt-4" aria-labelledby="group-{{ Str::slug($day) }}">
                    <h2 id="group-{{ Str::slug($day) }}" class="mb-2 px-1 text-[13px] font-semibold text-[#5b4a60]">{{ $day }}</h2>
                    <ul class="overflow-hidden rounded-lg border border-[#eee6ef] bg-white">
                        @foreach ($items as $notification)
                            @php $k = $kind($notification); $unread = ! $notification->read_at; @endphp
                            <li class="group relative flex items-start gap-3 border-b border-[#f1e8f2] px-4 py-3.5 last:border-0 sm:px-5 {{ $unread ? 'bg-[#fbf6fc]' : 'bg-white' }}">
                                @if ($unread)<span class="absolute left-1.5 top-[26px] h-2 w-2 rounded-full bg-[#805487] sm:left-2" aria-hidden="true"></span>@endif

                                <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-full {{ $tone[$k] }}" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        @switch($k)
                                            @case('order') <path d="M3 8l9-5 9 5v8l-9 5-9-5z" /><path d="M3 8l9 5 9-5M12 13v8" /> @break
                                            @case('message') <path d="M4 5h16v11H9l-5 4z" /> @break
                                            @case('review') <path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" /> @break
                                            @case('announcement') <path d="M4 10v4h3l7 4V6L7 10z" /><path d="M17.5 9a4 4 0 0 1 0 6" /> @break
                                            @case('warning') <path d="M12 4 3 20h18z" /><path d="M12 10v4M12 17.5h.01" /> @break
                                            @default <path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z" /><path d="M10 20a2 2 0 0 0 4 0" />
                                        @endswitch
                                    </svg>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                        <h3 class="text-[13px] {{ $unread ? 'font-semibold text-[#2b1730]' : 'font-medium text-[#3d2a42]' }}">{{ $notification->title }}</h3>
                                        <time class="text-[11px] text-[#8a7a8e]" datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('M j, Y g:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-[13px] leading-5 text-[#5b4a60]">{{ $notification->message }}</p>

                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <form method="POST" action="{{ route('buyer.notifications.open', $notification) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-8 items-center rounded-md bg-[#402143] px-3.5 text-[12px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">View</button>
                                        </form>
                                        @if ($unread)
                                            <form method="POST" action="{{ route('buyer.notifications.read', $notification) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 items-center rounded-md border border-[#e5dce7] px-3 text-[12px] font-medium text-[#5b4a60] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Mark as read</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            @if ($notifications->hasPages())
                <div class="mt-5">{{ $notifications->links() }}</div>
            @endif
        @endif
    </div>
    @include('shared.notification-detail', ['notificationSide' => 'buyer'])
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'notifications'), 'mode' => 'reload'])
</x-buyer.layout>