// Seller · Delivered orders: auto-submit filters, row click, loading states, copy buttons.
const page = document.querySelector('.do-page');

function banner(options) {
    if (typeof window.sellerBanner === 'function') window.sellerBanner(options);
}

if (page) {
    const form = page.querySelector('[data-do-filters]');
    const card = page.querySelector('[data-do-table]');

    // Flash message from the server becomes a banner.
    const flash = page.querySelector('[data-flash]');
    if (flash) banner({ type: 'success', message: flash.dataset.flash });

    // Loading state: swap rows for skeletons while the next page loads.
    const showLoading = () => {
        if (!card || card.classList.contains('is-loading')) return;
        card.classList.add('is-loading');
        card.setAttribute('aria-busy', 'true');
        const columns = card.querySelectorAll('thead th').length || 7;
        const tbody = card.querySelector('tbody');
        const row = `<tr class="do-skel">${'<td><i></i></td>'.repeat(columns)}</tr>`;
        tbody.innerHTML = row.repeat(6);
        page.querySelectorAll('.do-filters [data-range-apply]').forEach(button => button.classList.add('is-loading'));
    };

    // Selects submit on change. Dates wait for "Apply dates" so a half-picked range never fires.
    form?.querySelectorAll('[data-auto-submit]').forEach(select => select.addEventListener('change', () => form.requestSubmit()));
    const applyDates = form?.querySelector('[data-range-apply]');
    form?.querySelectorAll('[data-range]').forEach(input => {
        input.addEventListener('change', () => {
            applyDates.hidden = ![...form.querySelectorAll('[data-range]')].some(field => field.value !== field.defaultValue);
        });
    });
    form?.addEventListener('submit', () => {
        // Skip empty fields so the URL stays clean.
        form.querySelectorAll('input, select').forEach(field => { if (field.value === '') field.disabled = true; });
        showLoading();
    });

    // Presets, clear links and pagination.
    page.addEventListener('click', event => {
        const link = event.target.closest('.do-chips a, .ops-pagination a, .do-alert a:not([data-no-load]), a[data-load]');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) showLoading();
    });

    // Whole row opens details.
    card?.addEventListener('click', event => {
        const row = event.target.closest('.do-row');
        if (!row || event.target.closest('a, button')) return;
        row.querySelector('a[data-panel], [data-cmp-open]')?.click();
    });
}

// Copy buttons (table + drawer).
document.addEventListener('click', async event => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    event.preventDefault();
    const label = button.dataset.copyLabel || 'Text';
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.classList.add('is-done');
        setTimeout(() => button.classList.remove('is-done'), 1500);
        banner({ type: 'success', message: `${label} copied.`, timeout: 2500 });
    } catch {
        banner({ type: 'error', message: `Couldn't copy. Select the ${label.toLowerCase()} and copy it by hand.` });
    }
});

// Back/forward cache can restore the skeleton. Reload to show real rows.
window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
