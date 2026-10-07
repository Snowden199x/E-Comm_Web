{{-- Header dropdown list. Rendered on first load and refreshed every few seconds from NotificationController@recent. --}}
@forelse ($notifications as $notification)
    @php $unread = ! $notification->read_at; @endphp
    <form method="POST" action="{{ route('buyer.notifications.open', $notification) }}" class="border-b border-[#f1e8f2] last:border-0 {{ $unread ? 'bg-[#fbf6fc]' : '' }}">
        @csrf
        <button type="submit" class="group relative flex w-full items-start gap-3 px-4 py-3 text-left transition-colors duration-200 hover:bg-[#f5ecf6]">
            @if ($unread)<span class="absolute left-0 top-0 h-full w-[3px] bg-[#805487]" aria-hidden="true"></span>@endif
            @include('buyer.notifications.partials.icon', ['notification' => $notification, 'size' => 'h-9 w-9'])
            <span class="min-w-0 flex-1">
                <span class="flex items-baseline justify-between gap-2">
                    <strong class="block truncate text-[12px] {{ $unread ? 'font-semibold text-[#2b1730]' : 'font-medium text-[#3d2a42]' }}">{{ $notification->title }}</strong>
                    <small class="flex-shrink-0 text-[11px] text-[#8a7a8e]">{{ $notification->timeAgo() }}</small>
                </span>
                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-4 text-[#5b4a60]">{{ $notification->message }}</span>
            </span>
            @if ($unread)<span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-[#805487]" aria-label="Unread"></span>@endif
        </button>
    </form>
@empty
    <div class="px-4 py-10 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z" /><path d="M10 20a2 2 0 0 0 4 0" /></svg>
        </span>
        <p class="mt-3 text-[13px] font-medium text-[#2b1730]">You are all caught up</p>
        <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Order updates and messages will show up here.</p>
    </div>
@endforelse