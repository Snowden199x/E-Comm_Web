// Admin sidebar: pinned/peek state (Alpine store) and the sliding highlight.
// Used by components/admin/layout.blade.php and admin/partials/sidebar.blade.php only.
//
// Only #sidebar (the link list) is swapped when a link is followed with AJAX; the <aside>, the
// highlight element (#vd-nav-pill) and this store survive, which is what lets the highlight slide.

const DESKTOP = 1024;

const pillEl = () => document.getElementById('vd-nav-pill');
const activeItem = () => document.querySelector('#sidebar .vd-nav-link[aria-current="page"]')?.closest('.vd-nav-item') ?? null;

const PENDING_MAX_MS = 12000; // failsafe: a request that never reports back must not leave the highlight on the wrong item

let pendingLink = null;
let pendingTimer = 0;
let followFrame = 0;

/** Move the highlight to an item. `instant` skips the slide (first paint, resize, layout following). */
function place(item, instant = false) {
    const pill = pillEl();
    const box = pill?.parentElement;
    if (!pill || !box) return;

    if (!item) {
        pill.classList.remove('is-visible');
        return;
    }

    const itemRect = item.getBoundingClientRect();
    if (!itemRect.height) return;

    const boxRect = box.getBoundingClientRect();
    const y = itemRect.top - boxRect.top + box.scrollTop;

    // The very first placement must not slide in from the top of the list.
    const jump = instant || !pill.classList.contains('is-visible');
    if (jump) pill.classList.add('no-anim');

    pill.style.setProperty('--y', `${y}px`);
    pill.style.height = `${itemRect.height}px`;
    pill.classList.add('is-visible');

    if (jump && !followFrame) {
        void pill.offsetWidth; // commit the position before transitions come back
        requestAnimationFrame(() => pill.classList.remove('no-anim'));
    }
}

/**
 * Keep the highlight glued to its item while the list changes size (rail collapsing, group
 * labels folding). Runs for the length of the CSS transition, then hands control back.
 */
function follow(duration = 560) {
    const pill = pillEl();
    if (!pill) return;

    cancelAnimationFrame(followFrame);
    pill.classList.add('no-anim');

    const end = performance.now() + duration;
    const tick = () => {
        place(pendingLink?.closest('.vd-nav-item') ?? activeItem(), true);

        if (performance.now() < end) {
            followFrame = requestAnimationFrame(tick);
        } else {
            followFrame = 0;
            requestAnimationFrame(() => pill.classList.remove('no-anim'));
        }
    };
    followFrame = requestAnimationFrame(tick);
}

function settle() {
    window.clearTimeout(pendingTimer);
    pendingLink = null;
    document.getElementById('sidebar')?.removeAttribute('data-pending');
    place(activeItem());
}

// Click feedback: the highlight heads for the clicked item straight away, before the page arrives.
document.addEventListener('click', (event) => {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest?.('#sidebar .vd-nav-link');
    if (!link) return;

    document.querySelectorAll('#sidebar .vd-nav-link.is-pending').forEach((el) => el.classList.remove('is-pending'));

    pendingLink = link;
    link.classList.add('is-pending');
    document.getElementById('sidebar')?.setAttribute('data-pending', '');
    document.body.dataset.skeleton = link.dataset.skeleton || document.body.dataset.skeleton || 'table';

    window.clearTimeout(pendingTimer);
    pendingTimer = window.setTimeout(() => requestAnimationFrame(settle), PENDING_MAX_MS);

    place(link.closest('.vd-nav-item'));
});

const pathOf = (url) => {
    try {
        return new URL(url, window.location.origin).pathname.replace(/\/+$/, '');
    } catch (_) {
        return '';
    }
};

// The request finished (success or error): the real active item is the truth again.
//
// Listens on window, not document. The clicked link lives inside #sidebar, which is swapped by the
// response, so Alpine AJAX reports the end of the request on window (the link is gone by then).
// A document listener never heard it, so the highlight stayed on the clicked item and the
// loading skeleton never lifted until the page was reloaded.
window.addEventListener('ajax:after', (event) => {
    const response = event.detail?.response;

    // A newer click is already in flight: this answer belongs to an older request.
    if (pendingLink && response?.url && !response.redirected
        && pathOf(response.url) !== pathOf(pendingLink.getAttribute('href'))) {
        return;
    }

    requestAnimationFrame(settle);
});

window.addEventListener('resize', () => place(pendingLink?.closest('.vd-nav-item') ?? activeItem(), true));
window.addEventListener('pageshow', () => place(activeItem(), true));

const start = () => {
    place(activeItem(), true);
    follow(700); // Alpine applies the pinned width a moment after first paint
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
    start();
}

document.addEventListener('alpine:init', () => {
    const read = () => {
        try {
            return localStorage.getItem('vendo_sidebar_collapsed') === 'true';
        } catch (_) {
            return false;
        }
    };

    Alpine.store('sidebar', {
        // "collapsed" is the pinned state, what the burger button controls
        collapsed: read(),
        // "hovering" is the temporary peek when the cursor is over the rail
        hovering: false,
        mobileOpen: false,
        _timer: null,

        // The width the sidebar should actually render at right now
        get expanded() {
            return !this.collapsed || this.hovering;
        },

        toggle() {
            if (window.innerWidth < DESKTOP) {
                this.mobileOpen = !this.mobileOpen;
                return;
            }

            clearTimeout(this._timer);
            this.collapsed = !this.collapsed;
            this.hovering = false;

            try {
                localStorage.setItem('vendo_sidebar_collapsed', this.collapsed);
            } catch (_) {}

            follow();
        },

        close() {
            this.mobileOpen = false;
        },

        // A short delay on enter stops the rail from flickering open when the cursor only crosses it.
        hoverOn() {
            if (window.innerWidth < DESKTOP || !window.matchMedia('(hover: hover)').matches) return;

            clearTimeout(this._timer);
            this._timer = setTimeout(() => {
                if (!this.collapsed) return;
                this.hovering = true;
                follow();
            }, 120);
        },

        hoverOff() {
            clearTimeout(this._timer);
            if (!this.hovering) return;

            this._timer = setTimeout(() => {
                this.hovering = false;
                follow();
            }, 60);
        },
    });
});