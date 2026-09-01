<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ openProductId: null, rejectId: null, warnId: null, confirmation: @js(session('confirmation')) }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Products for Review</h2>
            <p class="text-gray-500">Review products submitted by sellers before they go live</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'For Review', 'value' => $stats['for_review'], 'icon' => 'product-approved-element.svg', 'color' => 'green'],
                ['label' => 'Warning Issued', 'value' => $stats['warnings_issued'], 'icon' => 'warning-issued-element.svg', 'color' => 'orange'],
                ['label' => 'Violations (Rejected)', 'value' => $stats['violations'], 'icon' => 'product-rejected-element.svg', 'color' => 'red'],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended_sellers'], 'icon' => 'suspended-sellers-icon.svg', 'color' => 'purple'],
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
                <select x-model="categoryId" @change="search"
                    class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="products-table-wrap">
                @include('admin.seller-compliance.partials.products-table')
            </div>
        </div>

        @include('admin.seller-compliance.partials.confirmation-modal')
    </div>
</x-admin-layout>