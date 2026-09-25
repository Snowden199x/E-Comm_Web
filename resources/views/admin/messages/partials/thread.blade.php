<div class="flex items-center gap-3 p-4 border-b border-gray-100">
    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-600">
        {{ strtoupper(substr($conversation->user->name, 0, 1)) }}
    </div>
    <div class="flex-1">
        <p class="font-medium text-gray-900">{{ $conversation->user->name }}</p>
        <p class="text-xs text-gray-400 capitalize">{{ $conversation->user->role }}</p>
    </div>
    @if ($conversation->complaint)
        <a href="{{ route('admin.complaints.show', $conversation->complaint) }}"
            class="text-xs px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
            View Complaint
        </a>
    @endif
</div>

<div class="flex-1 flex flex-col overflow-hidden" x-data="{
    conversationId: {{ $conversation->id }},
    messages: [],
    error: '',
    fetching: false,
    init() {
        this.fetchMessages();
        this.poll = setInterval(() => {
            if (!this.$el.isConnected) { clearInterval(this.poll); return; }
            if (!document.hidden) this.fetchMessages();
        }, 1000);
    },
    fetchMessages() {
        if (this.fetching) return;
        this.fetching = true;
        const box = document.getElementById('messages-scroll');
        const wasNearBottom = !box || (box.scrollHeight - box.scrollTop - box.clientHeight < 100);

        fetch('/admin/messages/' + this.conversationId + '/fetch')
            .then(r => r.json())
            .then(data => {
                this.messages = data.messages;
                this.$nextTick(() => {
                    const newBox = document.getElementById('messages-scroll');
                    if (newBox && wasNearBottom) newBox.scrollTop = newBox.scrollHeight;
                });
            })
            .catch(() => {})
            .finally(() => { this.fetching = false; });
    },
    async sendMessage() {
        this.error = '';
        const bodyInput = this.$refs.bodyInput;
        const fileInput = this.$refs.fileInput;
        if (!bodyInput.value && !fileInput.files.length) return;

        const formData = new FormData();
        formData.append('body', bodyInput.value);
        if (fileInput.files.length) formData.append('attachment', fileInput.files[0]);

        const res = await fetch('/admin/messages/' + this.conversationId + '/send', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: formData,
        });

        if (!res.ok) {
            this.error = 'Message or attachment is required.';
            return;
        }

        bodyInput.value = '';
        fileInput.value = '';
        this.fetchMessages();
    }
}" @message-deleted.window="fetchMessages()">
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-scroll">
        <template x-for="message in messages" :key="message.id">
            <div :class="message.is_mine ? 'flex justify-end' : 'flex justify-start'">
                <div class="max-w-xs rounded-2xl px-4 py-2" :class="message.is_mine ? 'bg-[#3b1735] text-white' : 'bg-gray-100 text-gray-900'">
                    <p class="text-sm" x-show="message.body" x-text="message.body"></p>
                    <template x-for="attachment in message.attachments" :key="attachment.url">
                        <template x-if="/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                            <img :src="attachment.url" :alt="attachment.name"
                                class="max-w-full max-h-48 rounded-lg cursor-pointer"
                                @click="$dispatch('open-lightbox', attachment.url)">
                        </template>
                        <template x-if="!/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                            <a :href="attachment.url" target="_blank" class="text-xs underline block mt-1"
                                :class="message.is_mine ? 'text-purple-200' : 'text-blue-600'"
                                x-text="'📎 ' + attachment.name"></a>
                        </template>
                    </template>
                    <p class="text-xs mt-1" :class="message.is_mine ? 'text-purple-200' : 'text-gray-400'" x-text="message.created_at"></p>
                    <button x-show="message.is_mine" type="button" class="mt-2 inline-flex rounded-md border border-white/60 px-2 py-1 text-xs font-semibold text-white" :data-delete-message="'/admin/messages/' + conversationId + '/messages/' + message.id">Delete message</button>
                </div>
            </div>
        </template>
    </div>

    <p x-show="error" x-text="error" class="text-red-600 text-xs px-4 pt-2"></p>

    <div class="p-4 border-t border-gray-100 flex items-center gap-2">
        <label class="cursor-pointer text-gray-400 hover:text-gray-600">
            <input type="file" x-ref="fileInput" class="hidden" @change="sendMessage()">
            📎
        </label>
        <input type="text" x-ref="bodyInput" @keydown.enter="sendMessage()" placeholder="Type a message..."
            class="flex-1 px-3 py-2 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
        <button @click="sendMessage()" class="px-4 py-2 rounded-full bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">
            Send
        </button>
    </div>
</div>