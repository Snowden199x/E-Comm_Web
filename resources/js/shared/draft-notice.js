import '../../css/shared/draft-notice.css';

let shownOnThisPage = false;

function wasReloaded() {
    const navigation = performance.getEntriesByType('navigation')[0];
    return navigation?.type === 'reload' || (!navigation && performance.navigation?.type === 1);
}

export function showRestoredDraftNotice(draftKey, message) {
    if (!wasReloaded()) return;

    const noticeKey = `vendo.draft.notice.${draftKey}`;
    let alreadyShown = false;

    try {
        alreadyShown = sessionStorage.getItem(noticeKey) === '1';
        sessionStorage.setItem(noticeKey, '1');
    } catch (_) {
        // Browser storage can be unavailable; draft recovery still continues.
    }

    if (alreadyShown || shownOnThisPage) return;
    shownOnThisPage = true;

    const display = () => {
        const toast = document.createElement('div');
        toast.className = 'vendo-draft-toast';
        toast.setAttribute('role', 'status');

        const text = document.createElement('span');
        text.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss draft notice');
        close.textContent = '×';

        let timer;
        const dismiss = () => {
            clearTimeout(timer);
            toast.remove();
        };
        close.addEventListener('click', dismiss);
        toast.append(text, close);
        document.body.append(toast);
        timer = setTimeout(dismiss, 7000);
    };

    if (document.body) display();
    else document.addEventListener('DOMContentLoaded', display, { once: true });
}

export function clearRestoredDraftNotice(draftKey) {
    try { sessionStorage.removeItem(`vendo.draft.notice.${draftKey}`); } catch (_) {}
}

window.vendoDraftNotice = {
    restored: showRestoredDraftNotice,
    clear: clearRestoredDraftNotice,
};
