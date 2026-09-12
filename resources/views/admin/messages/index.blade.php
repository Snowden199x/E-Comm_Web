<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6 h-[calc(100vh-89px)] flex flex-col" x-data="{
        activeId: {{ $activeId ?? 'null' }},
        lightboxImage: null,
        openConversation(id) {
            this.activeId = id;
            fetch('{{ route('messages.thread', ['conversation' => '__ID__']) }}'.replace('__ID__', id))
                .then(r => r.text())
                .then(html => {
                    document.getElementById('thread-wrap').innerHTML = html;
                    this.refreshList();
                });
        },
        refreshList() {
            const params = new URLSearchParams({ search: document.querySelector('[x-model=q]')?.value || '' });
            fetch('{{ route('messages.list') }}?' + params)
                .then(r => r.text())
                .then(html => { document.getElementById('conversation-list').innerHTML = html; });
        }
    }">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900">Messages</h2>
            <p class="text-gray-500">Support conversations with buyers and sellers.</p>
        </div>

        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 min-h-0">
            <div class="bg-white rounded-2xl shadow-sm flex flex-col overflow-hidden" x-data="{
                q: '{{ request('search') }}',
                timer: null,
                search() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => {
                        fetch('{{ route('messages.list') }}?search=' + encodeURIComponent(this.q))
                            .then(r => r.text()).then(html => { document.getElementById('conversation-list').innerHTML = html; });
                    }, 250);
                }
            }">
                <div class="p-4 border-b border-gray-100">
                    <div class="relative">
                        <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 opacity-50">
                        <input type="text" x-model="q" @input="search" autocomplete="off"
                            placeholder="Search conversations..."
                            class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                    </div>
                </div>
                <div id="conversation-list" class="flex-1 overflow-y-auto">
                    @include('admin.messages.partials.conversation-list')
                </div>
            </div>

            <div class="md:col-span-2 bg-white rounded-2xl shadow-sm flex flex-col overflow-hidden">
                <div id="thread-wrap" class="flex-1 flex flex-col overflow-hidden">
                    @if ($activeId)
                        @php $activeConversation = $conversations->firstWhere('id', (int) $activeId); @endphp
                        @if ($activeConversation)
                            @include('admin.messages.partials.thread', [
                                'conversation' => $activeConversation->load([
                                    'user',
                                    'complaint',
                                    'messages.sender',
                                    'messages.attachments',
                                ]),
                            ])
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                            Select a conversation to view messages.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div x-show="lightboxImage" x-cloak @open-lightbox.window="lightboxImage = $event.detail"
            class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" @click="lightboxImage = null">
            <img :src="lightboxImage" class="max-w-full max-h-full rounded-lg">
            <button type="button" @click="lightboxImage = null"
                class="absolute top-4 right-4 text-white text-3xl leading-none">&times;</button>
        </div>
    </div>
</x-admin-layout>
