// Logistics shell loading states.
//
//  - First load: the boot skeleton (#vd-boot) is lifted by the Admin's motion.js, imported below.
//  - Buttons: submit buttons get a spinner and stop accepting clicks once their form is sent (button-loading.js).
//  - Page to page: the Logistics sidebar uses normal links, so the browser keeps the old page on screen until the
//    next one arrives. When a sidebar link is slow to open, a skeleton in the shape of the target page
//    (the link's data-skeleton) covers the old page. Quick navigations never show it.
//
// Styles: resources/css/admin/motion.css. Skeleton markup: components/logistics/skeleton.blade.php.

import '../admin/motion.js';
import '../admin/button-loading.js';

const body = document.body;
const progress = document.getElementById('lg-progress');

const SKELETON_DELAY_MS = 90; // links that open faster than this show no skeleton
const FAILSAFE_MS = 12000; // a request that never reports back must not strand the skeleton

let delayTimer = 0;
let failsafeTimer = 0;

function endNavigation() {
    window.clearTimeout(delayTimer);
    window.clearTimeout(failsafeTimer);
    body.classList.remove('is-navigating');
    progress?.classList.remove('is-active');
}

function opensAnotherPage(link, event) {
    if (event.defaultPrevented || event.button !== 0) return false;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.hasAttribute('data-no-loading')) return false;
    if (link.getAttribute('aria-current') === 'page') return false;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return false;

    // Same page, different hash: the browser does not load anything.
    return !(url.pathname === window.location.pathname && url.search === window.location.search);
}

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (!link || !opensAnotherPage(link, event)) return;

    // The progress bar shows for every link; the skeleton only where the page shape is known (sidebar links).
    progress?.classList.add('is-active');

    window.clearTimeout(delayTimer);
    window.clearTimeout(failsafeTimer);

    const shape = link.dataset.skeleton;
    if (shape) {
        body.dataset.skeleton = shape;
        delayTimer = window.setTimeout(() => body.classList.add('is-navigating'), SKELETON_DELAY_MS);
    }

    failsafeTimer = window.setTimeout(endNavigation, FAILSAFE_MS);
});

// Back/forward cache restores the page without a new load: never show a stale skeleton or bar.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) endNavigation();
});
