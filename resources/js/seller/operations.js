const drawer = document.getElementById('opsDrawer');
const content = document.getElementById('opsDrawerContent');
let pending;
let saving = false;
let lastTrigger;
let selectedRow;

function showMessage(message) {
    const toast = document.getElementById('opsToast');
    toast.textContent = message;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, 6000);
}
try {
    const message = sessionStorage.getItem('sellerOperationMessage');
    if (message) { showMessage(message); sessionStorage.removeItem('sellerOperationMessage'); }
} catch { /* The operation works even when browser storage is unavailable. */ }

async function openPanel(link) {
    if (saving) return;
    pending?.abort();
    const controller = new AbortController();
    pending = controller;
    if (!drawer.open) lastTrigger = link;
    document.querySelectorAll('.ops-menu[open]').forEach(menu => { menu.open = false; });
    selectedRow?.classList.remove('is-selected');
    selectedRow = link.closest('tr');
    selectedRow?.classList.add('is-selected');
    document.getElementById('opsPanelTitle').textContent = link.dataset.panelTitle || 'Details';
    content.replaceChildren(Object.assign(document.createElement('p'), { textContent: 'Loading…', className: 'ops-muted' }));
    if (!drawer.open) drawer.showModal();
    content.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(link.href, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
        if (response.redirected) { window.location.assign(response.url); return; }
        if (!response.ok) throw new Error(response.status === 404 ? 'This record is no longer available.' : 'Unable to open details. Please close and try again.');
        const html = await response.text();
        if (controller.signal.aborted) return;
        content.innerHTML = html;
        (content.querySelector('[autofocus]') || drawer.querySelector('[data-close-dialog]')).focus();
        drawer.scrollTop = 0;
    } catch (error) {
        if (error.name === 'AbortError') return;
        content.replaceChildren(Object.assign(document.createElement('p'), { className: 'ops-alert', role: 'alert', textContent: error.message }));
    } finally {
        if (pending === controller) content.removeAttribute('aria-busy');
    }
}

document.addEventListener('click', event => {
    const link = event.target.closest('a[data-panel]');
    if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
        event.preventDefault();
        openPanel(link);
        return;
    }
    const openButton = event.target.closest('[data-dialog-open]');
    if (openButton) document.getElementById(openButton.dataset.dialogOpen)?.showModal();
    if (event.target.closest('[data-close-dialog]') && !saving) event.target.closest('dialog')?.close();
    if (!event.target.closest('.ops-menu')) document.querySelectorAll('.ops-menu[open]').forEach(menu => { menu.open = false; });
});

document.querySelectorAll('dialog').forEach(dialog => {
    dialog.addEventListener('click', event => {
        if (event.target !== dialog || saving) return;
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
});
drawer.addEventListener('close', () => {
    pending?.abort();
    selectedRow?.classList.remove('is-selected');
    lastTrigger?.focus();
});

document.addEventListener('submit', async event => {
    const form = event.target.closest('form[data-operation]');
    if (!form) return;
    event.preventDefault();
    if (saving) return;
    const errorBox = form.querySelector('[data-form-error]');
    errorBox.hidden = true;
    const body = new FormData(form);
    if (event.submitter?.name) body.set(event.submitter.name, event.submitter.value);
    const buttons = [...drawer.querySelectorAll('button')];
    const states = buttons.map(button => button.disabled);
    buttons.forEach(button => { button.disabled = true; });
    saving = true;
    form.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
            throw new Error(response.status === 419 ? 'Your session expired. Reload the page and sign in again.' : messages || 'Unable to save. Please try again.');
        }
        try { sessionStorage.setItem('sellerOperationMessage', data.message || 'Saved.'); } catch { /* Optional success notice. */ }
        window.location.reload();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
        errorBox.scrollIntoView({ block: 'nearest' });
        buttons.forEach((button, index) => { button.disabled = states[index]; });
        saving = false;
        form.removeAttribute('aria-busy');
    }
});

// Fixed dropdown placement keeps row actions accessible inside scrolling tables.
document.querySelectorAll('.ops-menu').forEach(menu => menu.addEventListener('toggle', () => {
    if (!menu.open) return;
    document.querySelectorAll('.ops-menu[open]').forEach(other => { if (other !== menu) other.open = false; });
    const rect = menu.querySelector('summary').getBoundingClientRect();
    const panel = menu.querySelector('div');
    panel.style.left = `${Math.max(8, Math.min(rect.right - 190, window.innerWidth - 198))}px`;
    panel.style.top = `${Math.min(rect.bottom + 5, window.innerHeight - panel.offsetHeight - 10)}px`;
}));
window.addEventListener('scroll', () => document.querySelectorAll('.ops-menu[open]').forEach(menu => { menu.open = false; }), true);
