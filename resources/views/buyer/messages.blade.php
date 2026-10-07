{{--
    Buyer Messages: seller chats on the left, Vendo Support on the right.
    Data from BuyerMessageController@index (unchanged): $sellerConversations (via the seller-list partial) and $conversation.
    Routes, form fields and the chat script are unchanged (messages.start with `reason`, messages.close, fetch, store).
    Restyled to the Vendo buyer design: no gray/emoji styling, SVG icons, entrance animation.
--}}
@php
    $supportOpen = $conversation && $conversation->status === 'open';
    $reasons = [
        ['General Inquiry', 'Questions about how Vendo works, your account or an order.', 'M12 17v.01M9.1 9a3 3 0 1 1 4.6 2.5c-.9.6-1.7 1.2-1.7 2.5M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z'],
        ['Raise a Concern', 'Report a problem with a seller, product or delivery.', 'M12 4 3 20h18zM12 10v4M12 17.5h.01'],
        ['Other', 'Anything else you would like help with.', 'M5 12h.01M12 12h.01M19 12h.01'],
    ];
@endphp

<x-buyer.layout title="Messages | Vendo">
    <div class="mx-auto max-w-[1100px] px-3 pb-14 pt-4 sm:px-4">
        <script>
            (() => {
                async function updateSellerChats() {
                    if (document.hidden) return;
                    try {
                        const response = await fetch(@json(route('buyer.messages.seller-list')), {headers: {'Accept': 'text/html'}});
                        if (response.ok) document.getElementById('buyerSellerChats').innerHTML = await response.text();
                    } catch (_) {}
                }
                setInterval(updateSellerChats, 1500);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) updateSellerChats(); });
            })();
            </script>

        <div class="vb-reveal">
            <h1 class="text-[20px] font-semibold text-[#2b1730]">Messages</h1>
            <p class="mt-0.5 text-[13px] text-[#7a6a7e]">Chat with sellers about your orders, or reach Vendo Support.</p>
        </div>

        <div class="mt-4 grid items-start gap-4 lg:grid-cols-[360px_1fr]">

            <!-- ===================== Seller chats ===================== -->
            <section class="vb-reveal overflow-hidden rounded-lg border border-[#eee6ef] bg-white" style="--i: 1" aria-labelledby="seller-chats-title">
                <div class="border-b border-[#f1e8f2] px-4 py-3.5">
                    <h2 id="seller-chats-title" class="text-[15px] font-semibold text-[#402143]">Seller chats</h2>
                    <p class="mt-0.5 text-[12px] leading-4 text-[#7a6a7e]">Questions about an order stay with the seller of that order.</p>
                </div>
                <div id="buyerSellerChats" class="vb-thin-scroll max-h-[560px] overflow-y-auto">@include('buyer.messages.partials.seller-list')</div>
            </section>

            <!-- ===================== Vendo Support ===================== -->
            <section class="vb-reveal min-w-0" style="--i: 2" aria-labelledby="support-title">
                @if (! $supportOpen)
                    <div class="overflow-hidden rounded-lg border border-[#eee6ef] bg-white">
                        <div class="flex items-center gap-3 border-b border-[#f1e8f2] px-5 py-4">
                            <span class="grid h-11 w-11 flex-shrink-0 place-items-center rounded-full bg-[#402143] text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1M4 13h3v5H5a1 1 0 0 1-1-1zM20 13h-3v5h2a1 1 0 0 0 1-1zM17 18c0 1.7-2 3-5 3" /></svg>
                            </span>
                            <div>
                                <h2 id="support-title" class="text-[15px] font-semibold text-[#402143]">Vendo Support</h2>
                                <p class="text-[12px] text-[#7a6a7e]">{{ $conversation ? 'Your last conversation has ended. Start a new one any time.' : 'Pick a topic to start a conversation with our team.' }}</p>
                            </div>
                        </div>

                        <div class="grid gap-3 p-5 sm:grid-cols-3">
                            @foreach ($reasons as $i => [$reasonName, $reasonText, $reasonIcon])
                                <form action="{{ route('buyer.messages.start') }}" method="POST" class="vb-reveal flex" style="--i: {{ $i + 3 }}">
                                    @csrf
                                    <input type="hidden" name="reason" value="{{ $reasonName }}">
                                    <button type="submit"
                                        class="group flex w-full flex-col items-start rounded-lg border border-[#eee6ef] bg-white p-4 text-left transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)] active:scale-[0.98]">
                                        <span class="grid h-10 w-10 place-items-center rounded-full bg-[#f3e8f5] text-[#52245b] transition-colors duration-300 group-hover:bg-[#805487] group-hover:text-white">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $reasonIcon }}" /></svg>
                                        </span>
                                        <span class="mt-3 text-[14px] font-semibold text-[#2b1730]">{{ $reasonName }}</span>
                                        <span class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">{{ $reasonText }}</span>
                                        <span class="mt-3 inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 group-hover:text-[#402143]">
                                            Start chat
                                            <svg class="h-3.5 w-3.5 transition-transform duration-300 ease-vendo group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex h-[min(620px,72vh)] flex-col overflow-hidden rounded-lg border border-[#eee6ef] bg-white" x-data="buyerSupportChat()">
                        <div class="flex items-center justify-between gap-3 border-b border-[#f1e8f2] px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-full bg-[#402143] text-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1M4 13h3v5H5a1 1 0 0 1-1-1zM20 13h-3v5h2a1 1 0 0 0 1-1zM17 18c0 1.7-2 3-5 3" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <h2 id="support-title" class="truncate text-[14px] font-semibold text-[#2b1730]">Vendo Support</h2>
                                    <p class="flex items-center gap-1.5 text-[12px] text-[#7a6a7e]"><span class="h-1.5 w-1.5 rounded-full bg-[#3f9b64]"></span>Open conversation</p>
                                </div>
                            </div>
                            <form action="{{ route('buyer.messages.close', $conversation) }}" method="POST" onsubmit="return confirm('End this conversation?')">
                                @csrf
                                <button class="inline-flex h-8 items-center rounded-md border border-[#e5dce7] px-3 text-[12px] font-medium text-[#5b4a60] transition-colors duration-200 hover:border-[#e3b4bd] hover:bg-[#fdf1f3] hover:text-[#a32b43]">End conversation</button>
                            </form>
                        </div>

                        <div class="vb-thin-scroll flex-1 space-y-3 overflow-y-auto bg-[#fcfafc] px-4 py-4" x-ref="scrollBox">
                            <div x-show="!messages.length" x-cloak class="grid h-full place-items-center text-center">
                                <div>
                                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" /></svg>
                                    </span>
                                    <p class="mt-3 text-[13px] font-medium text-[#2b1730]">Say hello</p>
                                    <p class="mt-0.5 text-[12px] text-[#7a6a7e]">Describe what you need and our team will reply here.</p>
                                </div>
                            </div>

                            <template x-for="message in messages" :key="message.id">
                                <div class="flex items-end gap-2" :class="message.is_mine ? 'justify-end' : (message.is_system ? 'justify-center' : 'justify-start')">
                                    <span x-show="!message.is_system && !message.is_mine" class="h-8 w-8 shrink-0"><img x-show="message.avatar" :src="message.avatar" alt="" class="h-8 w-8 rounded-full object-cover"><span x-show="!message.avatar" class="grid h-8 w-8 place-items-center rounded-full bg-[#805487] text-xs font-semibold text-white" x-text="message.initial"></span></span>
                                    <div class="max-w-[78%] px-3.5 py-2 sm:max-w-[420px]"
                                        :class="message.is_system ? 'rounded-full bg-[#fbf3dc] text-[12px] text-[#7a5a0c]' : (message.is_mine ? 'rounded-2xl rounded-br-md bg-[#402143] text-white' : 'rounded-2xl rounded-bl-md border border-[#eee6ef] bg-white text-[#2b1730]')">
                                        <p class="whitespace-pre-line break-words text-[13px] leading-5" x-show="message.body" x-text="message.body"></p>
                                        <template x-for="attachment in message.attachments" :key="attachment.url">
                                            <div>
                                                <template x-if="/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                                                    <img :src="attachment.url" :alt="attachment.name" class="mt-1 max-h-48 max-w-full cursor-pointer rounded-lg" @click="lightbox = attachment.url">
                                                </template>
                                                <template x-if="!/\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.name)">
                                                    <a :href="attachment.url" target="_blank" class="mt-1 flex items-center gap-1.5 text-[12px] underline" :class="message.is_mine ? 'text-[#e9d3ee]' : 'text-[#52245b]'">
                                                        <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 11-8.6 8.6a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.6 8.6a2 2 0 0 1-3-3L15 7" /></svg>
                                                        <span x-text="attachment.name"></span>
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <span x-show="message.is_mine" class="h-8 w-8 shrink-0"><img x-show="message.avatar" :src="message.avatar" alt="" class="h-8 w-8 rounded-full object-cover"><span x-show="!message.avatar" class="grid h-8 w-8 place-items-center rounded-full bg-[#805487] text-xs font-semibold text-white" x-text="message.initial"></span></span>
                                </div>
                            </template>
                        </div>

                        <p x-show="error" x-text="error" role="alert" class="border-t border-[#f3d4da] bg-[#fdf1f3] px-4 py-2 text-[12px] text-[#a32b43]"></p>

                        <div class="flex items-center gap-2 border-t border-[#f1e8f2] bg-white p-3">
                            <label class="grid h-10 w-10 flex-shrink-0 cursor-pointer place-items-center rounded-full text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]" title="Attach a file">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 11-8.6 8.6a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.6 8.6a2 2 0 0 1-3-3L15 7" /></svg>
                                <span class="sr-only">Attach a file</span>
                                <input type="file" x-ref="fileInput" class="hidden" @change="send()">
                            </label>
                            <input type="text" x-model="body" @keydown.enter="send()" placeholder="Type a message..." aria-label="Message"
                                class="h-10 min-w-0 flex-1 rounded-full border-[#e5dce7] bg-[#faf7fb] px-4 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:border-[#805487] focus:bg-white focus:ring-[#805487]">
                            <button @click="send()" aria-label="Send message"
                                class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-full bg-[#402143] text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-90">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z" /></svg>
                            </button>
                        </div>

                        <div x-show="lightbox" x-cloak @click="lightbox = null" @keydown.escape.window="lightbox = null"
                            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/80 p-4">
                            <img :src="lightbox" alt="" class="max-h-full max-w-full rounded-lg">
                            <button type="button" @click="lightbox = null" aria-label="Close image"
                                class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/15 text-white transition-colors duration-200 hover:bg-white/30">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
    @if($conversation?->status === 'open')
    <script>
    function buyerSupportChat() {
        return {
            messages: [], body: '', error: '', lightbox: null, fetching: false,
            draftKey: @js('vendo.buyer.support.'.auth()->id().'.'.$conversation->id),
            init() {
                try {
                    const draft = JSON.parse(sessionStorage.getItem(this.draftKey) || 'null');
                    if (draft?.savedAt > Date.now() - 7200000 && typeof draft.body === 'string') {
                        this.body = draft.body;
                        window.vendoDraftNotice?.restored(this.draftKey, 'Your unsent message was restored. Reattach any file before sending.');
                    } else if (draft) {
                        sessionStorage.removeItem(this.draftKey);
                        window.vendoDraftNotice?.clear(this.draftKey);
                    }
                } catch (_) {}
                this.$watch('body', () => this.saveDraft());
                this.fetchMessages();
                this.poll = setInterval(() => {
                    if (!this.$el.isConnected) { clearInterval(this.poll); return; }
                    if (!document.hidden) this.fetchMessages();
                }, 1000);
            },
            saveDraft() {
                try {
                    if (this.body.trim()) sessionStorage.setItem(this.draftKey, JSON.stringify({ savedAt: Date.now(), body: this.body }));
                    else {
                        sessionStorage.removeItem(this.draftKey);
                        window.vendoDraftNotice?.clear(this.draftKey);
                    }
                } catch (_) {}
            },
            async fetchMessages() {
                if (this.fetching) return;
                this.fetching = true;
                const box = this.$refs.scrollBox;
                const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                try {
                    const response = await fetch(@json(route('buyer.messages.fetch', $conversation)));
                    if (response.status === 404) { location.assign(@json(route('buyer.messages.index'))); return; }
                    if (!response.ok) return;
                    const data = await response.json();
                    if (data.status !== 'open') { location.reload(); return; }
                    this.messages = data.messages;
                    this.$nextTick(() => { if (nearBottom) box.scrollTop = box.scrollHeight; });
                } catch (_) {} finally { this.fetching = false; }
            },
            async send() {
                this.error = '';
                const fileInput = this.$refs.fileInput;
                if (!this.body.trim() && !fileInput.files.length) return;
                const formData = new FormData();
                formData.append('body', this.body);
                if (fileInput.files.length) formData.append('attachment', fileInput.files[0]);
                try {
                    const response = await fetch(@json(route('buyer.messages.store', $conversation)), {
                        method: 'POST', body: formData,
                        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    });
                    if (!response.ok) { this.error = 'Message could not be sent. Check the text or attachment and try again.'; return; }
                    this.body = ''; fileInput.value = ''; this.saveDraft(); this.fetchMessages();
                } catch (_) { this.error = 'Connection lost. Please try again.'; }
            },
        };
    }
    </script>
    @endif
</x-buyer.layout>