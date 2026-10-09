// Reports page (Admin). Registered as an Alpine component from layout.js, so it works whether the page is
// opened directly or swapped in by the in-app navigation (the old version ran once at load and could miss the swap).
//
// UI pass 2 (7 Oct): period changes refresh in place (no full reload), one chart toggles Sales / Commission,
// the seller table sorts and searches in the browser, and Preview / Download / CSV always follow what is on screen.
// No server change: it reads the same `admin.reports.index` page and the existing preview / download routes.

document.addEventListener('alpine:init', () => {
    window.Alpine.data('reportsPage', (cfg) => ({
        // ----- Period -----------------------------------------------------------
        view: cfg.view,
        dateFilter: cfg.dateFilter,
        customDate: cfg.customDate,
        year: cfg.year,
        periods: [
            { v: 'today', l: 'Today' },
            { v: 'week', l: 'This week' },
            { v: 'month', l: 'This month' },
            { v: 'custom', l: 'Pick a day' },
            { v: 'year', l: 'Full year' },
        ],

        // ----- Page state ---------------------------------------------------------
        loading: false,
        failed: false,
        ctrl: null,
        metric: 'sales', // chart: 'sales' | 'commission'
        chart: null,
        report: { labels: [], sales: [], commission: [], sellers: [], rate: 0, periodLabel: '' },

        // Seller table
        q: '',
        sortKey: 'sales',
        sortDir: 'desc',

        // Preview dialog
        previewOpen: false,
        previewLoading: false,
        previewFailed: false,
        previewHtml: '',

        // The server renders #rp-region before Alpine starts, so it can be read straight away
        // (refs are not filled in yet while init() runs, which is why this does not use x-ref).
        get region() {
            return document.getElementById('rp-region');
        },

        init() {
            this.readRegion();
            this.$nextTick(() => this.renderChart());
            this.$watch('metric', () => this.renderChart());
        },
        destroy() {
            if (this.chart) this.chart.destroy();
        },

        // ----- Period controls -------------------------------------------------------
        get period() {
            return this.view === 'monthly' ? 'year' : this.dateFilter;
        },
        setPeriod(p) {
            if (p === 'year') {
                this.view = 'monthly';
            } else {
                this.view = 'daily';
                this.dateFilter = p;
                if (p === 'custom' && !this.customDate) this.customDate = new Date().toISOString().slice(0, 10);
            }
            this.load();
        },
        params() {
            const p = new URLSearchParams();
            if (this.view === 'monthly') {
                p.set('view', 'monthly');
                p.set('year', this.year);
            } else {
                p.set('view', 'daily');
                p.set('date_filter', this.dateFilter);
                if (this.dateFilter === 'custom') p.set('custom_date', this.customDate);
            }
            return p;
        },
        get downloadHref() {
            return cfg.downloadUrl + '?' + this.params().toString();
        },

        // ----- Loading ---------------------------------------------------------------
        readRegion() {
            const node = this.region?.querySelector('[data-report]');
            if (!node) return;
            try {
                this.report = JSON.parse(node.dataset.report);
            } catch (e) { /* keep the previous data */ }
        },
        async load() {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;

            const url = cfg.indexUrl + '?' + this.params().toString();
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: ctrl.signal });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('rp-region');
                if (!next) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                if (this.chart) { this.chart.destroy(); this.chart = null; } // its canvas is about to be replaced
                const region = this.region;
                region.innerHTML = next.innerHTML;
                this.readRegion();
                this.q = '';
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');
                this.$nextTick(() => this.renderChart());

                history.replaceState(null, '', url);
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.failed = true;
            } finally {
                if (this.ctrl === ctrl) { this.loading = false; this.ctrl = null; }
            }
        },

        // ----- Chart -----------------------------------------------------------------
        peso(n) {
            return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        pesoShort(n) {
            n = Number(n || 0);
            if (n >= 1000000) return '₱' + (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            if (n >= 1000) return '₱' + (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
            return '₱' + n;
        },
        renderChart() {
            const canvas = this.region?.querySelector('canvas[data-main-chart]');
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (!canvas || typeof Chart === 'undefined') return;

            const isSales = this.metric === 'sales';
            const color = isSales ? '#5b2963' : '#15803d';
            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: this.report.labels,
                    datasets: [{
                        label: isSales ? 'Sales' : 'Commission',
                        data: isSales ? this.report.sales : this.report.commission,
                        backgroundColor: color,
                        hoverBackgroundColor: isSales ? '#3b1735' : '#166534',
                        borderRadius: 6,
                        maxBarThickness: 36,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 350 },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (c) => ' ' + this.peso(c.parsed.y) } },
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f3edf4' }, ticks: { callback: (v) => this.pesoShort(v) } },
                        x: { grid: { display: false } },
                    },
                },
            });
        },

        // ----- Seller table ----------------------------------------------------------
        get totalSales() {
            return this.report.sellers.reduce((s, r) => s + Number(r.sales), 0);
        },
        get totalCommission() {
            return this.report.sellers.reduce((s, r) => s + Number(r.commission), 0);
        },
        get rows() {
            const total = this.totalSales;
            const q = this.q.trim().toLowerCase();
            // The server sends sellers ordered by sales, so the original position is the rank (it stays put while sorting/searching).
            const list = this.report.sellers
                .map((r, i) => ({ ...r, rank: i + 1, share: total > 0 ? (Number(r.sales) / total) * 100 : 0 }))
                .filter((r) => !q || r.name.toLowerCase().includes(q));
            const dir = this.sortDir === 'asc' ? 1 : -1;
            return list.sort((a, b) => {
                const x = a[this.sortKey], y = b[this.sortKey];
                return (typeof x === 'string' ? x.localeCompare(y) : Number(x) - Number(y)) * dir;
            });
        },
        sortBy(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = key === 'name' ? 'asc' : 'desc';
            }
        },
        ariaSort(key) {
            return this.sortKey === key ? (this.sortDir === 'asc' ? 'ascending' : 'descending') : 'none';
        },

        // ----- Export ----------------------------------------------------------------
        exportCsv() {
            // Spreadsheet apps run text that starts with = + - @ as a formula, so those get a leading apostrophe.
            const cell = (v) => {
                let s = String(v ?? '');
                if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;
                return '"' + s.replace(/"/g, '""') + '"';
            };
            const lines = [
                ['Vendo report', this.report.periodLabel],
                ['Commission rate', this.report.rate + '%'],
                [],
                ['Rank', 'Seller', 'Sales (PHP)', 'Commission (PHP)', 'Share of sales (%)'],
                ...this.rows.map((r) => [r.rank, r.name, Number(r.sales).toFixed(2), Number(r.commission).toFixed(2), r.share.toFixed(1)]),
                [],
                ['', 'Total', this.totalSales.toFixed(2), this.totalCommission.toFixed(2), ''],
            ];
            const csv = '\ufeff' + lines.map((l) => l.map(cell).join(',')).join('\r\n');
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            a.download = 'vendo-report-' + String(this.report.periodLabel).replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        },

        // ----- Preview -----------------------------------------------------------------
        async openPreview() {
            this.previewOpen = true;
            this.previewLoading = true;
            this.previewFailed = false;
            this.previewHtml = '';
            try {
                const res = await fetch(cfg.previewUrl + '?' + this.params().toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.previewHtml = await res.text();
            } catch (e) {
                this.previewFailed = true;
            } finally {
                this.previewLoading = false;
            }
        },
    }));
});