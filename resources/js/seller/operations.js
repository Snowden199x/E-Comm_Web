const drawer = document.getElementById('opsDrawer');
const content = document.getElementById('opsDrawerContent');
let pending;
let saving = false;
let lastTrigger;
let selectedRow;

const COLOR_OPTIONS = [
    'Black', 'White', 'Gray', 'Silver', 'Beige', 'Cream', 'Brown', 'Tan', 'Navy Blue',
    'Blue', 'Sky Blue', 'Teal', 'Turquoise', 'Green', 'Olive', 'Mint', 'Yellow', 'Gold',
    'Orange', 'Red', 'Maroon', 'Pink', 'Rose Gold', 'Purple', 'Lavender', 'Violet',
    'Peach', 'Coral', 'Ivory', 'Khaki', 'Burgundy', 'Mustard', 'Charcoal', 'Multi Color',
];

function showMessage(message) {
    const toast = document.getElementById('opsToast');
    toast.textContent = message;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, 6000);
}

function initProductForm(form) {
    const sizeCount = form.querySelector('[data-size-count]');
    const updateSizeCount = () => {
        const count = form.querySelectorAll('input[name="sizes[]"]:checked').length;
        sizeCount.textContent = count ? `${count} selected` : 'Optional';
    };
    form.querySelectorAll('input[name="sizes[]"]').forEach(input => input.addEventListener('change', updateSizeCount));

    const picker = form.querySelector('[data-color-picker]');
    const selected = picker.querySelector('[data-color-selected]');
    const popover = picker.querySelector('[data-color-popover]');
    const search = picker.querySelector('[data-color-search]');
    const results = picker.querySelector('[data-color-results]');
    const custom = picker.querySelector('[data-color-custom]');
    const selectedColors = () => [...selected.querySelectorAll('input[name="colors[]"]')].map(input => input.value.toLocaleLowerCase());
    const addColor = color => {
        const name = color.trim().replace(/\s+/g, ' ');
        if (!name || !/^[\p{L}][\p{L}\s-]{0,29}$/u.test(name)) return;
        if (selectedColors().includes(name.toLocaleLowerCase()) || selectedColors().length >= 10) return;
        const chip = document.createElement('span');
        chip.className = 'ops-color-chip';
        const label = document.createElement('span');
        label.textContent = name;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'colors[]';
        input.value = name;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.colorRemove = '';
        remove.setAttribute('aria-label', `Remove ${name}`);
        remove.textContent = '×';
        chip.append(label, input, remove);
        selected.append(chip);
        search.value = '';
        renderResults();
        search.focus();
    };
    function renderResults() {
        const query = search.value.trim().toLocaleLowerCase();
        const chosen = selectedColors();
        const matches = COLOR_OPTIONS.filter(color => color.toLocaleLowerCase().includes(query) && !chosen.includes(color.toLocaleLowerCase())).slice(0, 12);
        results.replaceChildren();
        matches.forEach(color => {
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('role', 'option');
            button.textContent = color;
            button.addEventListener('click', () => addColor(color));
            results.append(button);
        });
        const name = search.value.trim().replace(/\s+/g, ' ');
        custom.hidden = !name || !/^[\p{L}][\p{L}\s-]{0,29}$/u.test(name) || chosen.includes(name.toLocaleLowerCase()) || chosen.length >= 10;
        custom.textContent = `Add "${name}"`;
        if (chosen.length >= 10) results.replaceChildren(Object.assign(document.createElement('small'), { textContent: 'Maximum of 10 colors selected.' }));
    }
    picker.querySelector('[data-color-open]').addEventListener('click', () => {
        popover.hidden = !popover.hidden;
        if (!popover.hidden) { renderResults(); search.focus(); }
    });
    selected.addEventListener('click', event => {
        if (event.target.closest('[data-color-remove]')) {
            event.target.closest('.ops-color-chip').remove();
            renderResults();
        }
    });
    search.addEventListener('input', renderResults);
    search.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const first = results.querySelector('button');
            if (first) first.click(); else if (!custom.hidden) custom.click();
        }
    });
    custom.addEventListener('click', () => addColor(search.value));

    const photos = form.querySelector('[data-photo-field]');
    photos.querySelectorAll('[data-remove-photo]').forEach(input => input.addEventListener('change', () => validateProductPhotos(form)));
    photos.querySelectorAll('input[type="file"]').forEach(input => input.addEventListener('change', () => validateProductPhotos(form)));
}

function validateProductPhotos(form) {
    if (!form.hasAttribute('data-product-form')) return true;
    const existing = [...form.querySelectorAll('[data-remove-photo]')];
    const main = form.querySelector('[data-main-photo]');
    const gallery = form.querySelector('[data-gallery-photos]');
    const newFiles = [...main.files, ...gallery.files];
    const removed = existing.filter(input => input.checked).length;
    const replacedMain = main.files.length && existing[0] && !existing[0].checked ? 1 : 0;
    const total = existing.length - removed - replacedMain + newFiles.length;
    let message = '';
    if (total < 1 || total > 6) message = 'Keep one main photo and no more than five additional photos.';
    else if (gallery.files.length > 5) message = 'Choose at most five additional photos.';
    else if (newFiles.some(file => file.size > 2 * 1024 * 1024)) message = 'Each photo must be 2 MB or less.';
    else if (newFiles.reduce((sum, file) => sum + file.size, 0) > 7 * 1024 * 1024) message = 'All new photos together must be 7 MB or less.';
    const error = form.querySelector('[data-photo-error]');
    error.textContent = message;
    error.hidden = !message;
    form.querySelector('[data-gallery-slots]').textContent = `${Math.max(0, 6 - (existing.length - removed - replacedMain) - main.files.length)} gallery slots available.`;
    return !message;
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
        const productForm = content.querySelector('[data-product-form]');
        if (productForm) initProductForm(productForm);
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
    if (!validateProductPhotos(form)) return;
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
