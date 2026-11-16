// Seller · Returns: filters without a full reload, one-click row details, confirm and decline dialogs, banners.
const page = document.querySelector('[data-rt-page]');
const banner = (options) => { if (typeof window.sellerBanner === 'function') window.sellerBanner(options); };

if (page) {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

    // Messages from the server become top banners.
    $$('[data-rt-flash]').forEach((el) => banner({ type: 'success', message: el.dataset.rtFlash }));
    $$('[data-rt-error]').forEach((el) => banner({ type: 'error', title: 'That did not work', message: el.dataset.rtError }));

    /* ---------- Filters, chips and paging ---------- */
    const filters = $('#rtFilters');
    let controller = null;
    const card = () => $('#rtCard');
    const showError = (url) => {
        const body = $('.rt-real', card());
        card().classList.remove('is-loading');
        body.innerHTML = '<tr><td colspan="5" class="do-empty-cell"><div class="do-empty" role="alert">'
            + '<span class="do-empty__icon do-tone-rose"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/></svg></span>'
            + '<strong>Could not load returns</strong><p>Check your connection, then select Retry.</p>'
            + '<button type="button" class="do-btn do-btn--primary" data-rt-retry>Retry</button></div></td></tr>';
        $('[data-rt-retry]', body).addEventListener('click', () => load(url, false));
    };
    const load = async (url, push = true) => {
        controller?.abort();
        controller = new AbortController();
        card().classList.add('is-loading');
        try {
            const response = await fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' }, credentials: 'same-origin' });
            if (response.status === 419 || response.status === 401) throw new Error('Your session expired. Reload the page and sign in again.');
            if (!response.ok) throw new Error('Vendo could not load returns. Try again in a moment.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (!$('#rtCard', doc)) throw new Error('Vendo could not load returns. Reload the page and try again.');
            card().innerHTML = $('#rtCard', doc).innerHTML;
            $('#rtChips').innerHTML = $('#rtChips', doc).innerHTML;
            const search = $('#rtFilters input[name=status]', doc);
            let hidden = $('input[name=status]', filters);
            if (search && !hidden) { hidden = Object.assign(document.createElement('input'), { type: 'hidden', name: 'status' }); filters.prepend(hidden); }
            if (hidden) hidden.value = search ? search.value : '';
            if (hidden && !search) hidden.remove();
            if (push) history.pushState({ rt: true }, '', url);
        } catch (error) {
            if (error.name === 'AbortError') return;
            showError(url);
            banner({ type: 'error', title: 'Returns did not load', message: /session|Vendo/.test(error.message) ? error.message : 'Check your connection, then select Retry.' });
        }
    };
    const submitFilters = () => {
        const params = new URLSearchParams(new FormData(filters));
        [...params.keys()].forEach((key) => { if (!params.get(key)) params.delete(key); });
        load(filters.action + (params.toString() ? '?' + params : ''));
    };
    filters.addEventListener('submit', (event) => { event.preventDefault(); submitFilters(); });
    $('[data-rt-auto]', filters)?.addEventListener('change', submitFilters);
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-rt-nav], .ops-pagination a');
        if (!link || !page.contains(link) || event.metaKey || event.ctrlKey || event.button !== 0) return;
        event.preventDefault();
        load(link.href);
    });
    window.addEventListener('popstate', () => load(location.href, false));

    /* ---------- Dialogs ---------- */
    const panel = $('#rtPanel'); const confirmDialog = $('#rtConfirm'); const decline = $('#rtDecline');
    const dialogs = [panel, confirmDialog, decline];
    dialogs.forEach((dialog) => {
        $$('[data-rt-close]', dialog).forEach((b) => b.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    });

    const openConfirm = (row, kind) => {
        const form = $('form', confirmDialog);
        const approve = kind === 'approve';
        form.action = approve ? row.dataset.approveUrl : row.dataset.receivedUrl;
        $('[data-rt-title]', confirmDialog).textContent = approve ? `Approve return for ${row.dataset.order}?` : `Mark ${row.dataset.order} as received?`;
        $('[data-rt-text]', confirmDialog).textContent = approve
            ? `The stock goes back and a ${row.dataset.refund} refund is recorded. You can't undo this.`
            : 'Only do this once the parcel is in your hands. The buyer is notified.';
        $('[data-rt-submit]', confirmDialog).textContent = approve ? 'Approve return' : 'Mark received';
        $('[data-rt-close]:not(.rt-dialog__close)', confirmDialog).textContent = approve ? 'Keep waiting' : 'Not yet';
        panel.close();
        confirmDialog.showModal();
    };
    const note = $('#rtNote'); const noteField = note.closest('.rt-field'); const noteError = $('[data-rt-note-error]', decline); const count = $('[data-rt-count]', decline);
    const updateCount = () => { count.textContent = `${note.value.length} / ${note.maxLength}`; };
    note.addEventListener('input', () => { updateCount(); noteField.classList.remove('has-error'); noteError.hidden = true; });
    $$('[data-rt-quick]', decline).forEach((b) => b.addEventListener('click', () => { note.value = b.dataset.rtQuick; note.dispatchEvent(new Event('input')); note.focus(); }));
    const openDecline = (row, text = '') => {
        const form = $('form', decline);
        form.action = row.dataset.rejectUrl;
        form.elements.return_id.value = row.dataset.rtRow;
        $('[data-rt-text]', decline).textContent = `${row.dataset.buyer} sees your reason for ${row.dataset.order}.`;
        note.value = text; updateCount();
        panel.close();
        decline.showModal();
        note.focus();
    };
    $('form', decline).addEventListener('submit', (event) => {
        const length = note.value.trim().length;
        const message = length === 0 ? 'Reason is empty. Tell the buyer why you are declining the return.' : (length < 5 ? 'Reason is too short. Use at least 5 characters, for example what you found on the item.' : '');
        if (message) {
            event.preventDefault();
            noteError.textContent = message; noteError.hidden = false; noteField.classList.add('has-error'); note.focus();
        }
    });

    // Spinner on the button while a decision is saved, and no double submit.
    $$('form[data-rt-form]').forEach((form) => form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const button = $('[data-rt-submit]', form);
        setTimeout(() => { button.classList.add('is-loading'); button.disabled = true; }, 0);
    }));
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) $$('[data-rt-submit]').forEach((b) => { b.classList.remove('is-loading'); b.disabled = false; });
    });

    // Row buttons and row click. Delegated, so rows that load later work too.
    document.addEventListener('click', (event) => {
        const row = event.target.closest('.rt-row');
        if (!row || !page.contains(row)) return;
        if (event.target.closest('[data-rt-approve]')) return openConfirm(row, 'approve');
        if (event.target.closest('[data-rt-decline]')) return openDecline(row);
        if (event.target.closest('[data-rt-receive]')) return openConfirm(row, 'receive');
        openDetails(row);
    });
    document.addEventListener('keydown', (event) => {
        const row = event.target.closest?.('.rt-row');
        if (row && event.target === row && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); openDetails(row); }
    });
    function openDetails(row) {
        const template = $('template[data-rt-detail]', row);
        if (!template) return;
        $('#rtPanelBody').replaceChildren(template.content.cloneNode(true));
        $('#rtPanelTitle').textContent = `Return ${row.dataset.order}`;
        panel.dataset.row = row.dataset.rtRow;
        if (!panel.open) panel.showModal();
    }
    // Buttons inside the details panel act on the same row.
    $('#rtPanelBody').addEventListener('click', (event) => {
        const row = $(`.rt-row[data-rt-row="${panel.dataset.row}"]`);
        if (!row) return;
        if (event.target.closest('[data-rt-approve]')) openConfirm(row, 'approve');
        else if (event.target.closest('[data-rt-decline]')) openDecline(row);
        else if (event.target.closest('[data-rt-receive]')) openConfirm(row, 'receive');
    });

    // Opened from a notification (?return=ID): scroll to the return, highlight it and open its details.
    const focus = $('[data-rt-focus]');
    if (focus) {
        const row = $(`.rt-row[data-rt-row="${focus.dataset.rtFocus}"]`);
        if (row) {
            if (!row.closest('table[hidden]')) {
                row.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                row.classList.add('is-focus');
                setTimeout(() => row.classList.remove('is-focus'), 4000);
            }
            openDetails(row);
        }
        // Drop ?return= so a refresh or a new filter does not reopen it.
        const url = new URL(location.href);
        if (url.searchParams.has('return')) { url.searchParams.delete('return'); history.replaceState(history.state, '', url); }
    }

    // After a server error on Decline, reopen the dialog with what was typed.
    const reopen = $('[data-rt-reopen]');
    if (reopen) {
        const row = $(`.rt-row[data-rt-row="${reopen.dataset.rtReopen}"]`);
        if (row) openDecline(row, reopen.dataset.rtNote || '');
    }
}
