<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ openProductId: null, rejectId: null, warnId: null, confirmation: @js(session('confirmation')) }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Products for Review</h2>
            <p class="text-gray-500">Review products submitted by sellers before they go live</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                [
                    'label' => 'For Review',
                    'value' => $stats['for_review'],
                    'icon' => 'product-approved-element.svg',
                    'color' => 'green',
                ],
                [
                    'label' => 'Warning Issued',
                    'value' => $stats['warnings_issued'],
                    'icon' => 'warning-issued-element.svg',
                    'color' => 'orange',
                ],
                [
                    'label' => 'Violations (Rejected)',
                    'value' => $stats['violations'],
                    'icon' => 'product-rejected-element.svg',
                    'color' => 'red',
                ],
                [
                    'label' => 'Suspended Sellers',
                    'value' => $stats['suspended_sellers'],
                    'icon' => 'suspended-sellers-icon.svg',
                    'color' => 'purple',
                ],
            ],
        ])

        <div x-data="{
            q: '{{ request('search') }}',
            categoryId: '{{ request('category_id') }}',
            timer: null,
            search() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const params = new URLSearchParams({ search: this.q, category_id: this.categoryId });
                    fetch('{{ route('seller-compliance.products-table') }}?' + params)
                        .then(r => r.text())
                        .then(html => { document.getElementById('products-table-wrap').innerHTML = html; });
                }, 250);
            }
        }">
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="flex-1 relative">
                    <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                        class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                    <input type="text" x-model="q" @input="search" autocomplete="off"
                        placeholder="Search seller name or email..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                </div>
                <div class="relative" x-data="{ catOpen: false, categoryName: 'All Categories' }" @click.outside="catOpen = false">
                    <button type="button" @click="catOpen = !catOpen"
                        class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2 min-w-[180px] justify-between focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                        <span x-text="categoryName"></span>
                        <span class="text-gray-400">&#9662;</span>
                    </button>
                    <div x-show="catOpen" x-cloak
                        class="absolute z-10 mt-1 w-full bg-white rounded-lg border border-gray-100 shadow-lg max-h-[224px] overflow-y-auto">
                        <button type="button"
                            @click="categoryId = ''; categoryName = 'All Categories'; catOpen = false; search()"
                            class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">All Categories</button>
                        @foreach ($categories as $cat)
                            <button type="button"
                                @click="categoryId = '{{ $cat->id }}'; categoryName = '{{ $cat->name }}'; catOpen = false; search()"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">{{ $cat->name }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="products-table-wrap">
                @include('admin.seller-compliance.partials.products-table')
            </div>
        </div>

        @include('admin.seller-compliance.partials.confirmation-modal')
    </div>
</x-admin-layout>
