// Seller Compliance: shared table controller for the Overview, Products for Review,
// Warnings, Violations and Suspended Sellers screens.
//
// Each screen wraps its toolbar and table region in x-data="scTable({...})".
// Search, filters and paging refresh only the table region (the existing
// *-table partial routes) with a loading bar, request cancelling and a row reveal.
// No backend change: it calls the same routes and query parameters as before.

document.addEventListener('alpine:init', () => {
    Alpine.data('scTable', (config = {}) => {
        const defaults = {
            search: '',
            category_id: '',
            date_filter: 'all',
            custom_date: '',
            ...(config.defaults || {}),
        };

        return {
            url: config.url, // partial endpoint, e.g. admin.seller-compliance.sellers-table
            pageUrl: config.pageUrl || window.location.pathname,
            f: { ...defaults, ...(config.filters || {}) },
            page: Number(config.page || 1),
            categories: config.categories || [],

            loading: false,
            failed: false,
            catOpen: false,
            dateOpen: false,
            timer: null,
            ctrl: null,

            // Dialog state. The dialogs themselves are rendered inside the swapped region.
            sellerId: null, // seller whose products popup is open (Overview tab)
            detailId: null, // warning / violation / suspended-seller row whose details popup is open
            openProductId: null,
            rejectId: null,
            warnId: null,
            activateId: null,
            confirmation: config.confirmation || null,

            get anyDialog() {
                return !!(this.sellerId || this.detailId || this.openProductId || this.rejectId || this.warnId || this.activateId || this.confirmation);
            },

            closeDialogs() {
                this.sellerId = this.detailId = this.openProductId = this.rejectId = this.warnId = this.activateId = null;
                this.confirmation = null;
            },

            get filtered() {
                return Object.keys(defaults).some(key => String(this.f[key] ?? '').trim() !== String(defaults[key]));
            },

            get categoryLabel() {
                const found = this.categories.find(c => String(c.id) === String(this.f.category_id));
                return found ? found.name : 'All categories';
            },

            get dateLabel() {
                const labels = { all: 'All dates', today: 'Today', week: 'This week', month: 'This month' };
                if (this.f.date_filter === 'custom' && this.f.custom_date) {
                    const [y, m, d] = this.f.custom_date.split('-').map(Number);
                    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
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
                this.catOpen = false;
                this.dateOpen = false;
                this.apply();
            },

            pickCategory(id) {
                this.f.category_id = id;
                this.catOpen = false;
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
                    this.closeDialogs();
                    region.innerHTML = html;
                    region.classList.remove('sc-fresh');
                    void region.offsetWidth; // restart the row animation
                    region.classList.add('sc-fresh');

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