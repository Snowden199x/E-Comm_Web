// Seller · Messages: chat search, attachment chip, auto-grow, Ctrl+Enter, flash banner.
const search = document.getElementById('smSearch');
const list = document.getElementById('sellerCustomerChats');
const noMatch = document.getElementById('smNoMatch');

function applyFilter() {
    if (!search || !list) return;
    const query = search.value.trim().toLowerCase();
    const entries = [...list.querySelectorAll('.sm-entry')];
    let shown = 0;
    entries.forEach(entry => {
        const match = !query || entry.textContent.toLowerCase().includes(query);
        entry.hidden = !match;
        if (match) shown += 1;
    });
    if (noMatch) noMatch.hidden = !(query && entries.length > 0 && shown === 0);
}
search?.addEventListener('input', applyFilter);
// The list re-renders every few seconds. Keep the filter applied.
if (list) new MutationObserver(applyFilter).observe(list, { childList: true });

// Attachment chip with size check (server limit is 5 MB).
document.addEventListener('change', event => {
    const input = event.target.closest('[data-file-input]');
    if (!input) return;
    const form = input.closest('form');
    const chip = form.querySelector('[data-file-chip]');
    const error = form.querySelector('.sw-error');
    const file = input.files[0];
    if (file && file.size > 5 * 1024 * 1024) {
        input.value = '';
        chip.hidden = true;
        if (error) { error.textContent = 'File is too large. Choose a JPG, PNG, WEBP, or PDF under 5 MB.'; error.hidden = false; }
        return;
    }
    if (error) error.hidden = true;
    chip.hidden = !file;
    if (file) chip.querySelector('[data-file-name]').textContent = file.name;
});
document.addEventListener('click', event => {
    const clear = event.target.closest('[data-file-clear]');
    if (!clear) return;
    const form = clear.closest('form');
    form.querySelector('[data-file-input]').value = '';
    form.querySelector('[data-file-chip]').hidden = true;
});
document.addEventListener('reset', event => {
    const chip = event.target.querySelector?.('[data-file-chip]');
    if (chip) chip.hidden = true;
}, true);

// Auto-grow the composer and send with Ctrl+Enter.
document.addEventListener('input', event => {
    const field = event.target.closest('.sw-inbox__composer textarea');
    if (!field) return;
    field.style.height = 'auto';
    field.style.height = Math.min(field.scrollHeight, 180) + 'px';
});
document.addEventListener('keydown', event => {
    if (event.key !== 'Enter' || !(event.ctrlKey || event.metaKey)) return;
    const field = event.target.closest('.sw-inbox__composer textarea');
    if (field) { event.preventDefault(); field.form.requestSubmit(); }
});

// Flash message becomes a banner.
const flash = document.querySelector('[data-flash]');
if (flash && typeof window.sellerBanner === 'function') window.sellerBanner({ type: 'success', message: flash.dataset.flash });
