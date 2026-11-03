<x-admin.layout title="Products for Review">
    @vite('resources/css/admin/seller-compliance.css')

    <div class="sc-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7">
        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Products for Review</h1>
            <p class="mt-1 text-sm text-vd-muted">Approve, warn or reject new products.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'For Review', 'value' => $stats['for_review'], 'icon' => 'clipboard-check', 'color' => 'green'],
                ['label' => 'Warnings Issued', 'value' => $stats['warnings_issued'], 'icon' => 'triangle-alert', 'color' => 'orange', 'href' => route('admin.seller-compliance.warnings')],
                ['label' => 'Violations (Rejected)', 'value' => $stats['violations'], 'icon' => 'alert-circle', 'color' => 'red', 'href' => route('admin.seller-compliance.violations')],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended_sellers'], 'icon' => 'lock', 'color' => 'purple', 'href' => route('admin.seller-compliance.suspended-sellers')],
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