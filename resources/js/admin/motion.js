// Admin shell motion: first-load skeleton, page-to-page skeleton, back/forward cache.
// Imported from resources/js/admin/layout.js. No backend involved.
//
//  - #vd-boot is a skeleton of the whole shell, painted by the layout before any stylesheet
//    arrives. It lifts once the DOM and scripts are ready (and a short minimum, so it never just
//    flashes). It does not wait for the window "load" event: that also waits for every image and
//    third-party file, which is what made the first load feel endless on a slow connection.
//  - Following a sidebar/menu link with AJAX swaps #main-content. If that takes longer than a
//    moment, a page-shaped skeleton covers the old page until the new one arrives. Quick
//    navigations never show it.

const body = document.body;
const boot = document.getElementById("vd-boot");

const MIN_BOOT_MS = 380; // long enough to read as a deliberate frame, short enough not to annoy
const NAV_SKELETON_DELAY_MS = 90; // navigations faster than this show no skeleton at all
const NAV_SKELETON_MAX_MS = 12000; // failsafe: a request that never reports back must not strand the skeleton

function wait(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function liftBoot() {
    if (!boot) return;

    const fonts = document.fonts?.ready ?? Promise.resolve();
    const minimum = wait(Math.max(0, MIN_BOOT_MS - performance.now()));

    // Never wait on fonts for more than a moment.
    Promise.all([minimum, Promise.race([fonts, wait(1500)])]).then(() => {
        boot.classList.add("is-done");
        body.classList.remove("vd-booting");
        body.classList.add("vd-intro"); // one cascade of the sidebar items

        window.setTimeout(() => boot.remove(), 500);
        window.setTimeout(() => body.classList.remove("vd-intro"), 1400);
    });
}

if (boot) {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", liftBoot, { once: true });
    } else {
        liftBoot();
    }
} else {
    body.classList.remove("vd-booting");
}

// Back/forward cache restores the page without a new load: never show a stale skeleton.
window.addEventListener("pageshow", (event) => {
    if (!event.persisted) return;
    document.getElementById("vd-boot")?.remove();
    body.classList.remove("vd-booting", "is-navigating");
});

// ---- Page-to-page skeleton ----------------------------------------------------------------

// A navigation is a link that asks for #main-content (x-target.push="main-content ...").
// Forms, filters and in-page refreshes keep their own loading bars instead.
function isNavigationLink(el) {
    if (!(el instanceof HTMLAnchorElement)) return false;

    return Array.from(el.attributes).some(
        (attr) => attr.name.startsWith("x-target") && attr.value.split(/\s+/).includes("main-content"),
    );
}

let navTimer = 0;
let navFailsafe = 0;

function endNavigation() {
    window.clearTimeout(navTimer);
    window.clearTimeout(navFailsafe);
    body.classList.remove("is-navigating");
}

document.addEventListener("ajax:before", (event) => {
    const link = event.target;
    if (!isNavigationLink(link)) return;

    const shape = link.dataset.skeleton || link.closest("[data-skeleton]")?.dataset.skeleton;
    if (shape) body.dataset.skeleton = shape;

    window.clearTimeout(navTimer);
    window.clearTimeout(navFailsafe);
    navTimer = window.setTimeout(() => body.classList.add("is-navigating"), NAV_SKELETON_DELAY_MS);
    navFailsafe = window.setTimeout(endNavigation, NAV_SKELETON_MAX_MS);
});

// The top bar and the tab title sit outside #main-content, so a swap never touches them.
// Copy them from the page that just arrived.
function syncPageTitle(response) {
    const incoming = response?.html;
    if (!incoming?.querySelector) return;

    const documentTitle = incoming.querySelector("title")?.textContent?.trim();
    if (documentTitle) document.title = documentTitle;

    const heading = document.getElementById("admin-page-title");
    const next = incoming.getElementById?.("admin-page-title");
    if (heading && next) heading.textContent = next.textContent.trim();
}

// "after" fires whether the request worked or not, so the skeleton can never get stuck.
//
// This listens on window, not document, on purpose. When the clicked link was inside a swapped
// target (every sidebar link is: #sidebar is swapped), Alpine AJAX dispatches "after" on window
// because the link is no longer in the DOM. A document listener never hears that event, which
// left the skeleton on screen until the page was reloaded.
window.addEventListener("ajax:after", (event) => {
    endNavigation();
    syncPageTitle(event.detail?.response);

    // The new page is in: put keyboard and screen-reader focus at its top.
    document.getElementById("main-content")?.focus({ preventScroll: true });
});
