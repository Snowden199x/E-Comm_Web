// Button loading state for the Admin panel.
//
// Submit buttons get a spinner (and stop accepting clicks) as soon as their form is sent, so a
// slow request can't be double-submitted and the person sees that something is happening.
//   - Normal forms: loading starts on submit and ends when the browser leaves the page.
//   - Alpine AJAX forms (x-target): loading ends on ajax:after.
//   - Custom buttons (a click handler calling fetch): call vdButtonLoading(button, true / false).
//   - Opt out of the automatic behaviour with data-no-loading on the form or the button.
//
// Styles: the button[data-vd-busy] rules in resources/css/admin/motion.css.

const BUSY = "data-vd-busy";
const SAFETY_MS = 20000; // downloads never leave the page; don't spin forever

const timers = new WeakMap();

export function setButtonLoading(button, loading = true) {
    if (!(button instanceof HTMLElement)) return;

    if (!loading) {
        window.clearTimeout(timers.get(button));
        timers.delete(button);

        button.removeAttribute(BUSY);
        button.removeAttribute("aria-busy");
        button.removeAttribute("aria-disabled");
        button.style.removeProperty("--vd-spin-color");

        if (button.dataset.vdPosition === "1") {
            button.style.removeProperty("position");
            delete button.dataset.vdPosition;
        }

        return;
    }

    if (button.hasAttribute(BUSY)) return;

    const style = getComputedStyle(button);

    // The spinner takes the button's own text colour, so it works on dark and light buttons.
    button.style.setProperty("--vd-spin-color", style.color);

    // The spinner is positioned inside the button.
    if (style.position === "static") {
        button.style.position = "relative";
        button.dataset.vdPosition = "1";
    }

    // Not "disabled": a disabled submit button can lose its name/value in the request.
    button.setAttribute(BUSY, "");
    button.setAttribute("aria-busy", "true");
    button.setAttribute("aria-disabled", "true");

    timers.set(button, window.setTimeout(() => setButtonLoading(button, false), SAFETY_MS));
}

window.vdButtonLoading = setButtonLoading;

function submitButtonOf(form, submitter) {
    if (submitter instanceof HTMLElement) return submitter;

    return form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
}

function isAjaxForm(form) {
    return Array.from(form.attributes).some((attr) => attr.name.startsWith("x-target"));
}

// Capture phase: a second click or Enter press while the first is in flight does nothing.
document.addEventListener(
    "click",
    (event) => {
        if (event.target.closest?.(`[${BUSY}]`)) {
            event.preventDefault();
            event.stopPropagation();
        }
    },
    true,
);

document.addEventListener(
    "submit",
    (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.querySelector(`[${BUSY}]`)) event.preventDefault();
    },
    true,
);

// Bubble phase: page handlers (confirm dialogs, validation) have already had their say.
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute("data-no-loading") || form.target === "_blank") return;

    const button = submitButtonOf(form, event.submitter);
    if (!button || button.hasAttribute("data-no-loading")) return;

    // Cancelled by the page (confirm said no, custom fetch): nothing is being sent by the browser.
    // Alpine AJAX cancels the browser submit too, but it sends the request itself.
    if (event.defaultPrevented && !isAjaxForm(form)) return;

    setButtonLoading(button, true);
});

// Alpine AJAX: the request is done (success or error), so the buttons come back.
document.addEventListener("ajax:after", (event) => {
    const scope = event.target;
    if (!(scope instanceof HTMLElement)) return;

    if (scope.hasAttribute?.(BUSY)) setButtonLoading(scope, false);
    scope.querySelectorAll?.(`[${BUSY}]`).forEach((button) => setButtonLoading(button, false));
});

// Back/forward cache can bring a page back mid-spinner.
window.addEventListener("pageshow", (event) => {
    if (event.persisted) document.querySelectorAll(`[${BUSY}]`).forEach((button) => setButtonLoading(button, false));
});