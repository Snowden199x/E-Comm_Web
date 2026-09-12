<div class="flex items-center gap-3 p-4 border-b border-gray-100">
    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-600">
        {{ strtoupper(substr($conversation->user->name, 0, 1)) }}
    </div>
    <div class="flex-1">
        <p class="font-medium text-gray-900">{{ $conversation->user->name }}</p>
        <p class="text-xs text-gray-400 capitalize">{{ $conversation->user->role }}</p>
    </div>
    @if ($conversation->complaint)
        <a href="{{ route('complaints.show', $conversation->complaint) }}"
            class="text-xs px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
            View Complaint
        </a>
    @endif
</div>

<div class="flex-1 overflow-y-auto p-4 space-y-3">
    @foreach ($conversation->messages as $message)
        @php $isAdmin = $message->sender_id === auth()->id(); @endphp
        <div class="flex {{ $isAdmin ? 'justify-end' : 'justify-start' }}">
            <div
                class="max-w-xs {{ $isAdmin ? 'bg-[#3b1735] text-white' : 'bg-gray-100 text-gray-900' }} rounded-2xl px-4 py-2">
                @if ($message->body)
                    <p class="text-sm">{{ $message->body }}</p>
                @endif
                @foreach ($message->attachments as $attachment)
                    @php
                        $ext = strtolower(pathinfo($attachment->path, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    @endphp
                    @if ($isImage)
                        <img src="{{ Storage::url($attachment->path) }}" alt="{{ $attachment->original_filename }}"
                            class="max-w-full max-h-48 rounded-lg cursor-pointer"
                            @click="$dispatch('open-lightbox', '{{ Storage::url($attachment->path) }}')">
                    @else
                        <a href="{{ Storage::url($attachment->path) }}" target="_blank"
                            class="text-xs underline block mt-1 {{ $isAdmin ? 'text-purple-200' : 'text-blue-600' }}">
                            📎 {{ $attachment->original_filename }}
                        </a>
                    @endif
                @endforeach
                <p class="text-xs mt-1 {{ $isAdmin ? 'text-purple-200' : 'text-gray-400' }}">
                    {{ $message->created_at->format('g:i A') }}</p>
            </div>
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('messages.send', $conversation) }}" enctype="multipart/form-data"
    class="p-4 border-t border-gray-100 flex items-center gap-2" x-data="{ sending: false }"
    @submit.prevent="
        sending = true;
        const form = $event.target;
        fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(r => r.text())
            .then(html => { document.getElementById('thread-wrap').innerHTML = html; sending = false; })
    ">
    @csrf
    <label class="cursor-pointer text-gray-400 hover:text-gray-600">
        <input type="file" name="attachment" class="hidden" @change="$event.target.form.requestSubmit()">
        📎
    </label>
    <input type="text" name="body" placeholder="Type a message..."
        class="flex-1 px-3 py-2 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
    <button type="submit" :disabled="sending"
        class="px-4 py-2 rounded-full bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">
        Send
    </button>
</form>
