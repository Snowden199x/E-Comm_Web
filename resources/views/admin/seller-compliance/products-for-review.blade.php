<x-admin.layout title="Products for Review">
    @vite('resources/css/admin/seller-compliance.css')

    <div class="sc-page mx-auto w-full max-w-[1280px] p-4 sm:p-6">
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Products for Review</h1>
            <p class="mt-1 text-sm text-gray-500">Review products submitted by sellers before they go live.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'For Review', 'value' => $stats['for_review'], 'icon' => 'clipboard-check', 'color' => 'green'],
                ['label' => 'Warnings Issued', 'value' => $stats['warnings_issued'], 'icon' => 'triangle-alert', 'color' => 'orange', 'href' => route('admin.seller-compliance.warnings')],
                ['label' => 'Violations (Rejected)', 'value' => $stats['violations'], 'icon' => 'shield-x', 'color' => 'red', 'href' => route('admin.seller-compliance.violations')],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended_sellers'], 'icon' => 'ban', 'color' => 'purple', 'href' => route('admin.seller-compliance.suspended-sellers')],
            ],
        ])

        @include('admin.seller-compliance.partials.table-shell', [
            'table' => 'admin.seller-compliance.partials.products-table',
            'url' => route('admin.seller-compliance.products-table'),
            'placeholder' => 'Search product or seller name',
            'showCategory' => true,
            'outside' => 'admin.seller-compliance.partials.confirmation-modal',
        ])
    </div>
@include('shared.live-revision', ['endpoint' => route('admin.live', 'products'), 'mode' => 'reload'])
</x-admin.layout>