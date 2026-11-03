{{-- Direct conversation: header + chat. Loaded into the page's conversation pane. Needs $conversation (with user). --}}
@php $roleLabel = ['seller' => 'Seller', 'buyer' => 'Buyer', 'logistics_center' => 'Courier'][$conversation->user->role] ?? ucfirst($conversation->user->role); @endphp

<div class="flex flex-none items-center gap-3 border-b border-vd-line p-4">
    <button type="button" @click="back()" aria-label="Back to messages"
        class="-ml-1 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-vd-plum transition-colors duration-200 hover:bg-vd-tint lg:hidden">
        <x-admin.icon name="chevron-left" class="h-5 w-5" />
    </button>
    <x-admin.avatar :user="$conversation->user" size="h-10 w-10" text="text-sm" />
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-vd-ink">{{ $conversation->user->name }}</p>
        <p class="truncate text-xs text-vd-muted">{{ $roleLabel }} · ADM-{{ $conversation->created_at?->format('Y') ?? now()->format('Y') }}-{{ str_pad((string) $conversation->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>
    <button type="button"
        data-delete-conversation="{{ route('admin.messages.conversation.delete', $conversation) }}"
        data-delete-redirect="{{ route('admin.messages.index') }}"
        class="inline-flex h-9 flex-shrink-0 items-center rounded-lg border border-vd-danger-line bg-vd-danger-bg px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-danger-ink/40">
        Delete conversation
    </button>
</div>

@include('admin.messages.partials.chat-pane', [
    'fetchUrl' => route('admin.messages.fetch', $conversation),
    'sendUrl' => route('admin.messages.send', $conversation),
    'attachments' => true,
    'peer' => $conversation->user->name,
])
