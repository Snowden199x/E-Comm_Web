<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ createOpen: false, viewAnnouncementId: null, editAnnouncementId: null, deleteAnnouncementId: null, viewPolicyId: null, editPolicyId: null, deletePolicyId: null, confirmation: @js(session('confirmation')) }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Manage Platform Settings</h2>
            <p class="text-gray-500">Manage announcements and platform policies.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <div class="lg:col-span-2 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                        <img src="{{ asset('assets/icons/platform_settings/total_announcements.svg') }}" alt=""
                            class="w-10 h-10">
                        <div>
                            <p class="text-xs text-gray-600">Total Announcements</p>
                            <p class="text-xl font-bold text-black">{{ number_format($stats['total']) }}</p>
                        </div>
                    </div>
                    <div class="bg-green-50 border-2 border-green-500 rounded-2xl p-4 flex items-center gap-3">
                        <img src="{{ asset('assets/icons/platform_settings/published.svg') }}" alt=""
                            class="w-10 h-10">
                        <div>
                            <p class="text-xs text-gray-600">Published</p>
                            <p class="text-xl font-bold text-black">{{ number_format($stats['published']) }}</p>
                        </div>
                    </div>
                    <div class="bg-orange-50 border-2 border-orange-500 rounded-2xl p-4 flex items-center gap-3">
                        <img src="{{ asset('assets/icons/platform_settings/scheduled.svg') }}" alt=""
                            class="w-10 h-10">
                        <div>
                            <p class="text-xs text-gray-600">Scheduled</p>
                            <p class="text-xl font-bold text-black">{{ number_format($stats['scheduled']) }}</p>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-2xl p-4 flex items-center gap-3">
                        <img src="{{ asset('assets/icons/platform_settings/drafts.svg') }}" alt=""
                            class="w-10 h-10">
                        <div>
                            <p class="text-xs text-gray-600">Drafts</p>
                            <p class="text-xl font-bold text-black">{{ number_format($stats['drafts']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm" x-data="{
                    q: '{{ request('search') }}',
                    statusFilter: '{{ request('status') }}',
                    dateFilter: '{{ request('date_filter', 'all') }}',
                    customDate: '{{ request('custom_date') }}',
                    dateOpen: false,
                    timer: null,
                    dateLabel() {
                        return { all: 'All Dates', today: 'Today', week: 'This Week', month: 'This Month', custom: this.customDate || 'Custom Date' } [this.dateFilter];
                    },
                    search() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => {
                            const params = new URLSearchParams({ search: this.q, status: this.statusFilter, date_filter: this.dateFilter, custom_date: this.customDate });
                            fetch('{{ route('platform-settings.announcements-table') }}?' + params)
                                .then(r => r.text()).then(html => { document.getElementById('announcements-table-wrap').innerHTML = html; });
                        }, 250);
                    }
                }">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900">Recent Announcements</h3>
                        <button type="button" @click="createOpen = true"
                            class="px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">+
                            Create Announcement</button>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div class="relative w-72">
                            <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                                class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                            <input type="text" x-model="q" @input="search" autocomplete="off"
                                placeholder="Search announcements..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                        </div>

                        <div class="flex gap-3">
                            <div class="relative" @click.outside="dateOpen = false">
                                <button type="button" @click="dateOpen = !dateOpen"
                                    class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2 min-w-[140px] justify-between">
                                    <span x-text="dateLabel()"></span>
                                    <span class="text-gray-400">&#9662;</span>
                                </button>
                                <div x-show="dateOpen" x-cloak
                                    class="absolute right-0 z-10 mt-1 w-56 bg-white rounded-lg border border-gray-100 shadow-lg p-2">
                                    <button type="button" @click="dateFilter = 'all'; dateOpen = false; search()"
                                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">All
                                        Dates</button>
                                    <button type="button" @click="dateFilter = 'today'; dateOpen = false; search()"
                                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">Today</button>
                                    <button type="button" @click="dateFilter = 'week'; dateOpen = false; search()"
                                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This
                                        Week</button>
                                    <button type="button" @click="dateFilter = 'month'; dateOpen = false; search()"
                                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This
                                        Month</button>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <label class="block px-3 py-1 text-xs text-gray-400">Custom Date</label>
                                    <input type="date" x-model="customDate"
                                        @change="dateFilter = 'custom'; dateOpen = false; search()"
                                        class="w-full px-3 py-2 rounded border border-gray-200 text-sm">
                                </div>
                            </div>

                            <select x-model="statusFilter" @change="search"
                                class="appearance-none bg-no-repeat bg-[right_0.75rem_center] bg-[length:12px] px-3 pr-9 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]"
                                style="background-image: url('{{ asset('assets/icons/user-management/down-arrow-icon.svg') }}');">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>

                    <div id="announcements-table-wrap">
                        @include('admin.platform-settings.partials.announcements-table')
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900">Platform Policy</h3>
                            <p class="text-xs text-gray-500">Manage and update platform policies</p>
                        </div>
                        <button type="button"
                            onclick="document.getElementById('add-policy-form').classList.toggle('hidden')"
                            class="text-xs px-3 py-1.5 rounded-full border border-[#3b1735] text-[#3b1735] hover:bg-purple-50">+
                            Add</button>
                    </div>

                    <form id="add-policy-form" method="POST" action="{{ route('platform-settings.policies.store') }}"
                        class="hidden mb-4 flex gap-2">
                        @csrf
                        <select name="name" required
                            class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                            <option value="">Select policy type...</option>
                            <option value="Seller Policy">Seller Policy</option>
                            <option value="Buyer Policy">Buyer Policy</option>
                            <option value="Logistics Policy">Logistics Policy</option>
                            <option value="Prohibited Item Policy">Prohibited Item Policy</option>
                        </select>
                        <button type="submit"
                            class="px-3 py-2 rounded-lg bg-[#3b1735] text-white text-xs font-medium">Add</button>
                    </form>

                    <div class="space-y-3">
                        @forelse ($policies as $policy)
                            <div class="pb-3 border-b border-gray-100 last:border-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <img src="{{ asset('assets/icons/platform_settings/' . $policy->icon['icon']) }}"
                                        alt="" class="w-10 h-10 flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $policy->name }}</p>
                                        <p class="text-xs text-gray-400">Version {{ $policy->version }}</p>
                                        <p class="text-xs text-gray-400">Last updated
                                            {{ $policy->updated_at->format('M j, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="viewPolicyId = {{ $policy->id }}"
                                        class="px-4 py-1.5 rounded-full border border-[#3b1735] text-xs font-medium text-[#3b1735] hover:bg-purple-50 whitespace-nowrap">View</button>
                                    <button type="button" @click="editPolicyId = {{ $policy->id }}"
                                        class="px-4 py-1.5 rounded-full border border-[#3b1735] text-xs font-medium text-[#3b1735] hover:bg-purple-50 whitespace-nowrap">Edit</button>
                                    <button type="button" @click="deletePolicyId = {{ $policy->id }}"
                                        class="px-4 py-1.5 rounded-full border border-red-300 text-xs font-medium text-red-600 hover:bg-red-50 whitespace-nowrap">Delete</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No policies yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-1">Chat Welcome Message</h3>
                    <p class="text-xs text-gray-500 mb-3">Sent automatically when a user starts a support chat.</p>
                    <form method="POST" action="{{ route('platform-settings.chat-welcome.update') }}">
                        @csrf
                        <textarea name="welcome_message" rows="3" required
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-[#3b1735]">{{ $chatSetting->welcome_message ?? 'Hi! How can we help you today?' }}</textarea>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Save</button>
                    </form>
                </div>
            </div>

        </div>

        @include('admin.platform-settings.partials.create-announcement-modal')
        @foreach ($announcements as $a)
            @include('admin.platform-settings.partials.view-announcement-modal', ['a' => $a])
            @include('admin.platform-settings.partials.edit-announcement-modal', ['a' => $a])
            @include('admin.platform-settings.partials.delete-announcement-modal', ['a' => $a])
        @endforeach
        @foreach ($policies as $policy)
            @include('admin.platform-settings.partials.view-policy-modal', ['policy' => $policy])
            @include('admin.platform-settings.partials.edit-policy-modal', ['policy' => $policy])
            @include('admin.platform-settings.partials.delete-policy-modal', ['policy' => $policy])
        @endforeach
    </div>
</x-admin-layout>
