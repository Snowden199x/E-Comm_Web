@forelse ($conversations as $conversation)
    <button type="button" @click="openConversation({{ $conversation->id }})"
        :class="activeId === {{ $conversation->id }} ? 'bg-purple-50' : 'hover:bg-gray-50'"
        class="w-full text-left p-4 border-b border-gray-100 flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-600 flex-shrink-0">
            {{ strtoupper(substr($conversation->user->name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $conversation->user->name }}</p>
                @if ($conversation->unread_count > 0)
                    <span class="bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">{{ $conversation->unread_count }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-400 capitalize">{{ $conversation->user->role }}</p>
            @if ($conversation->latestMessage)
                <p class="text-xs text-gray-500 truncate mt-1">{{ $conversation->latestMessage->body ?? '📎 Attachment' }}</p>
            @endif
        </div>
    </button>
@empty
    <p class="text-sm text-gray-400 text-center py-8">No conversations yet.</p>
@endforelse