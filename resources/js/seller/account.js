// Seller · Account: tabs, photo/banner upload preview, counters, password rules, loading states.
const page = document.querySelector('.ac-page');

const banner = options => { if (typeof window.sellerBanner === 'function') window.sellerBanner(options); };

if (page) {
    // ---- Flash message (and one carried over a reload, like a category request) ----
    const flash = page.querySelector('[data-flash]');
    if (flash) banner({ type: 'success', message: flash.dataset.flash });
    const carried = sessionStorage.getItem('ac-flash');
    if (carried) { sessionStorage.removeItem('ac-flash'); banner({ type: 'success', message: carried }); }

    // ---- Tabs (all panels stay visible without JS) ----
    const tabs = [...page.querySelectorAll('[data-ac-tab]')];
    const panels = [...page.querySelectorAll('[data-ac-panel]')];
    const names = tabs.map(tab => tab.dataset.acTab);
    const show = (name, { focus = false, push = true } = {}) => {
        if (!names.includes(name)) name = names[0];
        tabs.forEach(tab => {
            const active = tab.dataset.acTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            if (active && focus) tab.focus();
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.acPanel !== name; });
        if (push) history.replaceState(null, '', '#' + name);
    };
    tabs.forEach(tab => tab.setAttribute('role', 'tab'));
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab.dataset.acTab));
        tab.addEventListener('keydown', event => {
            const move = { ArrowRight: 1, ArrowLeft: -1 }[event.key];
            if (move) { event.preventDefault(); show(names[(index + move + names.length) % names.length], { focus: true }); }
        });
    });
    const fromHash = location.hash.slice(1);
    show(names.includes(fromHash) ? fromHash : page.dataset.initialTab, { push: false });
    window.addEventListener('hashchange', () => show(location.hash.slice(1), { push: false }));

    // ---- Click-to-edit menus (profile photo and banner) ----
    const triggers = [...page.querySelectorAll('[data-menu]')];
    const closeMenus = (except = null, returnFocus = false) => {
        triggers.forEach(trigger => {
            const menu = document.getElementById(trigger.dataset.menu);
            if (!menu || menu === except) return;
            if (!menu.hidden && returnFocus) trigger.focus();
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        });
    };
    triggers.forEach(trigger => {
        const menu = document.getElementById(trigger.dataset.menu);
        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const willOpen = menu.hidden;
            closeMenus(menu);
            menu.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) menu.querySelector('[role="menuitem"]')?.focus();
        });
        menu.addEventListener('keydown', event => {
            const items = [...menu.querySelectorAll('[role="menuitem"]')];
            const at = items.indexOf(document.activeElement);
            if (event.key === 'ArrowDown') { event.preventDefault(); items[(at + 1) % items.length].focus(); }
            if (event.key === 'ArrowUp') { event.preventDefault(); items[(at - 1 + items.length) % items.length].focus(); }
            if (event.key === 'Tab') closeMenus();
        });
    });
    document.addEventListener('click', event => { if (!event.target.closest('.ac-menu')) closeMenus(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenus(null, true); });

    // ---- Crop and reposition editor (no library, plain canvas) ----
    const crop = (() => {
        const dialog = document.getElementById('acCrop');
        if (!dialog) return null;
        const stage = document.getElementById('acCropStage');
        const img = document.getElementById('acCropImg');
        const zoomInput = document.getElementById('acCropZoom');
        const title = document.getElementById('acCropTitle');
        const specs = {
            avatar: { w: 512, h: 512, title: 'Adjust profile photo', name: 'profile-photo.jpg', apply: 'Save photo' },
            banner: { w: 1600, h: 320, title: 'Adjust shop banner', name: 'shop-banner.jpg', apply: 'Save banner' },
        };
        let spec, objectUrl, settle, applied = false;
        let nw = 0, nh = 0, sw = 0, sh = 0, base = 1, zoom = 1, x = 0, y = 0;
        const pointers = new Map();
        let drag = null, pinch = null;

        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const scale = () => base * zoom;
        const layout = () => {
            const k = scale();
            x = clamp(x, sw - nw * k, 0);
            y = clamp(y, sh - nh * k, 0);
            img.style.width = nw * k + 'px';
            img.style.height = nh * k + 'px';
            img.style.transform = `translate(${x}px, ${y}px)`;
            zoomInput.value = zoom;
        };
        const zoomAt = (px, py, next) => {
            next = clamp(next, 1, 4);
            const k = scale();
            const ix = (px - x) / k, iy = (py - y) / k;
            zoom = next;
            x = px - ix * scale();
            y = py - iy * scale();
            layout();
        };
        const reset = () => {
            zoom = 1;
            x = (sw - nw * base) / 2;
            y = (sh - nh * base) / 2;
            layout();
        };
        const finish = value => {
            if (!settle) return;
            const done = settle;
            settle = null;
            applied = true;
            done(value);
            if (dialog.open) dialog.close();
        };
        const cleanup = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; img.removeAttribute('src'); };

        const point = event => { const rect = stage.getBoundingClientRect(); return [event.clientX - rect.left, event.clientY - rect.top]; };
        stage.addEventListener('pointerdown', event => {
            stage.setPointerCapture(event.pointerId);
            pointers.set(event.pointerId, point(event));
            if (pointers.size === 1) drag = { sx: pointers.get(event.pointerId)[0], sy: pointers.get(event.pointerId)[1], x, y };
            if (pointers.size === 2) {
                const [a, b] = [...pointers.values()];
                pinch = { dist: Math.hypot(a[0] - b[0], a[1] - b[1]), zoom };
                drag = null;
            }
            stage.classList.add('is-dragging');
        });
        stage.addEventListener('pointermove', event => {
            if (!pointers.has(event.pointerId)) return;
            pointers.set(event.pointerId, point(event));
            if (pointers.size === 2 && pinch) {
                const [a, b] = [...pointers.values()];
                const dist = Math.hypot(a[0] - b[0], a[1] - b[1]);
                zoomAt((a[0] + b[0]) / 2, (a[1] + b[1]) / 2, pinch.zoom * (dist / pinch.dist));
            } else if (drag) {
                const [px, py] = pointers.get(event.pointerId);
                x = drag.x + (px - drag.sx);
                y = drag.y + (py - drag.sy);
                layout();
            }
        });
        const release = event => {
            pointers.delete(event.pointerId);
            pinch = null;
            drag = null;
            if (pointers.size === 1) { const [px, py] = [...pointers.values()][0]; drag = { sx: px, sy: py, x, y }; }
            if (!pointers.size) stage.classList.remove('is-dragging');
        };
        stage.addEventListener('pointerup', release);
        stage.addEventListener('pointercancel', release);
        stage.addEventListener('wheel', event => {
            event.preventDefault();
            const [px, py] = point(event);
            zoomAt(px, py, zoom * (event.deltaY < 0 ? 1.08 : 1 / 1.08));
        }, { passive: false });
        stage.addEventListener('keydown', event => {
            const step = event.shiftKey ? 30 : 10;
            const moves = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
            if (moves[event.key]) { event.preventDefault(); x += moves[event.key][0]; y += moves[event.key][1]; layout(); }
            if (event.key === '+' || event.key === '=') { event.preventDefault(); zoomAt(sw / 2, sh / 2, zoom + 0.1); }
            if (event.key === '-') { event.preventDefault(); zoomAt(sw / 2, sh / 2, zoom - 0.1); }
        });
        zoomInput.addEventListener('input', () => zoomAt(sw / 2, sh / 2, Number(zoomInput.value)));
        dialog.querySelector('[data-zoom-in]').addEventListener('click', () => zoomAt(sw / 2, sh / 2, zoom + 0.2));
        dialog.querySelector('[data-zoom-out]').addEventListener('click', () => zoomAt(sw / 2, sh / 2, zoom - 0.2));
        dialog.querySelector('[data-crop-reset]').addEventListener('click', reset);
        dialog.querySelectorAll('[data-crop-cancel]').forEach(button => button.addEventListener('click', () => finish(null)));
        dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); });
        dialog.addEventListener('close', () => { cleanup(); if (settle) { const done = settle; settle = null; done(null); } });

        dialog.querySelector('[data-crop-apply]').addEventListener('click', () => {
            const canvas = document.createElement('canvas');
            canvas.width = spec.w;
            canvas.height = spec.h;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            const k = scale();
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, -x / k, -y / k, sw / k, sh / k, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => finish(blob ? new File([blob], spec.name, { type: 'image/jpeg' }) : null), 'image/jpeg', 0.9);
        });

        // Resolves with a cropped File, or null when the person cancels. Rejects if the image cannot be read.
        return {
            open(source, kind) {
                return new Promise((resolve, reject) => {
                    spec = specs[kind];
                    stage.dataset.kind = kind;
                    title.textContent = spec.title;
                    dialog.querySelector('[data-crop-apply-label]').textContent = spec.apply;
                    objectUrl = URL.createObjectURL(source);
                    img.onload = () => {
                        nw = img.naturalWidth;
                        nh = img.naturalHeight;
                        settle = resolve;
                        dialog.showModal();
                        sw = stage.clientWidth;
                        sh = stage.clientHeight;
                        base = Math.max(sw / nw, sh / nh);
                        reset();
                        stage.focus();
                    };
                    img.onerror = () => { cleanup(); reject(new Error('unreadable')); };
                    img.src = objectUrl;
                });
            },
        };
    })();

    // ---- Upload: pick a file, crop it, and save it right away ----
    page.querySelectorAll('[data-upload]').forEach(form => {
        const kind = form.dataset.upload;
        const input = form.querySelector('[data-upload-input]');
        const error = form.querySelector('[data-upload-error]');
        const maxBytes = Number(form.dataset.maxMb) * 1024 * 1024;
        const target = document.getElementById(kind === 'banner' ? 'swBannerPreview' : 'swAvatarPreview');
        const busyHost = kind === 'banner' ? target.parentElement : target.closest('.ac-avatar-btn');

        const pickButton = page.querySelector(`[data-pick="${kind}"]`);
        const adjustButton = page.querySelector(`[data-adjust="${kind}"]`);

        const setFile = file => {
            const transfer = new DataTransfer();
            if (file) transfer.items.add(file);
            input.files = transfer.files;
        };
        const showPreview = file => {
            const url = URL.createObjectURL(file);
            if (kind === 'banner') target.style.backgroundImage = `url("${url}")`;
            else if (target.tagName === 'IMG') target.src = url;
            else { target.textContent = ''; target.style.backgroundImage = `url("${url}")`; target.style.backgroundSize = 'cover'; }
        };
        const fail = message => {
            setFile(null);
            input.value = '';
            error.textContent = message;
            error.hidden = false;
            form.hidden = false;
            clearTimeout(form._timer);
            form._timer = setTimeout(() => { form.hidden = true; error.hidden = true; }, 7000);
        };

        const edit = async source => {
            form.hidden = true;
            error.hidden = true;
            let cropped;
            try { cropped = await crop.open(source, kind); }
            catch { return fail('That image could not be read. Choose a different JPG, PNG, or WebP file.'); }
            if (!cropped) { setFile(null); input.value = ''; return; }   // cancelled: nothing changes
            if (cropped.size > (kind === 'banner' ? 4 : 2) * 1024 * 1024) return fail('That crop is too large to save. Zoom in a little or pick a smaller image.');
            setFile(cropped);
            showPreview(cropped);
            busyHost?.classList.add('is-saving');
            banner({ type: 'info', message: kind === 'banner' ? 'Saving your banner…' : 'Saving your photo…', timeout: 8000 });
            form.requestSubmit();
        };

        pickButton?.addEventListener('click', () => { closeMenus(); input.click(); });
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file || file.name === 'profile-photo.jpg' || file.name === 'shop-banner.jpg') return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return fail('That file type is not supported. Choose a JPG, PNG, or WebP image.');
            if (file.size > maxBytes) return fail(`That image is over ${form.dataset.maxMb} MB. Choose a smaller one.`);
            edit(file);
        });
        adjustButton?.addEventListener('click', async () => {
            closeMenus();
            try {
                const response = await fetch(form.dataset.current, { credentials: 'same-origin' });
                if (!response.ok) throw new Error('missing');
                edit(await response.blob());
            } catch { fail('The current image did not load. Upload it again to reposition it.'); }
        });
    });

    // ---- Description counter ----
    page.querySelectorAll('[data-count-field]').forEach(field => {
        const counter = field.closest('label').querySelector('[data-count]');
        const update = () => {
            counter.textContent = `${field.value.length} / ${field.maxLength}`;
            counter.classList.toggle('is-near', field.value.length > field.maxLength * 0.9);
        };
        field.addEventListener('input', update);
        update();
    });

    // ---- Password rules, match check, show/hide ----
    const passwordForm = page.querySelector('[data-ac-password]');
    if (passwordForm) {
        const fresh = passwordForm.querySelector('[data-new-pass]');
        const confirm = passwordForm.querySelector('[data-confirm-pass]');
        const matchError = passwordForm.querySelector('[data-match-error]');
        const rules = {
            length: v => v.length >= 8,
            case: v => /[a-z]/.test(v) && /[A-Z]/.test(v),
            number: v => /\d/.test(v),
            symbol: v => /[^A-Za-z0-9]/.test(v),
        };
        const check = () => {
            Object.entries(rules).forEach(([name, test]) => {
                passwordForm.querySelector(`[data-rule="${name}"]`).classList.toggle('is-met', test(fresh.value));
            });
            matchError.hidden = !confirm.value || confirm.value === fresh.value;
        };
        fresh.addEventListener('input', check);
        confirm.addEventListener('input', check);
        check();
        passwordForm.addEventListener('click', event => {
            const eye = event.target.closest('[data-toggle-pass]');
            if (!eye) return;
            const input = eye.previousElementSibling;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            eye.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            eye.classList.toggle('is-on', !showing);
        });
        passwordForm.addEventListener('submit', event => {
            if (confirm.value !== fresh.value) { event.preventDefault(); matchError.hidden = false; confirm.focus(); }
        });
    }

    // ---- Spinner on save buttons ----
    page.querySelectorAll('form:not([data-upload])').forEach(form => form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        const button = event.submitter || form.querySelector('[data-ac-submit]');
        if (button) setTimeout(() => { button.classList.add('is-loading'); button.disabled = true; }, 0);
    }));
    window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
}
