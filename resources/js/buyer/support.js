// Buyer · Support. Progressive enhancement only: every form and link still works without this file.
(() => {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const ICON = {
        chevron: '<svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
        check: '<svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
        close: '<svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    };

    // Top banner shared with the rest of the buyer area (Alpine store from buyer/partials/banner).
    const banner = (type, title, text) => {
        if (window.Alpine?.store('banner')) window.Alpine.store('banner').show({ type, title, text });
    };

    /* ---------- Request list: filter, search and page without a full reload ---------- */
    const list = $('#spList');
    if (list) {
        const chips = $('#spChips');
        let controller = null;

        const errorState = (url) => {
            list.classList.remove('is-loading');
            $$('.sp-list, .sp-empty, .sp-pager', list).forEach((el) => el.remove());
            const box = document.createElement('div');
            box.className = 'sp-empty sp-empty--error';
            box.setAttribute('role', 'alert');
            box.innerHTML = '<span class="sp-empty__icon"><svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/></svg></span>'
                + '<strong>Could not load your requests</strong><p>Check your connection, then select Retry.</p>'
                + '<button type="button" class="sp-btn sp-btn--primary">Retry</button>';
            box.querySelector('button').addEventListener('click', () => load(url, false));
            list.appendChild(box);
        };

        const load = async (url, push = true) => {
            controller?.abort();
            controller = new AbortController();
            list.classList.add('is-loading');
            list.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' });
                if (response.status === 419 || response.status === 401) throw new Error('Your session expired. Reload the page and sign in again.');
                if (!response.ok) throw new Error('Vendo could not load this list. Try again in a moment.');
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextList = $('#spList', doc);
                if (!nextList) throw new Error('Vendo could not load this list. Reload the page and try again.');
                list.innerHTML = nextList.innerHTML;
                if (chips && $('#spChips', doc)) chips.innerHTML = $('#spChips', doc).innerHTML;
                if (push) history.pushState({ sp: true }, '', url);
                list.classList.remove('is-loading');
                list.removeAttribute('aria-busy');
            } catch (error) {
                if (error.name === 'AbortError') return;
                list.removeAttribute('aria-busy');
                errorState(url);
                banner('error', 'Requests did not load', error.message && !/fetch/i.test(error.message) ? error.message : 'Check your connection, then select Retry.');
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-sp-nav]');
            if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
            event.preventDefault();
            load(link.href);
        });
        const search = $('#spSearch');
        if (search) {
            search.addEventListener('submit', (event) => {
                event.preventDefault();
                const params = new URLSearchParams(new FormData(search));
                [...params.keys()].forEach((key) => { if (!params.get(key)) params.delete(key); });
                load(search.action + (params.toString() ? '?' + params : ''));
            });
        }
        window.addEventListener('popstate', () => load(location.href, false));
    }

    /* ---------- Dropdowns: a styled menu on top of the native select ---------- */
    $$('select[data-sp-select]').forEach((select) => {
        const wrap = document.createElement('div');
        wrap.className = 'sp-select';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'sp-select__btn';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        const labelledBy = select.id ? document.querySelector(`label[for="${select.id}"]`) : null;
        if (labelledBy) { labelledBy.id ||= select.id + '-label'; button.setAttribute('aria-labelledby', labelledBy.id); }
        const menu = document.createElement('div');
        menu.className = 'sp-menu';
        menu.hidden = true;
        menu.setAttribute('role', 'listbox');
        wrap.append(button, menu);

        const options = [...select.options].map((option) => ({ value: option.value, label: option.textContent.trim() }));
        const searchable = options.length > 8;
        let search = null; let backdrop = null; let focusIndex = -1;

        const paint = () => {
            const current = options.find((o) => o.value === select.value) || options[0];
            button.innerHTML = `<span>${current ? current.label.replace(/</g, '&lt;') : ''}</span>${ICON.chevron}`;
            button.classList.toggle('is-placeholder', !select.value);
        };
        const rows = () => $$('.sp-menu__opt', menu);
        const render = (term = '') => {
            menu.innerHTML = '';
            if (searchable) {
                const holder = document.createElement('div');
                holder.className = 'sp-menu__search';
                holder.innerHTML = '<input type="search" placeholder="Search" aria-label="Search options" autocomplete="off">';
                search = holder.firstElementChild;
                search.value = term;
                search.addEventListener('input', () => render(search.value));
                menu.appendChild(holder);
            }
            const shown = options.filter((o) => o.label.toLowerCase().includes(term.toLowerCase()));
            shown.forEach((o) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'sp-menu__opt';
                row.setAttribute('role', 'option');
                row.setAttribute('aria-selected', String(o.value === select.value));
                row.dataset.value = o.value;
                row.innerHTML = `<span></span>${ICON.check}`;
                row.firstElementChild.textContent = o.label;
                row.addEventListener('click', () => choose(o.value));
                menu.appendChild(row);
            });
            if (!shown.length) {
                const none = document.createElement('p');
                none.className = 'sp-menu__none';
                none.textContent = 'No match. Try a different word.';
                menu.appendChild(none);
            }
            if (searchable) search.focus({ preventScroll: true });
            focusIndex = Math.max(0, rows().findIndex((r) => r.getAttribute('aria-selected') === 'true'));
            mark();
        };
        const mark = () => rows().forEach((r, i) => { r.classList.toggle('is-focus', i === focusIndex); if (i === focusIndex) r.scrollIntoView({ block: 'nearest' }); });
        const open = () => {
            wrap.classList.add('is-open');
            button.setAttribute('aria-expanded', 'true');
            const sheet = window.matchMedia('(max-width: 720px)').matches;
            menu.classList.toggle('is-sheet', sheet);
            if (sheet) { backdrop = document.createElement('div'); backdrop.className = 'sp-backdrop'; backdrop.addEventListener('click', () => close()); document.body.appendChild(backdrop); }
            menu.hidden = false;
            render();
            if (!searchable) rows()[focusIndex]?.focus({ preventScroll: true });
        };
        const close = (refocus = true) => {
            wrap.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
            backdrop?.remove(); backdrop = null;
            if (refocus) button.focus({ preventScroll: true });
        };
        const choose = (value) => {
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            paint();
            close();
        };
        button.addEventListener('click', () => (menu.hidden ? open() : close()));
        button.addEventListener('keydown', (event) => { if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); open(); } });
        menu.addEventListener('keydown', (event) => {
            const list = rows();
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            else if (event.key === 'ArrowDown') { event.preventDefault(); focusIndex = Math.min(list.length - 1, focusIndex + 1); mark(); list[focusIndex]?.focus({ preventScroll: true }); if (searchable) search.focus({ preventScroll: true }); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); focusIndex = Math.max(0, focusIndex - 1); mark(); list[focusIndex]?.focus({ preventScroll: true }); if (searchable) search.focus({ preventScroll: true }); }
            else if (event.key === 'Enter' && searchable && list[focusIndex]) { event.preventDefault(); choose(list[focusIndex].dataset.value); }
            else if (event.key === 'Tab') close(false);
        });
        document.addEventListener('click', (event) => { if (!menu.hidden && !wrap.contains(event.target)) close(false); });
        paint();
    });

    /* ---------- Attachments: check each file before it is sent ---------- */
    $$('[data-sp-files]').forEach((box) => {
        const input = $('input[type=file]', box);
        const chipList = $('.sp-files', box);
        const errorLine = $('[data-sp-files-error]', box);
        const zone = $('.sp-drop', box);
        const MAX = 2; const LIMIT = 5 * 1024 * 1024;
        const TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        let files = [];
        const size = (bytes) => (bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB');
        const sync = () => {
            const transfer = new DataTransfer();
            files.forEach((f) => transfer.items.add(f));
            input.files = transfer.files;
            chipList.innerHTML = '';
            files.forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'sp-file';
                li.innerHTML = '<span></span><button type="button">' + ICON.close + '</button>';
                li.firstElementChild.textContent = `${file.name} (${size(file.size)})`;
                const remove = li.querySelector('button');
                remove.setAttribute('aria-label', 'Remove ' + file.name);
                remove.addEventListener('click', () => { files.splice(index, 1); sync(); showError(''); });
                chipList.appendChild(li);
            });
        };
        const showError = (message) => {
            errorLine.textContent = message;
            errorLine.hidden = !message;
            box.classList.toggle('has-error', !!message);
        };
        const add = (picked) => {
            const problems = [];
            picked.forEach((file) => {
                const type = file.type || (/\.pdf$/i.test(file.name) ? 'application/pdf' : '');
                if (!TYPES.includes(type)) problems.push(`${file.name} is not a supported file. Attach a JPG, PNG, WebP or PDF.`);
                else if (file.size > LIMIT) problems.push(`${file.name} is ${size(file.size)}. Attach a file under 5 MB.`);
                else if (files.length >= MAX) problems.push(`You can attach ${MAX} files. Remove one to add ${file.name}.`);
                else files.push(file);
            });
            sync();
            showError(problems.join(' '));
            if (problems.length) banner('error', 'A file was not attached', problems[0]);
        };
        // The browser replaces the input's list on every pick, so new files are added to the ones already accepted.
        input.addEventListener('change', () => add([...input.files].filter((f) => !files.includes(f))));
        ['dragenter', 'dragover'].forEach((name) => zone.addEventListener(name, (e) => { e.preventDefault(); zone.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach((name) => zone.addEventListener(name, (e) => { e.preventDefault(); zone.classList.remove('is-over'); }));
        zone.addEventListener('drop', (event) => add([...event.dataTransfer.files]));
        sync();
    });

    /* ---------- Form checks that say how to fix the problem ---------- */
    $$('form[data-sp-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let first = null;
            $$('[data-sp-min]', form).forEach((field) => {
                const holder = field.closest('.sp-field');
                const line = $('.sp-error[data-sp-live]', holder) || holder.appendChild(Object.assign(document.createElement('p'), { className: 'sp-error' }));
                line.dataset.spLive = '1';
                const min = Number(field.dataset.spMin);
                const length = field.value.trim().length;
                const message = length === 0 ? field.dataset.spEmpty : (length < min ? field.dataset.spShort : '');
                line.textContent = message;
                line.hidden = !message;
                holder.classList.toggle('has-error', !!message);
                if (message && !first) first = field;
            });
            if (first) {
                event.preventDefault();
                first.focus();
                banner('error', 'Request not sent', 'Fix the highlighted fields, then select Send again.');
            }
        });
        $$('[data-sp-min]', form).forEach((field) => field.addEventListener('input', () => {
            const holder = field.closest('.sp-field');
            holder.classList.remove('has-error');
            const line = $('.sp-error[data-sp-live]', holder); if (line) line.hidden = true;
        }));
    });
    $$('[data-sp-count]').forEach((field) => {
        const out = document.getElementById(field.dataset.spCount);
        const update = () => { if (out) out.textContent = `${field.value.length} / ${field.maxLength}`; };
        field.addEventListener('input', update); update();
    });

    /* ---------- Dialogs ---------- */
    $$('[data-sp-open]').forEach((trigger) => trigger.addEventListener('click', () => document.getElementById(trigger.dataset.spOpen)?.showModal()));
    $$('dialog.sp-dialog').forEach((dialog) => {
        $$('[data-sp-close]', dialog).forEach((b) => b.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    });
})();
