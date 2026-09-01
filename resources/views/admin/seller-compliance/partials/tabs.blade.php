<div class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 p-1.5 mb-6 w-fit overflow-x-auto">
    @php
        $tabs = [
            'seller-compliance.overview' => ['label' => 'Overview', 'icon' => 'overview-page-icon (1).svg'],
            'seller-compliance.products-for-review' => ['label' => 'Products for Review', 'icon' => 'products-for-review-page-icon (1).svg'],
            'seller-compliance.warnings' => ['label' => 'Warnings', 'icon' => 'warning-page-icon (1).svg'],
            'seller-compliance.violations' => ['label' => 'Violations', 'icon' => 'violation-page-icon (1).svg'],
            'seller-compliance.suspended-sellers' => ['label' => 'Suspended Sellers', 'icon' => 'suspended-seller-page-icon (1).svg'],
        ];
    @endphp
    @foreach ($tabs as $route => $tab)
        <a href="{{ route($route) }}"
            @class([
                'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap',
                'bg-purple-50 text-[#3b1735]' => request()->routeIs($route),
                'text-gray-500 hover:bg-gray-50' => !request()->routeIs($route),
            ])>
            <img src="{{ asset('assets/icons/seller-compliance/' . $tab['icon']) }}" alt="" class="w-4 h-4">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>