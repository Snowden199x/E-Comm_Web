// Seller > Mobile helpers. Adds data-label to table cells so mobile.css can show them as card rows.
// Does nothing visible on desktop: the attributes are only read by rules inside the mobile media query.
(function () {
    const TABLES = '.do-table, .ops-table, .omo-table, .pi-table, .rp-table, .rt-table';

    function label(table) {
        const heads = Array.from(table.querySelectorAll('thead th')).map(th => (th.textContent || '').replace(/\s+/g, ' ').trim());
        if (!heads.length) return;
        table.querySelectorAll('tbody tr').forEach(row => {
            let col = 0;
            Array.from(row.children).forEach(cell => {
                if (cell.tagName !== 'TD') return;
                const span = parseInt(cell.getAttribute('colspan') || '1', 10);
                let text = span > 1 ? '' : (heads[col] || '');
                if (/^actions?$/i.test(text)) text = ''; // the buttons speak for themselves
                if (text) { if (cell.getAttribute('data-label') !== text) cell.setAttribute('data-label', text); }
                else if (cell.hasAttribute('data-label')) cell.removeAttribute('data-label');
                col += span;
            });
        });
    }

    function run(root) { (root || document).querySelectorAll(TABLES).forEach(label); }

    function start() {
        run(document);
        // Rows are replaced by the filters and pagination, so label them again when they change.
        let queued = false;
        new MutationObserver(() => {
            if (queued) return;
            queued = true;
            requestAnimationFrame(() => { queued = false; run(document); });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
