/*
 * Vendo Logistics workspace behavior.
 * Plain JS (no Alpine dependency) for the shell; page modals and menus use
 * Alpine directives in the Blade views.
 */

const NAV_KEY = 'vendo.logistics.nav';
const body = document.body;
const mobileQuery = window.matchMedia('(max-width: 960px)');
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Sidebar: collapse on desktop, drawer on mobile ---------- */
function storedCollapsed() {
    try {
        return localStorage.getItem(NAV_KEY) === 'collapsed';
    } catch (error) {
        return false;
    }
}

function syncToggleState() {
    const open = mobileQuery.matches
        ? body.classList.contains('lg-drawer-open')
        : !body.classList.contains('lg-collapsed');

    document.querySelectorAll('[data-lg-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', String(open));
    });
}

function closeDrawer() {
    body.classList.remove('lg-drawer-open');
    syncToggleState();
}

function initSidebar() {
    if (storedCollapsed()) {
        body.classList.add('lg-collapsed');
    }

    document.querySelectorAll('[data-lg-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            if (mobileQuery.matches) {
                body.classList.toggle('lg-drawer-open');
            } else {
                const collapsed = body.classList.toggle('lg-collapsed');
                try {
                    localStorage.setItem(NAV_KEY, collapsed ? 'collapsed' : 'expanded');
                } catch (error) {
                    /* storage unavailable: the choice just won't persist */
                }
            }
            syncToggleState();
        });
    });

    document.querySelectorAll('[data-lg-backdrop]').forEach((el) => el.addEventListener('click', closeDrawer));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    document.querySelectorAll('#lgSidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobileQuery.matches) {
                closeDrawer();
            }
        });
    });

    mobileQuery.addEventListener('change', closeDrawer);
    syncToggleState();

    // Enable width/margin transitions only after the first paint so the saved
    // collapsed state does not animate on page load.
    requestAnimationFrame(() => requestAnimationFrame(() => body.classList.add('lg-ready')));
}

/* ---------- Toasts ---------- */
function dismissToast(toast) {
    if (!toast || toast.classList.contains('is-leaving')) {
        return;
    }
    toast.classList.add('is-leaving');
    setTimeout(() => toast.remove(), reduceMotion ? 0 : 260);
}

function initToasts() {
    document.querySelectorAll('.lg-toast').forEach((toast) => {
        const timeout = parseInt(toast.dataset.timeout || '0', 10);
        if (timeout > 0) {
            setTimeout(() => dismissToast(toast), timeout);
        }
        toast.querySelector('.lg-toast__close')?.addEventListener('click', () => dismissToast(toast));
    });
}

function initNotificationCount() {
    const count = document.querySelector('[data-lg-notification-count]');
    if (!count) return;
    const refresh = async () => {
        try {
            const response = await fetch(count.dataset.endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            count.textContent = String(data.unread_count || 0);
            count.hidden = !data.unread_count;
            count.closest('a')?.setAttribute('aria-label', `Notifications, ${data.unread_count || 0} unread`);
        } catch (_) { /* The count will refresh on the next page load. */ }
    };
    setInterval(refresh, 30000);
}

/* ---------- Count-up numbers ---------- */
function initCountUp() {
    const format = new Intl.NumberFormat();

    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10) || 0;
        if (reduceMotion || target === 0) {
            el.textContent = format.format(target);
            return;
        }

        const duration = 700;
        const start = performance.now();
        el.textContent = '0';

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = format.format(Math.round(target * eased));
            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };
        requestAnimationFrame(tick);
    });
}

/* ---------- Client-side list filter (current page only) ---------- */
function initFilters() {
    document.querySelectorAll('[data-lg-filter]').forEach((root) => {
        const items = Array.from(root.querySelectorAll('[data-lg-item]'));
        const tabs = Array.from(root.querySelectorAll('[data-lg-tab]'));
        const areaSelect = root.querySelector('[data-lg-area]');
        const searchInput = root.querySelector('[data-lg-search]');
        const emptyState = root.querySelector('[data-lg-empty]');
        const visibleLabel = root.querySelector('[data-lg-visible]');
        const state = { stage: 'all', area: '', query: '' };

        // Nothing to filter (the page shows its own empty state).
        if (items.length === 0) {
            return;
        }

        const apply = () => {
            let visible = 0;

            items.forEach((item) => {
                const matches =
                    (state.stage === 'all' || item.dataset.stage === state.stage) &&
                    (!state.area || item.dataset.area === state.area) &&
                    (!state.query || (item.dataset.search || '').includes(state.query));

                const wasHidden = item.hidden;
                item.hidden = !matches;

                if (matches) {
                    visible += 1;
                    if (wasHidden) {
                        item.classList.remove('lg-pop');
                        // restart the animation
                        void item.offsetWidth;
                        item.classList.add('lg-pop');
                    }
                }
            });

            if (visibleLabel) {
                visibleLabel.textContent = String(visible);
            }
            if (emptyState) {
                emptyState.hidden = visible !== 0;
            }
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                state.stage = tab.dataset.lgTab;
                tabs.forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
                apply();
            });
        });

        areaSelect?.addEventListener('change', () => {
            state.area = areaSelect.value;
            apply();
        });

        searchInput?.addEventListener('input', () => {
            state.query = searchInput.value.trim().toLowerCase();
            apply();
        });

        apply();
    });
}

/* ---------- Submit buttons: show a spinner and block double submits ---------- */
function initLoadingForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-lg-loading')) {
            return;
        }

        const button = form.querySelector('[type="submit"]');
        if (button) {
            // Wait a tick so the browser has already collected the form data.
            setTimeout(() => {
                button.classList.add('is-loading');
                button.disabled = true;
            }, 0);
        }
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll('.lg-btn.is-loading').forEach((button) => {
                button.classList.remove('is-loading');
                button.disabled = false;
            });
        }
    });
}

/* ---------- CSV export of a rendered table ---------- */
function initCsvExport() {
    document.querySelectorAll('[data-lg-export]').forEach((button) => {
        button.addEventListener('click', () => {
            const table = document.getElementById(button.dataset.lgExport);
            if (!table) {
                return;
            }

            const escape = (value) => `"${value.replace(/\s+/g, ' ').trim().replace(/"/g, '""')}"`;
            const lines = Array.from(table.querySelectorAll('tr')).map((row) =>
                Array.from(row.querySelectorAll('th, td'))
                    .filter((cell) => !cell.classList.contains('lg-no-export'))
                    .map((cell) => escape(cell.textContent))
                    .join(',')
            );

            const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = button.dataset.lgFilename || 'logistics-report.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(link.href);
        });
    });
}

function initPrint() {
    document.querySelectorAll('[data-lg-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initToasts();
    initNotificationCount();
    initCountUp();
    initFilters();
    initLoadingForms();
    initCsvExport();
    initPrint();
});
