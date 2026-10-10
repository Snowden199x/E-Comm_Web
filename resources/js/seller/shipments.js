// Seller > Shipments helpers. Loaded after operations.js, which owns the details panel
// and the form submissions inside it (see data-panel links and [data-operation] forms).

async function copyText(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }
    const field = Object.assign(document.createElement('textarea'), { value: text });
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.append(field);
    field.select();
    document.execCommand('copy');
    field.remove();
}

document.addEventListener('click', async event => {
    // Copy tracking number
    const copy = event.target.closest('[data-sh-copy]');
    if (copy) {
        event.stopPropagation();
        const original = copy.dataset.shLabel ?? copy.textContent;
        copy.dataset.shLabel = original;
        try {
            await copyText(copy.dataset.shCopy);
            copy.textContent = 'Copied';
            copy.classList.add('is-copied');
        } catch {
            copy.textContent = 'Copy failed';
        }
        setTimeout(() => { copy.textContent = original; copy.classList.remove('is-copied'); }, 1600);
        return;
    }

    // Clicking anywhere on a row opens its details, like the Orders table
    const row = event.target.closest('li.sh-card[data-record]');
    if (row && !event.target.closest('a, button, summary, select, input, textarea, label')) {
        row.querySelector('a[data-panel]')?.click();
    }

    // Close the date popover when clicking elsewhere
    document.querySelectorAll('[data-sh-date][open]').forEach(details => {
        if (!details.contains(event.target)) details.open = false;
    });

    // Custom date range
    const apply = event.target.closest('[data-sh-date-apply]');
    if (apply) {
        const from = document.querySelector('[data-sh-date-from]').value;
        const to = document.querySelector('[data-sh-date-to]').value;
        if (from && to && from > to) {
            document.querySelector('[data-sh-date-to]').setCustomValidity('End date must be on or after the start date.');
            document.querySelector('[data-sh-date-to]').reportValidity();
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('page');
        from ? url.searchParams.set('date_from', from) : url.searchParams.delete('date_from');
        to ? url.searchParams.set('date_to', to) : url.searchParams.delete('date_to');
        window.location.assign(url);
    }
});

document.addEventListener('input', event => {
    if (event.target.matches('[data-sh-date-to]')) event.target.setCustomValidity('');
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') document.querySelectorAll('[data-sh-date][open]').forEach(details => { details.open = false; });
    // Keyboard access to rows: Enter opens the details panel
    const row = event.target.closest?.('li.sh-card[data-record]');
    if (event.key === 'Enter' && row && event.target === row) row.querySelector('a[data-panel]')?.click();
});

// Items-per-page (shown only when the controller supplies $perPageOptions)
document.querySelector('[data-sh-perpage]')?.addEventListener('change', event => {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', event.target.value);
    url.searchParams.delete('page');
    window.location.assign(url);
});

// Courier filter applies immediately; emptying the search box (the "x") clears the search.
document.querySelectorAll('[data-sh-autosubmit]').forEach(select => select.addEventListener('change', () => select.form.requestSubmit()));
document.querySelector('[data-sh-search]')?.addEventListener('search', event => {
    if (event.target.value === '') event.target.form.requestSubmit();
});