// Logistics top banners.
//
//   window.logisticsBanner({ type: 'success' | 'error' | 'warning' | 'info', title, message, actionLabel, onAction, timeout })
//
// - Session messages: <x-logistics.flash /> prints hidden [data-lg-flash] elements; they become banners on load.
// - Toasts: pages that still run $dispatch('lg-toast', ...) are routed here by the shell, so every message looks the same.
// - window.lgOpenNotification(url) opens a notification the same way the bell menu does (POST with the CSRF token).
// Styles: resources/css/logistics/banner.css.

const host = document.getElementById('lgBanners');

const ICONS = {
    success: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/>',
    warning: '<path d="M12 3.5L2.5 20h19L12 3.5z"/><path d="M12 10v4.5M12 17.5v.01"/>',
    info: '<path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
};

function banner(opts = {}) {
    if (!host) return null;

    const type = ICONS[opts.type] ? opts.type : 'info';
    const signature = [type, opts.title || '', opts.message || ''].join('|');

    // The same message twice in a row (for example a failed refresh) shows once.
    const same = [...host.children].find((node) => node.dataset.signature === signature);
    if (same) return same;

    while (host.children.length >= 3) host.firstChild.remove();

    const el = document.createElement('div');
    el.className = `lgb lgb--${type}`;
    el.dataset.signature = signature;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `<span class="lgb__icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[type]}</svg></span>`
        + '<div class="lgb__text"></div>'
        + '<button type="button" class="lgb__close" aria-label="Dismiss message"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';

    const text = el.querySelector('.lgb__text');
    if (opts.title) {
        const title = document.createElement('strong');
        title.textContent = opts.title;
        text.appendChild(title);
    }
    if (opts.message) {
        const message = document.createElement('span');
        message.textContent = opts.message;
        text.appendChild(message);
    }

    let timer = 0;
    const dismiss = () => {
        window.clearTimeout(timer);
        if (!el.parentNode) return;
        el.classList.add('is-leaving');
        window.setTimeout(() => el.remove(), 200);
    };
    const start = () => {
        timer = window.setTimeout(dismiss, opts.timeout || (type === 'error' || type === 'warning' ? 10000 : 6000));
    };

    if (opts.actionLabel) {
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'lgb__action';
        action.textContent = opts.actionLabel;
        action.addEventListener('click', () => {
            if (opts.onAction) opts.onAction();
            dismiss();
        });
        el.insertBefore(action, el.querySelector('.lgb__close'));
    }

    el.querySelector('.lgb__close').addEventListener('click', dismiss);
    el.addEventListener('mouseenter', () => window.clearTimeout(timer));
    el.addEventListener('mouseleave', start);

    host.appendChild(el);
    start();

    return el;
}

window.logisticsBanner = banner;

window.lgOpenNotification = (url) => {
    if (!url) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
};

document.querySelectorAll('[data-lg-flash]').forEach((node) => {
    banner({ type: node.dataset.type, title: node.dataset.title, message: node.dataset.message });
});
