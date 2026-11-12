@php
    $periods = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year'];
@endphp
<x-seller.layout title="Reports">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/reports.css', 'resources/js/seller/delivered-orders.js', 'resources/js/seller/reports.js'])
    <section class="ops-page do-page rp-page">
        <header class="do-head">
            <div>
                <h1>Reports</h1>
                <p>Sales from orders delivered in the selected period.</p>
            </div>
            <div class="rp-actions">
                <button type="button" class="do-btn do-btn--soft" data-rp-preview="{{ route('seller.reports.preview', ['period' => $period]) }}">
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    Preview report
                </button>
                <a class="do-btn do-btn--primary" href="{{ route('seller.reports.download', ['period' => $period]) }}" data-rp-download>
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
                    Download PDF
                </a>
            </div>
        </header>

        @if($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.500v.01"/></svg>
                <div>@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
                <a class="do-btn do-btn--small" href="{{ route('seller.reports.index') }}">Show today</a>
            </div>
        @endif

        <div class="rp-bar-top">
            <nav class="do-chips" aria-label="Report period">
                @foreach($periods as $value => $label)
                    <a href="{{ route('seller.reports.index', ['period' => $value]) }}" class="do-chip @if($period === $value) is-active @endif" @if($period === $value) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <p class="rp-range">{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }} · Manila time</p>
        </div>

        @include('seller.reports.summary')
    </section>

    <dialog class="rp-dialog" id="rpDialog" aria-labelledby="rpTitle">
        <header class="rp-dialog__head">
            <h2 id="rpTitle">Report preview</h2>
            <button type="button" class="cmp-x" data-rp-close aria-label="Close preview">×</button>
        </header>
        <div class="rp-dialog__body" id="rpContent" aria-live="polite"></div>
    </dialog>
</x-seller.layout>
