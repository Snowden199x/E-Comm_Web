<script>
(() => {
    let bound = null;
    let revision = null;
    let pending = false;
    let timer = null;
    let checkCurrent = null;
    const busy = () => {
        const active = document.activeElement;
        const dialog = [...document.querySelectorAll('dialog[open], [role="dialog"]')]
            .some(node => node.getClientRects().length > 0);
        return dialog || (active && active.matches('input, textarea, select, [contenteditable="true"]'));
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
        timer = setInterval(check, 3000);
    };
    new MutationObserver(bind).observe(document.body, {childList: true, subtree: true});
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkCurrent?.();
    });
    bind();
})();
</script>
