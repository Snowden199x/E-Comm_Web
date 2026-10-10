// Opens the shipping label in a modal instead of a new browser tab.
// Any <a data-waybill href="…/waybill"> on any seller page works, including links inside slide-over panels loaded later.
// Modified clicks (Ctrl/Cmd/Shift or middle button) still follow the link normally.
const modal = document.getElementById('wbModal');

if (modal) {
    const frame = document.getElementById('wbFrame');
    const state = document.getElementById('wbState');
    const title = document.getElementById('wbTitle');
    const printButton = document.getElementById('wbPrint');
    let loadToken = 0;

    const message = (text, isError = false) => {
        frame.hidden = true;
        state.hidden = false;
        state.textContent = text;
        state.classList.toggle('is-error', isError);
        printButton.disabled = true;
    };

    const open = async (url, label) => {
        const token = ++loadToken;
        title.textContent = label ? `Shipping label · ${label}` : 'Shipping label';
        message('Loading label…');
        if (!modal.open) modal.showModal();
        try {
            const response = await fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (token !== loadToken) return;
            if (response.status === 409) return message('This label is not available yet. Mark the order ready for pickup and wait for the pickup logistics assignment.', true);
            if (response.status === 403 || response.status === 404) return message('You do not have access to this label.', true);
            if (!response.ok) return message('The label could not be loaded. Close this window and try again.', true);

            const html = await response.text();
            if (token !== loadToken) return;
            frame.addEventListener('load', () => {
                if (token !== loadToken) return;
                // The page ships its own toolbar; the modal header already has Print, so hide the duplicate.
                try {
                    const style = frame.contentDocument.createElement('style');
                    style.textContent = '.print-toolbar{display:none!important}body{background:#fff!important}.shipping-label{margin-top:12px!important}';
                    frame.contentDocument.head.append(style);
                } catch { /* cross-origin frames cannot be styled; the toolbar stays visible */ }
                state.hidden = true;
                frame.hidden = false;
                printButton.disabled = false;
            }, { once: true });
            frame.srcdoc = html;
        } catch {
            if (token === loadToken) message('Network problem. Check your connection and try again.', true);
        }
    };

    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-waybill]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
        event.preventDefault();
        open(link.href, link.dataset.waybillTitle);
    });

    printButton.addEventListener('click', () => {
        frame.contentWindow?.focus();
        frame.contentWindow?.print();
    });

    const close = () => { loadToken++; modal.close(); };
    modal.addEventListener('click', event => {
        if (event.target === modal || event.target.closest('[data-wb-close]')) close();
    });
    modal.addEventListener('close', () => { loadToken++; frame.removeAttribute('srcdoc'); frame.hidden = true; });
}