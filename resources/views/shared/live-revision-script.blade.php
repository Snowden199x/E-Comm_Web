<script>
(() => {
    let bound = null;
    let revision = null;
    let pending = false;
    let timer = null;
    let checkCurrent = null;
    const hasUnsavedFormChanges = () => [...document.forms].some(form =>
        [...form.elements].some(field => {
            if (field.disabled || ['hidden', 'submit', 'button', 'reset', 'file', 'password'].includes(field.type)) return false;
            if (field.type === 'checkbox' || field.type === 'radio') return field.checked !== field.defaultChecked;
            if (field.tagName === 'SELECT') return [...field.options].some(option => option.selected !== option.defaultSelected);
            return 'defaultValue' in field && field.value !== field.defaultValue;
        })
    );
    const busy = () => {
        const active = document.activeElement;
        const dialog = [...document.querySelectorAll('dialog[open], [role="dialog"]')]
            .some(node => node.getClientRects().length > 0);
        return dialog || hasUnsavedFormChanges()
            || (active && active.matches('input, textarea, select, [contenteditable="true"]'));
    };
    const bind = () => {
        const banner = document.querySelector('[data-live-revision]');
        if (banner === bound) return;
        if (timer) clearInterval(timer);
        bound = banner;
        revision = null;
        pending = false;
        checkCurrent = null;
        if (!banner) return;
        banner.querySelector('[data-live-refresh]').addEventListener('click', () => location.reload());
        const check = async () => {
            if (document.hidden || pending || !banner.isConnected) return;
            pending = true;
            try {
                const response = await fetch(banner.dataset.liveRevision, {
                    headers: {'Accept': 'application/json'}, cache: 'no-store'
                });
                if (!response.ok || bound !== banner) return;
                const current = (await response.json()).revision;
                if (!current || bound !== banner) return;
                if (revision && revision !== current) {
                    if (banner.dataset.liveMode === 'reload' && !busy()) location.reload();
                    else banner.hidden = false;
                }
                if (revision === null) revision = current;
            } catch (_) {
                // A connection error leaves the current page usable.
            } finally { if (bound === banner) pending = false; }
        };
        checkCurrent = check;
        check();
        // WebSocket events request an immediate scoped revision check. Keep a
        // slower poll as a fallback for deployments where Reverb is offline.
        timer = setInterval(check, 30000);
    };
    new MutationObserver(bind).observe(document.body, {childList: true, subtree: true});
    window.addEventListener('vendo:live-update', () => checkCurrent?.());
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkCurrent?.();
    });
    bind();
})();
</script>
