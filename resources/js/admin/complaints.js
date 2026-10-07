// Complaints and Disputes list: search, filters, status cards and paging
// refresh only the table region (the existing admin.complaints.table route).
// Imported from resources/js/admin/layout.js so it registers before Alpine starts.
//
// status and kind are sent as query parameters. Until ComplaintController::filteredComplaints()
// honours them (see backend notes), the rows on the current page are also filtered here,
// so the tabs still behave sensibly instead of doing nothing.

document.addEventListener('alpine:init', () => {
    Alpine.data('casesPage', (config = {}) => {
        const defaults = { search: '', type: '', date_filter: 'all', custom_date: '', status: '', kind: '' };

        return {
            url: config.url,
            pageUrl: config.pageUrl || window.location.pathname,
            f: { ...defaults, ...(config.filters || {}) },
            page: Number(config.page || 1),

            loading: false,
            failed: false,
            dateOpen: false,
            typeOpen: false,
            timer: null,
            ctrl: null,

            drawerId: null, // case id (or 'sample') whose drawer is open
            sample: false, // sample case preview is shown
            clientEmpty: false, // every row on this page is hidden by the status/kind tabs

            init() {
                this.$nextTick(() => this.clientFilter());
            },

            get anyDialog() {
                return this.drawerId !== null;
            },

            get filtered() {
                return Object.keys(defaults).some(key => String(this.f[key] ?? '').trim() !== String(defaults[key]));
            },

            get typeLabel() {
                return this.f.type || 'All types';
            },

            get dateLabel() {
                const labels = { all: 'All dates', today: 'Today', week: 'This week', month: 'This month' };
                if (this.f.date_filter === 'custom' && this.f.custom_date) {
                    const [y, m, d] = this.f.custom_date.split('-').map(Number);
                    return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
                return labels[this.f.date_filter] || 'All dates';
            },

            params() {
                const p = new URLSearchParams();
                Object.keys(defaults).forEach(key => {
                    const value = String(this.f[key] ?? '').trim();
                    if (value !== '' && value !== String(defaults[key])) p.set(key, value);
                });
                if (this.page > 1) p.set('page', this.page);
                return p;
            },

            // Hides rows that do not match the status/kind tabs (fallback until the server does it).
            clientFilter() {
                const rows = this.$refs.region ? this.$refs.region.querySelectorAll('[data-case-row]') : [];
                let visible = 0;
                rows.forEach(row => {
                    const okStatus = !this.f.status || row.dataset.status === this.f.status;
                    const okKind = !this.f.kind || row.dataset.kind === this.f.kind;
                    row.hidden = !(okStatus && okKind);
                    if (!row.hidden) visible++;
                });
                this.clientEmpty = rows.length > 0 && visible === 0;
            },

            onSearch() {
                clearTimeout(this.timer);
                this.page = 1;
                this.timer = setTimeout(() => this.load(), 300);
            },

            apply() {
                clearTimeout(this.timer);
                this.page = 1;
                this.load();
            },

            clearAll() {
                this.f = { ...defaults };
                this.dateOpen = this.typeOpen = false;
                this.apply();
            },

            setStatus(value) {
                this.f.status = value;
                this.apply();
            },

            setKind(value) {
                this.f.kind = value;
                this.apply();
            },

            pickType(value) {
                this.f.type = value;
                this.typeOpen = false;
                this.apply();
            },

            pickDate(value) {
                this.f.date_filter = value;
                if (value !== 'custom') this.f.custom_date = '';
                this.dateOpen = false;
                this.apply();
            },

            pickCustomDate() {
                if (!this.f.custom_date) return;
                this.f.date_filter = 'custom';
                this.dateOpen = false;
                this.apply();
            },

            // Pagination buttons carry data-page; the region handles them by delegation.
            onRegionClick(event) {
                const btn = event.target.closest('[data-page]');
                if (!btn || btn.disabled) return;
                this.page = Number(btn.dataset.page);
                this.load(true);
            },

            async load(scrollToTable = false) {
                if (!this.url) return;
                if (this.ctrl) this.ctrl.abort();
                const ctrl = new AbortController();
                this.ctrl = ctrl;
                this.loading = true;
                this.failed = false;

                const qs = this.params().toString();
                try {
                    const res = await fetch(this.url + (qs ? '?' + qs : ''), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                        signal: ctrl.signal,
                    });

                    if (res.redirected && /login/i.test(res.url)) {
                        window.location.href = res.url;
                        return;
                    }
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const html = await res.text();
                    if (ctrl.signal.aborted) return;

                    const region = this.$refs.region;
                    this.drawerId = this.drawerId === 'sample' ? 'sample' : null;
                    region.innerHTML = html;
                    region.classList.remove('cs-fresh');
                    void region.offsetWidth; // restart the row animation
                    region.classList.add('cs-fresh');
                    this.$nextTick(() => this.clientFilter());

                    history.replaceState(null, '', this.pageUrl + (qs ? '?' + qs : ''));
                    if (scrollToTable) region.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } catch (e) {
                    if (e.name !== 'AbortError') this.failed = true;
                } finally {
                    if (this.ctrl === ctrl) this.loading = false;
                }
            },

            destroy() {
                clearTimeout(this.timer);
                if (this.ctrl) this.ctrl.abort();
            },
        };
    });
});