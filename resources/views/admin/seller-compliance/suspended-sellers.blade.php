<x-admin.layout title="Suspended Sellers">
    @vite('resources/css/admin/seller-compliance.css')

    <div class="sc-page mx-auto w-full max-w-[1280px] p-4 sm:p-6">
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Suspended Sellers</h1>
            <p class="mt-1 text-sm text-gray-500">Sellers currently suspended because of policy violations.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'Compliant Sellers', 'value' => $stats['compliant'], 'icon' => 'shield-check', 'color' => 'green', 'href' => route('admin.seller-compliance.overview')],
                ['label' => 'Sellers with Warnings', 'value' => $stats['with_warnings'], 'icon' => 'triangle-alert', 'color' => 'orange', 'href' => route('admin.seller-compliance.warnings')],
                ['label' => 'Sellers with Violations', 'value' => $stats['with_violations'], 'icon' => 'shield-x', 'color' => 'red', 'href' => route('admin.seller-compliance.violations')],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended'], 'icon' => 'ban', 'color' => 'purple', 'href' => route('admin.seller-compliance.suspended-sellers')],
            ],
        ])

        @include('admin.seller-compliance.partials.table-shell', [
            'table' => 'admin.seller-compliance.partials.suspended-sellers-table',
            'url' => route('admin.seller-compliance.suspended-sellers-table'),
            'placeholder' => 'Search seller name or email',
            'outside' => 'admin.seller-compliance.partials.confirmation-modal',
        ])
    </div>
</x-admin.layout>