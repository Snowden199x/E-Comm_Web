<x-logistics.shell title="Notifications" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Notifications</h1><p>Updates addressed to your Logistics account.</p></div>
            @if ($notifications->contains(fn ($item) => ! $item->read_at))
                <form method="POST" action="{{ route('lgp.notifications.read-all') }}">@csrf<button type="submit" class="lg-btn lg-btn--outline">Mark all as read</button></form>
            @endif
        </div>
        <section class="lg-card" aria-label="Notifications">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('lgp.notifications.open', $notification) }}" class="border-b border-[#ece4ec] last:border-0">@csrf
                    <button type="submit" class="w-full px-5 py-4 text-left hover:bg-[#FBF7F2] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3b1735]">
                        <span class="block font-semibold">{{ $notification->title }} @if (! $notification->read_at)<span class="text-xs text-[#6C3372]">New</span>@endif</span>
                        <span class="mt-1 block text-sm text-gray-600">{{ $notification->message }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ $notification->created_at?->diffForHumans() }}</span>
                    </button>
                </form>
            @empty
                <x-logistics.empty icon="bell" title="No notifications" text="Pickup, trip, scan and handoff updates addressed to you will appear here." />
            @endforelse
        </section>
        {{ $notifications->links() }}
    </div>
</x-logistics.shell>
