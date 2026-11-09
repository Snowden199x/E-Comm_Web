// Seller · Reports: preview dialog and PDF download feedback.
const dialog = document.getElementById('rpDialog');
const content = document.getElementById('rpContent');
let lastUrl = '';

const skeleton = '<div class="rp-skeleton" role="status" aria-label="Loading preview"><i></i><i></i><i></i><i></i></div>';

async function loadPreview(url) {
    content.innerHTML = skeleton;
    try {
        const response = await fetch(url, { headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('failed');
        content.innerHTML = await response.text();
    } catch {
        content.innerHTML = '<div class="do-error" role="alert"><span>Preview did not load. Check your connection, then try again.</span><button type="button" class="do-btn do-btn--small do-btn--soft" data-rp-retry>Try again</button></div>';
    }
}

document.addEventListener('click', event => {
    const preview = event.target.closest('[data-rp-preview]');
    if (preview && dialog) {
        lastUrl = preview.dataset.rpPreview;
        dialog.showModal();
        loadPreview(lastUrl);
        return;
    }
    if (event.target.closest('[data-rp-retry]')) { loadPreview(lastUrl); return; }
    if (event.target.closest('[data-rp-close]')) { dialog?.close(); return; }
    if (event.target === dialog) { dialog.close(); return; }

    const download = event.target.closest('[data-rp-download]');
    if (download && !download.classList.contains('is-loading')) {
        download.classList.add('is-loading');
        if (typeof window.sellerBanner === 'function') window.sellerBanner({ type: 'info', message: 'Preparing your PDF. The download starts in a moment.', timeout: 4000 });
        setTimeout(() => download.classList.remove('is-loading'), 3500);
    }
});
