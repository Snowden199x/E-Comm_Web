<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Suspended Sellers</h2>
            <p class="text-gray-500">Sellers currently suspended due to policy violations.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'Compliant Sellers', 'value' => $stats['compliant'], 'icon' => 'compliant-seller-icon.svg', 'color' => 'green'],
                ['label' => 'Sellers with Warnings', 'value' => $stats['with_warnings'], 'icon' => 'seller-with-warning-icon.svg', 'color' => 'orange'],
                ['label' => 'Sellers with Violence', 'value' => $stats['with_violations'], 'icon' => 'seller-with-violence-icon.svg', 'color' => 'red'],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended'], 'icon' => 'suspended-sellers-icon.svg', 'color' => 'purple'],
            ],
        ])

        <div x-data="{
            q: '{{ request('search') }}', timer: null,
            search() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    fetch('{{ route('seller-compliance.suspended-sellers-table') }}?search=' + encodeURIComponent(this.q))
                        .then(r => r.text()).then(html => { document.getElementById('suspended-table-wrap').innerHTML = html; });
                }, 250);
            }
        }">
            <div class="mb-4 relative w-full max-w-xs">
                <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                    class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                <input type="text" x-model="q" @input="search" autocomplete="off"
                    placeholder="Search seller name or email..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div id="suspended-table-wrap">
                @include('admin.seller-compliance.partials.suspended-sellers-table')
            </div>
        </div>
    </div>
</x-admin-layout>