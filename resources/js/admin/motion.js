// Admin shell motion: dismisses the loading screen and replays the page entrance
// whenever Alpine AJAX swaps the main content (sidebar links, "View all" links).
// Imported from resources/js/admin/layout.js. No backend involved.

const splash = document.getElementById("vd-splash");
const MIN_VISIBLE_MS = 600; // long enough to read as a deliberate moment, short enough not to annoy

function dismissSplash() {
    if (!splash) return;
    const wait = Math.max(0, MIN_VISIBLE_MS - performance.now());

    window.setTimeout(() => {
        splash.classList.add("is-done");
        window.setTimeout(() => splash.remove(), 700);
    }, wait);
}

if (splash) {
    if (document.readyState === "complete") {
        dismissSplash();
    } else {
        window.addEventListener("load", dismissSplash, { once: true });
    }
}

// Back/forward cache restores the page without a new load: never show a stale splash.
window.addEventListener("pageshow", (event) => {
    if (event.persisted) document.getElementById("vd-splash")?.remove();
});

// Replay the entrance each time the page body inside #main-content is replaced.
const main = document.getElementById("main-content");

if (main) {
    new MutationObserver(() => {
        main.classList.remove("vd-enter");
        void main.offsetWidth; // restart the animation
        main.classList.add("vd-enter");
        main.scrollTo({ top: 0 });
    }).observe(main, { childList: true });
}