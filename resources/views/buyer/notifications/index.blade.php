{{--
    Buyer notifications.
    Uses what NotificationController@index already passes ($notifications paginator, $unreadCount, $filter,
    $selectedNotification, $policy). Routes and forms are unchanged: notifications.open, .read, .read-all.
    New in this version (view only): the whole message area opens the notification, a type filter for the page
    that is shown, entrance animation, and a link to the notification sound setting.
    The type filter only filters the notifications on the current page (the server filter stays All / Unread).
--}}
@php
    use Illuminate\Support\Str;

    $kindOf = function ($notification) {
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
    $kindLabels = [
        'order' => 'Orders', 'message' => 'Messages', 'review' => 'Reviews',
        'announcement' => 'Announcements', 'warning' => 'Alerts', 'general' => 'Other',
    ];

    $pageItems = $notifications->getCollection();
    $kindsOnPage = $pageItems->map($kindOf)->unique()->values();

    // Group the current page by day
    $groups = $pageItems->groupBy(function ($n) {
        if ($n->created_at->isToday()) return 'Today';
        if ($n->created_at->isYesterday()) return 'Yesterday';
        if ($n->created_at->isCurrentWeek()) return 'This week';
        return 'Earlier';
    });
    $tabClass = fn ($active) => $active ? 'bg-[#402143] text-white' : 'text-[#5b4a60] hover:bg-[#f5ecf6]';
@endphp

<x-buyer.layout title="Notifications | Vendo">
    <div class="mx-auto max-w-[860px] px-3 pb-14 pt-4 sm:px-4" x-data="{ kind: 'all' }">

        <!-- Header -->
        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="flex items-center gap-2.5 text-[20px] font-semibold text-[#2b1730]">
                    Notifications
                    @if ($unreadCount)
                        <span class="rounded-full bg-[#e8c874] px-2.5 text-[12px] font-semibold leading-6 text-[#402143]">{{ $unreadCount }} new</span>
                    @endif
                </h1>
                <p class="mt-0.5 text-[13px] text-[#7a6a7e]">Updates about your orders, seller messages, reviews and announcements.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('buyer.account.index', ['tab' => 'notifications']) }}" class="inline-flex h-9 items-center rounded-md px-3 text-[13px] font-medium text-[#805487] transition-colors duration-200 hover:bg-[#f5ecf6] hover:text-[#402143]">Sound settings</a>
                @if ($unreadCount)
                    <form method="POST" action="{{ route('buyer.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="inline-flex h-9 items-center rounded-md border border-[#805487] px-4 text-[13px] font-medium text-[#52245b] transition-all duration-300 ease-vendo hover:bg-[#805487] hover:text-white active:scale-[0.97]">Mark all as read</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Filters -->
        <div class="vb-reveal mt-4 flex flex-wrap items-center gap-x-4 gap-y-2" style="--i: 1">
            <nav class="inline-flex gap-1 rounded-lg border border-[#eee6ef] bg-white p-1" aria-label="Notification filter">
                <a href="{{ route('buyer.notifications.index', ['filter' => 'all']) }}" @if ($filter === 'all') aria-current="page" @endif
                    class="flex h-9 items-center rounded-md px-4 text-[13px] font-medium transition-colors duration-200 {{ $tabClass($filter === 'all') }}">All</a>
                <a href="{{ route('buyer.notifications.index', ['filter' => 'unread']) }}" @if ($filter === 'unread') aria-current="page" @endif
                    class="flex h-9 items-center gap-1.5 rounded-md px-4 text-[13px] font-medium transition-colors duration-200 {{ $tabClass($filter === 'unread') }}">
                    Unread
                    @if ($unreadCount)<span class="rounded-full px-1.5 text-[11px] leading-[18px] {{ $filter === 'unread' ? 'bg-white/20' : 'bg-[#e8c874] text-[#402143]' }}">{{ $unreadCount }}</span>@endif
                </a>
            </nav>

            @if ($kindsOnPage->count() > 1)
                <div class="vb-no-scrollbar flex gap-1.5 overflow-x-auto" role="group" aria-label="Filter this page by type">
                    <button type="button" @click="kind = 'all'" :aria-pressed="kind === 'all'"
                        :class="kind === 'all' ? 'border-[#402143] bg-[#402143] text-white' : 'border-[#e5dce7] bg-white text-[#5b4a60] hover:border-[#c9a9ce]'"
                        class="h-8 flex-shrink-0 rounded-full border px-3.5 text-[12px] font-medium transition-colors duration-200">All types</button>
                    @foreach ($kindsOnPage as $k)
                        <button type="button" @click="kind = '{{ $k }}'" :aria-pressed="kind === '{{ $k }}'"
                            :class="kind === '{{ $k }}' ? 'border-[#402143] bg-[#402143] text-white' : 'border-[#e5dce7] bg-white text-[#5b4a60] hover:border-[#c9a9ce]'"
                            class="h-8 flex-shrink-0 rounded-full border px-3.5 text-[12px] font-medium transition-colors duration-200">{{ $kindLabels[$k] }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="vb-reveal mt-3 rounded-lg border border-[#eee6ef] bg-white" style="--i: 2">
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
                @php $dayKinds = $items->map($kindOf)->unique()->values(); @endphp
                <section class="vb-reveal mt-5" style="--i: {{ min($loop->iteration + 1, 6) }}" aria-labelledby="group-{{ Str::slug($day) }}"
                    x-show="kind === 'all' || @js($dayKinds).includes(kind)">
                    <h2 id="group-{{ Str::slug($day) }}" class="mb-2 flex items-center gap-2 px-1 text-[12px] font-semibold uppercase tracking-[0.12em] text-[#8a7a8e]">
                        {{ $day }}
                        <span class="rounded-full bg-[#f3e8f5] px-1.5 text-[11px] font-medium normal-case leading-[18px] tracking-normal text-[#52245b]">{{ $items->count() }}</span>
                    </h2>
                    <ul class="overflow-hidden rounded-lg border border-[#eee6ef] bg-white">
                        @foreach ($items as $notification)
                            @php $k = $kindOf($notification); $unread = ! $notification->read_at; @endphp
                            <li x-show="kind === 'all' || kind === '{{ $k }}'"
                                class="group relative flex items-stretch border-b border-[#f1e8f2] transition-colors duration-200 last:border-0 hover:bg-[#faf5fa] {{ $unread ? 'bg-[#fbf6fc]' : 'bg-white' }}">
                                @if ($unread)<span class="absolute left-0 top-0 h-full w-[3px] bg-[#805487]" aria-hidden="true"></span>@endif

                                {{-- The whole message area opens the notification (same form as the old View button) --}}
                                <form method="POST" action="{{ route('buyer.notifications.open', $notification) }}" class="min-w-0 flex-1">
                                    @csrf
                                    <button type="submit" class="flex w-full items-start gap-3.5 px-4 py-3.5 text-left sm:px-5">
                                        @include('buyer.notifications.partials.icon', ['notification' => $notification])
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                                                <span class="text-[13px] {{ $unread ? 'font-semibold text-[#2b1730]' : 'font-medium text-[#3d2a42]' }}">{{ $notification->title }}</span>
                                                <time class="text-[11px] text-[#8a7a8e]" datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('M j, Y g:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
                                            </span>
                                            <span class="mt-0.5 line-clamp-2 block text-[13px] leading-5 text-[#5b4a60]">{{ $notification->message }}</span>
                                            <span class="mt-1.5 inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 group-hover:text-[#402143]">
                                                View details
                                                <svg class="h-3.5 w-3.5 transition-transform duration-300 ease-vendo group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                            </span>
                                        </span>
                                    </button>
                                </form>

                                @if ($unread)
                                    <form method="POST" action="{{ route('buyer.notifications.read', $notification) }}" class="flex flex-shrink-0 items-start px-3 py-3.5 sm:px-4">
                                        @csrf
                                        <button type="submit" title="Mark as read" aria-label="Mark {{ $notification->title }} as read"
                                            class="grid h-8 w-8 place-items-center rounded-full text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                                        </button>
                                    </form>
                                @endif
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