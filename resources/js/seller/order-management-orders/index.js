/* Vendo Seller – Orders page: filters, list refresh, order drawer and order actions. */
(() => {
    'use strict';

    const app = document.getElementById('omoApp');
    if (!app) return;

    const el = id => document.getElementById(id);
    const initial = JSON.parse(el('omoInitial').textContent);
    const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isOverlay = () => true; // the details drawer is a wide slide-over at every screen size (see orders-enhance.css)

    const STATUS_LABELS = {
        all: 'All status', new: 'New', pack: 'To Pack', pickup: 'Ready for Pickup', pending: 'Pending Delivery',
        completed: 'Delivered / Completed', cancelled: 'Cancelled',
    };
    const ACTION_MESSAGES = {
        accept: 'Order accepted.', decline: 'Order declined.', prepare: 'Order moved to packing.',
        ready: 'Order marked ready for pickup.', cancel: 'Order cancelled.',
    };

    const state = { status: 'all', search: '', date_from: '', date_to: '', page: 1, ...initial.filters };
    let pagination = initial.pagination;
    let currentOrder = null;
    let drawerHtml = null;
    let rowsHtml = el('omoTableBody').innerHTML;
    let listSeq = 0;
    let drawerSeq = 0;
    let saving = false;
    let searchTimer;
    let toastTimer;
    let lastTrigger = null;

    /* ------------------------------------------------------------ helpers */
    function showError(message, id = 'omoError') {
        const node = el(id);
        if (!node) return;
        node.textContent = message;
        node.hidden = !message;
    }

    function toast(message) {
        const box = el('omoToast');
        box.textContent = message;
        box.classList.add('is-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => box.classList.remove('is-show'), 3500);
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                ...options.headers,
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Could not load orders. Please try again.');
        }
        return data;
    }

    const hasActiveFilters = () => state.status !== 'all' || state.search !== '' || state.date_from !== '' || state.date_to !== '';

    function markUpdated() {
        const now = new Date();
        el('omoUpdated').textContent = `Updated ${now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
    }

    /* --------------------------------------------------------- UI syncing */
    function setCounts(counts) {
        if (!counts) return;
        document.querySelectorAll('[data-count]').forEach(node => {
            const next = Number(counts[node.dataset.count] || 0).toLocaleString();
            if (node.textContent === next) return;
            node.textContent = next;
            if (reduceMotion()) return;
            node.classList.remove('is-bump');
            void node.offsetWidth; // restart the animation
            node.classList.add('is-bump');
        });
    }

    function sync(counts) {
        setCounts(counts);

        document.querySelectorAll('#omoTabs .omo-tab').forEach(node => node.classList.toggle('is-active', node.dataset.status === state.status));
        document.querySelectorAll('.omo-status-option').forEach(node => node.classList.toggle('is-selected', node.dataset.status === state.status));
        document.querySelectorAll('.omo-stat').forEach(node => {
            const on = node.dataset.filter === state.status;
            node.classList.toggle('is-active', on);
            node.setAttribute('aria-pressed', String(on));
        });
        el('omoStatusLabel').textContent = STATUS_LABELS[state.status] || STATUS_LABELS.all;

        el('omoResultCount').textContent = `Showing ${pagination.from || 0}–${pagination.to || 0} out of ${pagination.total} entries`;
        el('omoTableFoot').hidden = pagination.total === 0;
        el('omoPrevPage').disabled = pagination.page <= 1;
        el('omoNextPage').disabled = pagination.page >= pagination.last;

        const pages = el('omoPagerPages');
        pages.replaceChildren();
        for (let page = Math.max(1, pagination.page - 2); page <= Math.min(pagination.last, pagination.page + 2); page++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = page;
            button.classList.toggle('is-active', page === pagination.page);
            if (page === pagination.page) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => { state.page = page; load(); });
            pages.append(button);
        }

        el('omoSearchClear').hidden = state.search === '';
        el('omoClearFilters').hidden = !hasActiveFilters();
        highlightRows();
    }

    function highlightRows() {
        document.querySelectorAll('.omo-row').forEach(row => row.classList.toggle('is-selected', Number(row.dataset.id) === currentOrder));
        document.querySelectorAll('.omo-view-btn').forEach(node => node.classList.toggle('is-active', Number(node.dataset.id) === currentOrder));
    }

    /* --------------------------------------------------------- list loading */
    // `silent` refreshes (the 3 second poll) never animate or flash the loading bar.
    async function load(silent = false) {
        const seq = ++listSeq;
        const card = el('omoCard');
        if (!silent) { card.classList.add('is-loading'); card.setAttribute('aria-busy', 'true'); }
        try {
            const params = new URLSearchParams(Object.entries(state).filter(([, value]) => value !== '' && value != null));
            const data = await api(`${app.dataset.endpoint}?${params}`);
            if (seq !== listSeq) return;

            if (data.html !== rowsHtml) {
                const body = el('omoTableBody');
                body.classList.toggle('is-enter', !silent);
                body.innerHTML = data.html;
                rowsHtml = data.html;
            }
            pagination = data.pagination;
            sync(data.counts);
            markUpdated();
            showError('');
            history.replaceState(null, '', `${app.dataset.endpoint}?${params}`);
        } catch (error) {
            if (seq === listSeq && !silent) showError(error.message);
        } finally {
            if (seq === listSeq) { card.classList.remove('is-loading'); card.setAttribute('aria-busy', 'false'); }
        }
    }

    /* ----------------------------------------------------------- the drawer */
    async function openOrder(id, trigger = null) {
        const seq = ++drawerSeq;
        if (trigger) lastTrigger = trigger;
        currentOrder = Number(id);
        el('omoLayout').classList.add('is-open');
        el('omoBackdrop').classList.toggle('is-show', isOverlay());
        el('omoDrawer').setAttribute('aria-hidden', 'false');
        el('omoDrawer').innerHTML = '<div class="omo-drawer__inner"><div class="omo-drawer__loading" aria-live="polite"><span class="omo-skel" style="width:46%"></span><span class="omo-skel" style="width:78%"></span><span class="omo-skel" style="width:62%"></span><span class="omo-skel" style="width:90%"></span></div></div>';
        highlightRows();
        try {
            const data = await api(`${app.dataset.orderBase}/${id}`);
            if (seq !== drawerSeq) return;
            el('omoDrawer').innerHTML = data.html;
            drawerHtml = data.html;
            highlightRows();
            if (trigger) el('omoDrawerClose')?.focus({ preventScroll: true });
        } catch (error) {
            if (seq === drawerSeq) { closeOrder(true); showError(error.message); }
        }
    }

    function closeOrder(force = false) {
        if (saving && !force) return;
        drawerSeq++;
        currentOrder = null;
        drawerHtml = null;
        el('omoLayout').classList.remove('is-open');
        el('omoBackdrop').classList.remove('is-show');
        el('omoDrawer').setAttribute('aria-hidden', 'true');
        highlightRows();
        lastTrigger?.focus?.({ preventScroll: true });
        lastTrigger = null;
    }

    function resetCancelModal(modal) {
        modal.hidden = true;
        const reason = modal.querySelector('[data-omo-cancel-reason]');
        const details = modal.querySelector('[data-omo-cancel-details]');
        reason.required = false; reason.value = '';
        details.required = false; details.value = '';
    }

    el('omoTableBody').addEventListener('click', event => {
        if (saving) return;
        if (event.target.closest('[data-omo-clear]')) { clearFilters(); return; }
        const button = event.target.closest('.omo-view-btn');
        const row = !button && !event.target.closest('a') && event.target.closest('.omo-row');
        const id = button?.dataset.id || row?.dataset.id;
        if (!id) return;
        if (currentOrder === Number(id)) closeOrder();
        else openOrder(id, button || row?.querySelector('.omo-view-btn'));
    });

    el('omoBackdrop').addEventListener('click', () => closeOrder());

    el('omoDrawer').addEventListener('click', event => {
        const openButton = event.target.closest('[data-omo-cancel-open]');
        const modal = event.target.closest('[data-omo-cancel-modal]');
        const form = event.target.closest('#omoActionForm');

        if (event.target.closest('#omoDrawerClose')) closeOrder();

        if (openButton && form) {
            const cancelModal = form.querySelector('[data-omo-cancel-modal]');
            const reason = form.querySelector('[data-omo-cancel-reason]');
            const details = form.querySelector('[data-omo-cancel-details]');
            form.dataset.cancelAction = openButton.dataset.omoCancelOpen;
            cancelModal.querySelector('[data-omo-cancel-title]').textContent = form.dataset.cancelAction === 'decline' ? 'Decline this order?' : 'Cancel this order?';
            reason.required = true; reason.value = '';
            details.required = false; details.value = '';
            form.querySelector('[data-omo-cancel-details-wrap]').hidden = true;
            cancelModal.hidden = false;
            reason.focus();
        }

        if (event.target.closest('[data-omo-cancel-close]') || (modal && event.target === modal)) {
            const cancelModal = form?.querySelector('[data-omo-cancel-modal]') || event.target.closest('[data-omo-cancel-modal]');
            if (cancelModal) resetCancelModal(cancelModal);
        }
    });

    el('omoDrawer').addEventListener('change', event => {
        if (!event.target.matches('[data-omo-cancel-reason]')) return;
        const form = event.target.closest('#omoActionForm');
        const detailsWrap = form.querySelector('[data-omo-cancel-details-wrap]');
        const details = form.querySelector('[data-omo-cancel-details]');
        detailsWrap.hidden = event.target.value !== 'other';
        details.required = event.target.value === 'other';
        if (!details.required) details.value = '';
    });

    el('omoDrawer').addEventListener('submit', async event => {
        if (event.target.id !== 'omoActionForm') return;
        event.preventDefault();
        if (saving) return;

        const form = event.target;
        const isCancelSubmit = event.submitter?.hasAttribute('data-omo-confirm-cancel');
        const action = isCancelSubmit ? form.dataset.cancelAction : event.submitter?.value;
        if (!action) return;

        const body = Object.fromEntries(new FormData(form));
        body.action = action;
        if (['decline', 'cancel'].includes(action) && !body.reason?.trim()) {
            showError('Choose a reason before continuing.', 'omoActionError');
            form.querySelector('[data-omo-cancel-reason]').focus();
            return;
        }
        if (body.reason === 'other' && !body.reason_details?.trim()) {
            showError('Add details for the selected reason.', 'omoActionError');
            form.querySelector('[data-omo-cancel-details]').focus();
            return;
        }

        saving = true;
        showError('', 'omoActionError');
        const buttons = [...form.querySelectorAll('button')];
        buttons.forEach(button => { button.disabled = true; });
        event.submitter?.classList.add('is-loading');
        try {
            await api(form.dataset.url, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            form.dispatchEvent(new Event('vendo:draft-committed'));
            saving = false;
            toast(ACTION_MESSAGES[action] || 'Order updated.');
            await Promise.all([load(true), openOrder(currentOrder)]);
        } catch (error) {
            showError(error.message, 'omoActionError');
            buttons.forEach(button => { button.disabled = false; });
            event.submitter?.classList.remove('is-loading');
        } finally {
            saving = false;
        }
    });

    /* -------------------------------------------------------------- filters */
    function closePanels() {
        [['omoStatusPanel', 'omoStatusBtn'], ['omoDatePanel', 'omoDateBtn']].forEach(([panel, button]) => {
            el(panel).classList.remove('is-open');
            el(button).setAttribute('aria-expanded', 'false');
        });
    }

    function filter(status) {
        state.status = status || 'all';
        state.page = 1;
        closePanels();
        sync();
        load();
    }

    function setDates(from, to, label) {
        if (from && to && from > to) { showError('The end date must be on or after the start date.'); return; }
        state.date_from = from;
        state.date_to = to;
        state.page = 1;
        el('omoDateFrom').value = from;
        el('omoDateTo').value = to;
        el('omoDateLabel').textContent = label || (from || to ? `${from || 'Any'} – ${to || 'Any'}` : 'All Dates');
        closePanels();
        sync();
        load();
    }

    function clearFilters() {
        state.status = 'all'; state.search = ''; state.page = 1;
        el('omoSearchInput').value = '';
        setDates('', '', 'All Dates');
    }

    document.querySelectorAll('#omoTabs .omo-tab, .omo-status-option, [data-filter]').forEach(button => {
        button.addEventListener('click', () => filter(button.dataset.status || button.dataset.filter));
        button.addEventListener('keydown', event => {
            if (button.matches('.omo-status-option') && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); filter(button.dataset.status); }
        });
    });

    const searchInput = el('omoSearchInput');
    searchInput.value = state.search;
    searchInput.addEventListener('input', event => {
        state.search = event.target.value;
        state.page = 1;
        el('omoSearchClear').hidden = state.search === '';
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { sync(); load(); }, 250);
    });
    el('omoSearchClear').addEventListener('click', () => {
        searchInput.value = ''; state.search = ''; state.page = 1;
        sync(); load(); searchInput.focus();
    });
    el('omoClearFilters').addEventListener('click', clearFilters);

    [['omoStatusPanel', 'omoStatusBtn'], ['omoDatePanel', 'omoDateBtn']].forEach(([panel, button]) => {
        el(button).addEventListener('click', () => {
            const open = !el(panel).classList.contains('is-open');
            closePanels();
            el(panel).classList.toggle('is-open', open);
            el(button).setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', event => { if (!event.target.closest('.omo-filter')) closePanels(); });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        closePanels();
        const modal = el('omoDrawer').querySelector('[data-omo-cancel-modal]:not([hidden])');
        if (modal) { resetCancelModal(modal); return; }
        closeOrder();
    });

    const iso = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    document.querySelectorAll('.omo-date-preset').forEach(button => button.addEventListener('click', () => {
        const today = app.dataset.today;
        const date = new Date(`${today}T00:00:00`);
        if (button.dataset.preset === 'today') setDates(today, today, 'Today');
        else if (button.dataset.preset === '7days') { date.setDate(date.getDate() - 6); setDates(iso(date), today, 'Last 7 Days'); }
        else if (button.dataset.preset === 'month') { date.setDate(1); setDates(iso(date), today, 'This Month'); }
        else setDates('', '', 'All Dates');
    }));
    el('omoDateApply').addEventListener('click', () => setDates(el('omoDateFrom').value, el('omoDateTo').value));
    el('omoDateClear').addEventListener('click', () => setDates('', '', 'All Dates'));

    el('omoPrevPage').addEventListener('click', () => { if (state.page > 1) { state.page--; load(); } });
    el('omoNextPage').addEventListener('click', () => { if (state.page < pagination.last) { state.page++; load(); } });

    /* ---------------------------------------------------------------- start */
    el('omoDateFrom').value = state.date_from;
    el('omoDateTo').value = state.date_to;
    if (state.date_from || state.date_to) el('omoDateLabel').textContent = `${state.date_from || 'Any'} – ${state.date_to || 'Any'}`;
    sync(initial.counts);
    markUpdated();
    if (initial.filters.order) openOrder(initial.filters.order);

    // Keep the list and the open order fresh without disturbing typing or animations.
    setInterval(async () => {
        if (document.hidden || saving) return;
        await load(true);
        if (!currentOrder) return;
        const focused = document.activeElement;
        if (focused && focused.closest('#omoDrawer') && focused.matches('input,textarea,select')) return;
        try {
            const data = await api(`${app.dataset.orderBase}/${currentOrder}`);
            if (data.html !== drawerHtml) {
                el('omoDrawer').innerHTML = data.html;
                drawerHtml = data.html;
                highlightRows();
            }
        } catch (error) {
            showError(error.message);
        }
    }, 3000);
})();