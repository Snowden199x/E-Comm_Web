<x-admin.layout title="Warnings">
    @vite('resources/css/admin/seller-compliance.css')

    <div class="sc-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7">
        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Warnings</h1>
            <p class="mt-1 text-sm text-vd-muted">See warnings issued to sellers.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                ['label' => 'Compliant Sellers', 'value' => $stats['compliant'], 'icon' => 'shield-check', 'color' => 'green', 'href' => route('admin.seller-compliance.overview')],
                ['label' => 'Sellers with Warnings', 'value' => $stats['with_warnings'], 'icon' => 'triangle-alert', 'color' => 'orange', 'href' => route('admin.seller-compliance.warnings')],
                ['label' => 'Sellers with Violations', 'value' => $stats['with_violations'], 'icon' => 'alert-circle', 'color' => 'red', 'href' => route('admin.seller-compliance.violations')],
                ['label' => 'Suspended Sellers', 'value' => $stats['suspended'], 'icon' => 'lock', 'color' => 'purple', 'href' => route('admin.seller-compliance.suspended-sellers')],
            ],
        ])

        @include('admin.seller-compliance.partials.table-shell', [
            'table' => 'admin.seller-compliance.partials.warnings-table',
            'url' => route('admin.seller-compliance.warnings-table'),
            'placeholder' => 'Search seller name or email',
            'showDate' => true,
        ])
    </div>
</x-admin.layout>