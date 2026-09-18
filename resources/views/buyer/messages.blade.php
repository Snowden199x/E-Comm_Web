<x-buyer-layout>
    <div class="max-w-3xl mx-auto p-4 sm:p-5 lg:p-6" x-data="{
        messages: [],
        body: '',
        error: '',
        lightbox: null,
        init() {
            this.fetchMessages();
            setInterval(() => this.fetchMessages(), 3000);
        },
        fetchMessages() {
            const el = this.$refs.scrollBox;
            const wasNearBottom = !el || (el.scrollHeight - el.scrollTop - el.clientHeight < 100);

            fetch('{{ route('buyer.messages.fetch') }}')
                .then(res => res.json())
                .then(data => {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        if (wasNearBottom) el.scrollTop = el.scrollHeight;
                    });
                });
        },
        async send() {
            this.error = '';
            const fileInput = this.$refs.fileInput;
            if (!this.body && !fileInput.files.length) return;

            const formData = new FormData();
            formData.append('body', this.body);
            if (fileInput.files.length) formData.append('attachment', fileInput.files[0]);

            const res = await fetch('{{ route('buyer.messages.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}' },
                body: formData,
            });

            if (!res.ok) {
                this.error = 'Type a message or attach a file.';
                return;
            }

            this.body = '';
            fileInput.value = '';
            this.fetchMessages();
        }
    }">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Messages</h2>

        <div class="bg-white rounded-2xl shadow-sm flex flex-col h-[500px]">
            <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="scrollBox">
                <template x-if="messages.length === 0">
                    <div class="flex justify-start">
                        <div class="max-w-[70%] px-4 py-2 rounded-2xl text-sm bg-gray-100 text-gray-800">
                            {{ \App\Models\Communication\ChatSetting::currentWelcomeMessage() }}
                        </div>
                    </div>
                </template>

                <template x-for="message in messages" :key="message.id">
                    <div :class="message.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div class="max-w-xs rounded-2xl px-4 py-2"
                            :class="message.is_mine ? 'bg-[#3b1735] text-white' : 'bg-gray-100 text-gray-800'">
                            <p class="text-sm" x-show="message.body" x-text="message.body"></p>
                            <template x-for="attachment in message.attachments" :key="attachment.url">
                                <template x-if="/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                                    <img :src="attachment.url" :alt="attachment.name"
                                        class="max-w-full max-h-48 rounded-lg cursor-pointer mt-1"
                                        @click="lightbox = attachment.url">
                                </template>
                                <template x-if="!/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                                    <a :href="attachment.url" target="_blank" class="block underline text-xs mt-1"
                                        :class="message.is_mine ? 'text-purple-200' : 'text-blue-600'"
                                        x-text="'📎 ' + attachment.name"></a>
                                </template>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <p x-show="error" x-text="error" class="text-red-600 text-xs px-4 pt-2"></p>

            <div class="flex items-center gap-2 p-4 border-t">
                <label class="cursor-pointer text-gray-500 hover:text-[#3b1735]">
                    📎
                    <input type="file" x-ref="fileInput" class="hidden" @change="send()">
                </label>
                <input type="text" x-model="body" @keydown.enter="send()" placeholder="Type a message..."
                    class="flex-1 border rounded-lg px-3 py-2 text-sm">
                <button @click="send()"
                    class="bg-[#3b1735] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#4d1f45]">
                    Send
                </button>
            </div>
        </div>

        <div x-show="lightbox" x-cloak @click="lightbox = null"
            class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
            <img :src="lightbox" class="max-w-full max-h-full rounded-lg">
            <button type="button" @click="lightbox = null"
                class="absolute top-4 right-4 text-white text-3xl leading-none">&times;</button>
        </div>
    </div>
</x-buyer-layout>
