<x-seller.layout title="Reports">
    @vite('resources/css/seller/workspace.css')
    <section class="sw-page">
        <header class="sw-heading"><div><h1>Reports</h1><p>Sales from orders delivered in the selected period.</p></div></header>
        <form method="GET" action="{{ route('seller.reports.index') }}" class="sw-toolbar">
            <label>Period <select name="period" onchange="this.form.submit()">
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $value => $label)
                    <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            <span class="sw-toolbar__range">{{ $start->format('M j, Y g:i A') }} – {{ $end->format('M j, Y g:i A') }}</span>
            <div class="sw-report-action" data-report-menu><button class="sw-button sw-button--outline" type="button" aria-expanded="false">Preview ▾</button><div class="sw-report-action__menu" hidden>
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $value => $label)<button type="button" data-preview-period="{{ $value }}">{{ $label }}</button>@endforeach
            </div></div>
            <div class="sw-report-action" data-report-menu><button class="sw-button" type="button" aria-expanded="false">Download PDF ▾</button><div class="sw-report-action__menu" hidden>
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $value => $label)<a href="{{ route('seller.reports.download', ['period' => $value]) }}">{{ $label }}</a>@endforeach
            </div></div>
        </form>
        @include('seller.reports.summary')
    </section>
    <div class="sw-report-modal" id="swReportModal" hidden role="dialog" aria-modal="true" aria-labelledby="swReportModalTitle">
        <div class="sw-report-modal__panel" tabindex="-1"><header><h2 id="swReportModalTitle">Report Preview</h2><button type="button" id="swReportClose" aria-label="Close preview">×</button></header><div id="swReportContent" class="sw-report-modal__content"></div></div>
    </div>
    <script>
    (() => {
        const menus = [...document.querySelectorAll('[data-report-menu]')];
        const modal = document.getElementById('swReportModal');
        const close = document.getElementById('swReportClose');
        const content = document.getElementById('swReportContent');
        let lastFocus;
        const closeMenus = () => menus.forEach(menu => { menu.querySelector('.sw-report-action__menu').hidden = true; menu.querySelector('button').setAttribute('aria-expanded', 'false'); });
        menus.forEach(menu => menu.querySelector('button').addEventListener('click', () => { const popup = menu.querySelector('.sw-report-action__menu'); const open = popup.hidden; closeMenus(); popup.hidden = !open; menu.querySelector('button').setAttribute('aria-expanded', String(open)); }));
        document.addEventListener('click', event => { if (!event.target.closest('[data-report-menu]')) closeMenus(); });
        document.querySelectorAll('[data-preview-period]').forEach(button => button.addEventListener('click', async () => {
            lastFocus = document.activeElement; closeMenus();
            content.textContent = 'Loading report…'; modal.hidden = false; modal.querySelector('.sw-report-modal__panel').focus();
            try {
                const url = @json(route('seller.reports.preview')) + '?period=' + encodeURIComponent(button.dataset.previewPeriod);
                const response = await fetch(url, {headers: {'Accept': 'text/html'}});
                if (!response.ok) throw new Error('Preview unavailable.');
                content.innerHTML = await response.text();
            } catch (_) { content.textContent = 'Could not load the preview. Please try again.'; }
        }));
        const hide = () => { modal.hidden = true; lastFocus?.focus(); };
        close.addEventListener('click', hide);
        modal.addEventListener('click', event => { if (event.target === modal) hide(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape') { closeMenus(); if (!modal.hidden) hide(); } });
    })();
    </script>
</x-seller.layout>
