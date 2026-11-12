// Logistics · Company dashboard behavior.
//  - One click on a row (attention item or Province Hub) opens its page. Links and buttons inside the row keep working.
//  - A button that opens another page shows a spinner until the page arrives.
// Styles: resources/css/logistics/dashboard-theme.css.

const root = document.querySelector('[data-lgd]');

if (root) {
    const modified = (e) => e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0;

    const spin = (el) => {
        el.classList.add('is-loading');
        el.setAttribute('aria-busy', 'true');
    };

    root.addEventListener('click', (event) => {
        // Buttons and links: spinner while the next page loads.
        const link = event.target.closest('a.lgd-btn[href]');
        if (link) {
            if (!modified(event) && !event.defaultPrevented && link.target !== '_blank') spin(link);
            return;
        }

        // Rows: open the row's page unless the click was on another control.
        const row = event.target.closest('[data-href]');
        if (!row || event.target.closest('a, button, input, select, textarea, summary, label')) return;

        if (modified(event)) {
            window.open(row.dataset.href, '_blank', 'noopener');
            return;
        }

        const button = row.querySelector('a.lgd-btn');
        if (button) spin(button);
        window.location.assign(row.dataset.href);
    });

    // Coming back with the browser's Back button restores the page from cache: clear any spinner left on.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        root.querySelectorAll('.is-loading').forEach((el) => {
            el.classList.remove('is-loading');
            el.removeAttribute('aria-busy');
        });
    });
}
