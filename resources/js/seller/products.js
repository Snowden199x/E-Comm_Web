/* Vendo Seller – Products & Inventory, product modal, Add/Edit Product form. */
import { showRestoredDraftNotice, clearRestoredDraftNotice } from '../shared/draft-notice.js';
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const csrf = () => $('meta[name="csrf-token"]')?.content ?? '';
const el = (tag, props = {}, ...kids) => {
    // `dataset` is read-only and `ariaLabel`/`style` are not reliably assignable, so they are applied explicitly.
    const { dataset, ariaLabel, style, ...rest } = props;
    const node = Object.assign(document.createElement(tag), rest);
    if (dataset) Object.assign(node.dataset, dataset);
    if (ariaLabel) node.setAttribute('aria-label', ariaLabel);
    if (style) node.setAttribute('style', style);
    kids.forEach(k => node.append(k));
    return node;
};
const peso = n => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ------------------------------------------------------------------ toast */
let toastTimer;
function toast(message) {
    const box = $('#piToast');
    if (!box) return;
    box.textContent = message;
    box.classList.add('is-show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => box.classList.remove('is-show'), 6000);
}
try {
    const message = sessionStorage.getItem('sellerOperationMessage');
    if (message) { toast(message); sessionStorage.removeItem('sellerOperationMessage'); }
} catch { /* storage optional */ }

async function postForm(url, body, onUploadProgress = null) {
    if (onUploadProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
            xhr.upload.addEventListener('progress', event => {
                if (event.lengthComputable) onUploadProgress(event.loaded, event.total);
            });
            xhr.upload.addEventListener('load', () => onUploadProgress(null, null, true));
            xhr.addEventListener('load', () => {
                let data = {};
                let validJson = false;
                try { data = JSON.parse(xhr.responseText); validJson = true; } catch { /* Non-JSON responses are reported clearly below. */ }
                if (xhr.status >= 200 && xhr.status < 300 && validJson) return resolve(data);
                if (xhr.status >= 200 && xhr.status < 300) {
                    return reject(new Error('The upload reached the server, but it returned an unreadable response. Check the PHP request-size limits and try again.'));
                }
                let message = data.message || 'Unable to save. Please try again.';
                if (xhr.status === 419) message = 'Your session expired. Reload the page and sign in again.';
                else if (xhr.status === 413) message = 'The upload is larger than the PHP or web server request limit. Check upload_max_filesize and post_max_size.';
                else if (!Object.keys(data).length && xhr.status >= 500) message = 'The server could not process this upload. Check the local PHP upload limits and server error log.';
                const error = new Error(message);
                error.errors = data.errors || null;
                reject(error);
            });
            xhr.addEventListener('error', () => reject(new Error('The upload connection failed. Check your connection and PHP upload limits, then try again.')));
            xhr.addEventListener('abort', () => reject(new Error('The upload was interrupted. Please try again.')));
            xhr.send(body);
        });
    }

    const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const err = new Error(response.status === 419 ? 'Your session expired. Reload the page and sign in again.' : (data.message || 'Unable to save. Please try again.'));
        err.errors = data.errors || null;
        throw err;
    }
    return data;
}

// Restock form (works inside the modal and on the direct-visit details page).
document.addEventListener('submit', async e => {
    const form = e.target.closest('form[data-restock]');
    if (!form) return;
    e.preventDefault();
    const err = $('[data-form-error]', form);
    const btn = $('button[type=submit]', form);
    err.classList.remove('is-show');
    btn.disabled = true; btn.classList.add('is-loading');
    try {
        const data = await postForm(form.action, new FormData(form));
        form.dispatchEvent(new Event('vendo:draft-committed'));
        try { sessionStorage.setItem('sellerOperationMessage', data.message || 'Stock updated.'); } catch { /* optional */ }
        location.reload();
    } catch (ex) {
        err.textContent = ex.errors ? Object.values(ex.errors).flat().join(' ') : ex.message;
        err.classList.add('is-show');
        btn.disabled = false; btn.classList.remove('is-loading');
    }
});

/* =========================================================== LIST PAGE */
const modal = $('#piModal');
if (modal) initList();

function initList() {
    const body = $('#piModalBody');
    const title = $('#piModalTitle');
    let pending, lastTrigger, selectedRow;

    async function open(url, heading, tab) {
        pending?.abort();
        const ctrl = (pending = new AbortController());
        selectedRow?.classList.remove('is-selected');
        if (!modal.open) { modal.showModal(); document.body.style.overflow = 'hidden'; }
        title.textContent = heading || 'Product Details';
        body.setAttribute('aria-busy', 'true');
        body.replaceChildren(...[80, 100, 60, 90].map(w => el('div', { className: 'pi-skeleton', style: `width:${w}%` })));
        try {
            const res = await fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal });
            if (res.redirected) { location.assign(res.url); return; }
            if (!res.ok) throw new Error(res.status === 404 ? 'This product is no longer available.' : 'Unable to open details. Please close and try again.');
            const html = await res.text();
            if (ctrl.signal.aborted) return;
            body.innerHTML = html;
            const foot = $('[data-modal-footer]', body);
            const footSlot = $('#piModalFoot');
            footSlot.replaceChildren(...(foot ? [...foot.childNodes] : []));
            foot?.remove();
            footSlot.hidden = !footSlot.childNodes.length;
            activateTab(tab || $('[data-initial-tab]', body)?.dataset.initialTab || 'details');
            body.scrollTop = 0;
        } catch (e) {
            if (e.name !== 'AbortError') body.replaceChildren(el('p', { className: 'pi-callout', textContent: e.message }));
        } finally {
            if (pending === ctrl) body.removeAttribute('aria-busy');
        }
    }

    function activateTab(name) {
        $$('[data-tab]', body).forEach(b => {
            const on = b.dataset.tab === name;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on);
        });
        $$('[data-tabpanel]', body).forEach(p => { p.hidden = p.dataset.tabpanel !== name; });
    }

    // Open modal from product name / row / menu entries.
    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-product-modal]');
        const row = !trigger && !e.target.closest('a, button, .pi-menu') && e.target.closest('tr[data-row-url]');
        const source = trigger || row;
        if (source) {
            e.preventDefault();
            closeMenus();
            lastTrigger = source;
            selectedRow = source.closest('tr');
            selectedRow?.classList.add('is-selected');
            open(source.dataset.productModal || source.dataset.rowUrl, source.dataset.modalTitle, source.dataset.tab);
            return;
        }
        const tab = e.target.closest('[data-tab]');
        if (tab && modal.contains(tab)) activateTab(tab.dataset.tab);
        const thumb = e.target.closest('[data-gallery-src]');
        if (thumb) {
            $('[data-gallery-main]', body).replaceChildren(el('img', { src: thumb.dataset.gallerySrc, alt: thumb.dataset.galleryAlt || '' }));
            $$('[data-gallery-src]', body).forEach(t => t.classList.toggle('is-active', t === thumb));
        }
        const nav = e.target.closest('[data-modal-nav]');
        if (nav) { e.preventDefault(); open(nav.href, title.textContent, nav.dataset.tab); }
        if (e.target.closest('[data-close-modal]')) modal.close();
        if (e.target === modal) modal.close(); // backdrop click
    });
    modal.addEventListener('close', () => {
        pending?.abort();
        document.body.style.overflow = '';
        selectedRow?.classList.remove('is-selected');
        lastTrigger?.focus?.();
    });

    // Row kebab menus (fixed so the scroll container doesn't clip them).
    const closeMenus = () => $$('.pi-menu.is-open').forEach(m => m.classList.remove('is-open'));
    document.addEventListener('click', e => {
        const kebab = e.target.closest('[data-kebab]');
        if (!kebab) { if (!e.target.closest('.pi-menu')) closeMenus(); return; }
        e.stopPropagation();
        const menu = document.getElementById(kebab.dataset.kebab);
        const wasOpen = menu.classList.contains('is-open');
        closeMenus();
        if (wasOpen) return;
        const r = kebab.getBoundingClientRect();
        menu.style.top = `${Math.min(r.bottom + 6, innerHeight - 170)}px`;
        menu.style.left = `${Math.max(8, r.right - 190)}px`;
        menu.classList.add('is-open');
    });
    addEventListener('scroll', closeMenus, true);
    addEventListener('resize', closeMenus);

    // Filters: selects submit instantly, search submits after a short pause.
    const filters = $('#piFilters');
    let timer;
    $$('select', filters).forEach(s => s.addEventListener('change', () => filters.requestSubmit()));
    $('input[type=search]', filters)?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => filters.requestSubmit(), 600); });
}

/* =========================================================== PRODUCT FORM */
const formEl = $('#piProductForm');
if (formEl) initProductForm(formEl);

function initProductForm(form) {
    const cfg = JSON.parse($('#piConfig').textContent);
    const schema = cfg.schema || {};
    const categoryKey = cfg.categoryKeys || {};
    const limits = { maxImages: +form.dataset.maxImages || 6, maxMb: +form.dataset.maxMb || 2, totalMb: +form.dataset.totalMb || 10, videoMb: +form.dataset.videoMb || 10, videoSec: +form.dataset.videoSec || 60 };
    const isEdit = form.dataset.mode === 'edit';
    const MAX_VAR_TYPES = 3, MAX_OPTIONS = 20, MAX_VARIANTS = 100;
    const productDraftKey = `vendo.product.${form.dataset.productDraftKey}`;
    // Remove the older generic snapshot; this form has its own draft handler.
    try { sessionStorage.removeItem(`vendo.form.${form.dataset.productDraftKey}`); } catch { /* storage is optional */ }
    let productDraftReady = false, productDraftDirty = false, productDraftSaved = false, productDraftTimer;

    const state = {
        images: (cfg.existingImages || []).map(i => ({ kind: 'existing', id: i.id, url: i.url })),
        video: cfg.videoUrl ? { file: null, url: cfg.videoUrl } : null,
        removeVideo: false,
        hasVariations: false,
        types: cfg.variationTypes || [], // [{ name, options: [] }]
        variants: cfg.variants || {}, // label -> { price, stock, sku, image: File|null, url }
    };
    const errors = new Map(); // key -> { section, message }
    const fieldOf = key => $(`[data-field="${key}"]`, form);

    /* ---------- errors ---------- */
    function showError(key, message) {
        const box = $(`[data-error-for="${key}"]`, form);
        if (box) { box.textContent = message; box.classList.add('is-show'); }
        fieldOf(key)?.classList.add('is-invalid');
    }
    function clearError(key) {
        const box = $(`[data-error-for="${key}"]`, form);
        box?.classList.remove('is-show');
        fieldOf(key)?.classList.remove('is-invalid');
    }
    const clearAllErrors = () => { $$('.pi-error.is-show', form).forEach(b => b.classList.remove('is-show')); $$('.is-invalid', form).forEach(n => n.classList.remove('is-invalid')); $('#piFormError').classList.remove('is-show'); };
    const fail = (key, section, message) => errors.set(key, { section, message });

    /* ---------- basics ---------- */
    const desc = $('[name=description]', form), descCount = $('#piDescCount');
    const updateDescCount = () => { descCount.textContent = `${desc.value.length} / 5000`; };
    desc.addEventListener('input', updateDescCount); updateDescCount();

    const brand = $('[name=brand]', form), noBrand = $('#piNoBrand');
    const syncBrand = () => { brand.disabled = noBrand.checked; if (noBrand.checked) { brand.value = ''; clearError('brand'); } };
    noBrand.addEventListener('change', syncBrand); syncBrand();

    /* ---------- category fields ---------- */
    const catSelect = $('#piCategory'), subSelect = $('#piSubcategory'), subReq = $('#piSubReq'), catIdInput = $('#piCategoryId');
    const subs = cfg.subcategories || {};
    const subsOf = () => subs[catSelect.value] || [];
    const syncCategoryId = () => { catIdInput.value = subSelect.value || catSelect.value; }; // most specific category wins
    function renderSubs(selected = '') {
        const list = subsOf();
        subSelect.replaceChildren(el('option', { value: '', textContent: !catSelect.value ? 'Select a category first' : list.length ? 'Select subcategory' : 'No subcategories' }),
            ...list.map(c => el('option', { value: c.id, textContent: c.name, selected: String(c.id) === String(selected) })));
        subSelect.disabled = !list.length;
        subReq.hidden = !list.length;
        syncCategoryId();
    }
    const catWrap = $('#piCatFields'), catEmpty = $('#piCatEmpty'), hintsHost = $('#piVarHints');
    const subKeys = cfg.subcategoryKeys || {};
    // A subcategory with its own schema (e.g. Makeup & Cosmetics) wins over its main category's schema.
    const catName = () => { const sk = subKeys[subSelect.value]; return sk && schema[sk] ? sk : (catSelect.selectedOptions[0]?.dataset.key || categoryKey[catSelect.value] || ''); };
    let lastKey = '';
    const savedAttrs = cfg.attributes || {};

    function fieldNode(f, i) {
        const id = `attr_${f.key}`;
        const wrap = el('div', { className: 'pi-field' + (['textarea'].includes(f.type) ? ' pi-span' : ''), style: `--i:${i}` });
        wrap.append(el('label', { className: 'pi-label', htmlFor: id, innerHTML: f.label + (f.required ? '<em>*</em>' : '') }));
        let input;
        const val = savedAttrs[f.key] ?? '';
        if (f.type === 'select') {
            input = el('select', { id, className: 'pi-input' });
            input.append(el('option', { value: '', textContent: 'Select…' }), ...f.options.map(o => el('option', { value: o, textContent: o, selected: o === val })));
        } else if (f.type === 'textarea') {
            input = el('textarea', { id, className: 'pi-input', rows: 3, maxLength: 1000, placeholder: f.placeholder || '', value: val });
        } else if (f.type === 'yesno') {
            input = el('div', { className: 'pi-seg' });
            ['Yes', 'No'].forEach(o => input.append(el('label', { innerHTML: `<input type="radio" name="attributes[${f.key}]" value="${o}" ${o === val ? 'checked' : ''}><span>${o}</span>` })));
            wrap.append(input, el('div', { className: 'pi-error', dataset: { errorFor: `attr.${f.key}` } }));
            return wrap;
        } else if (f.type === 'chips') {
            input = el('div', { className: 'pi-chips' });
            const chosen = Array.isArray(val) ? val : String(val).split(',').map(s => s.trim());
            f.options.forEach(o => input.append(el('label', { className: 'pi-chipopt', innerHTML: `<input type="checkbox" name="attributes[${f.key}][]" value="${o}" ${chosen.includes(o) ? 'checked' : ''}><span>${o}</span>` })));
            wrap.append(input, el('div', { className: 'pi-error', dataset: { errorFor: `attr.${f.key}` } }));
            return wrap;
        } else {
            input = el('input', { id, className: 'pi-input', type: f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : 'text', placeholder: f.placeholder || '', value: val, maxLength: 255 });
            if (f.type === 'number') { if (f.min != null) input.min = f.min; if (f.max != null) input.max = f.max; input.step = 1; }
            if (f.type === 'date') input.min = new Date(Date.now() + 864e5).toISOString().slice(0, 10);
            if (f.suggest) { const dl = el('datalist', { id: `${id}_list` }); f.suggest.forEach(s => dl.append(el('option', { value: s }))); input.setAttribute('list', dl.id); wrap.append(dl); }
        }
        input.name = `attributes[${f.key}]`;
        input.dataset.field = `attr.${f.key}`;
        wrap.append(input);
        if (f.help) wrap.append(el('div', { className: 'pi-help', textContent: f.help }));
        wrap.append(el('div', { className: 'pi-error', dataset: { errorFor: `attr.${f.key}` } }));
        return wrap;
    }
    function renderCategory() {
        lastKey = catName();
        const def = schema[lastKey];
        catWrap.replaceChildren();
        hintsHost.replaceChildren();
        if (!catSelect.value) { catEmpty.hidden = false; catWrap.hidden = true; catEmpty.textContent = 'Choose a category in Basic Information to see the details buyers expect for it.'; return; }
        if (!def) { catEmpty.hidden = false; catWrap.hidden = true; catEmpty.textContent = 'No predefined details for this category. Use Additional Specifications below.'; return; }
        catEmpty.hidden = true; catWrap.hidden = false;
        def.fields.forEach((f, i) => catWrap.append(fieldNode(f, i)));
        (def.variation_hints || []).forEach(h => hintsHost.append(el('button', { type: 'button', textContent: `+ ${h}`, onclick: () => addType(h) })));
        hintsHost.hidden = !def.variation_hints?.length;
        hintsHost.prepend(el('span', { textContent: 'Suggested:' }));
        schemaDirty();
    }
    let lastCat = catSelect.value;
    catSelect.addEventListener('change', () => {
        clearError('category_id'); clearError('subcategory_id');
        if (catSelect.value !== lastCat) { lastCat = catSelect.value; Object.keys(savedAttrs).forEach(k => delete savedAttrs[k]); renderSubs(); renderCategory(); }
    });
    subSelect.addEventListener('change', () => {
        clearError('subcategory_id'); syncCategoryId();
        if (catName() !== lastKey) { Object.keys(savedAttrs).forEach(k => delete savedAttrs[k]); renderCategory(); }
    });

    /* ---------- images ---------- */
    const drop = $('#piDrop'), fileInput = $('#piFiles'), tiles = $('#piTiles');
    const IMG_OK = ['image/jpeg', 'image/png', 'image/webp'];
    function addImages(files) {
        const problems = [];
        [...files].forEach(f => {
            if (state.images.length >= limits.maxImages) { problems.push(`You can upload up to ${limits.maxImages} photos.`); return; }
            if (!IMG_OK.includes(f.type)) { problems.push(`${f.name}: use JPG, PNG or WebP.`); return; }
            if (f.size > limits.maxMb * 1048576) { problems.push(`${f.name}: must be ${limits.maxMb} MB or smaller.`); return; }
            state.images.push({ kind: 'new', file: f, url: URL.createObjectURL(f) });
        });
        const total = state.images.filter(i => i.kind === 'new').reduce((s, i) => s + i.file.size, 0);
        if (total > limits.totalMb * 1048576) {
            while (state.images.filter(i => i.kind === 'new').reduce((s, i) => s + i.file.size, 0) > limits.totalMb * 1048576) { const idx = state.images.map(i => i.kind).lastIndexOf('new'); URL.revokeObjectURL(state.images[idx].url); state.images.splice(idx, 1); }
            problems.push(`New photos together must be ${limits.totalMb} MB or less.`);
        }
        problems.length ? showError('images', [...new Set(problems)].join(' ')) : clearError('images');
        renderImages(); changed();
    }
    function renderImages() {
        tiles.replaceChildren(...state.images.map((img, i) => {
            const t = el('div', { className: 'pi-tile' + (i === 0 ? ' is-main' : '') });
            t.append(el('img', { src: img.url, alt: `Product photo ${i + 1}` }));
            if (i === 0) t.append(el('span', { className: 'pi-tile-badge', textContent: 'Main' }));
            const actions = el('div', { className: 'pi-tile-actions' });
            if (i > 0) actions.append(el('button', { type: 'button', textContent: 'Set as main', onclick: () => { state.images.unshift(...state.images.splice(i, 1)); renderImages(); changed(); } }));
            actions.append(el('button', { type: 'button', className: 'pi-del', textContent: 'Remove', onclick: () => { if (img.kind === 'new') URL.revokeObjectURL(img.url); state.images.splice(i, 1); renderImages(); changed(); } }));
            t.append(actions);
            return t;
        }));
        $('#piImgCount').textContent = `${state.images.length} / ${limits.maxImages} photos`;
    }
    drop.addEventListener('click', () => fileInput.click());
    drop.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
    fileInput.addEventListener('change', () => { addImages(fileInput.files); fileInput.value = ''; });
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
    drop.addEventListener('drop', e => addImages(e.dataTransfer.files));
    renderImages();

    /* ---------- video ---------- */
    const vidInput = $('#piVideoFile'), vidHost = $('#piVideoPreview');
    function renderVideo() {
        vidHost.replaceChildren();
        if (!state.video) return;
        const t = el('div', { className: 'pi-tile', style: 'max-width:200px' });
        t.append(el('video', { src: state.video.url, muted: true, controls: true, preload: 'metadata' }));
        t.append(el('div', { className: 'pi-tile-actions', style: 'opacity:1;position:static;background:none;padding:8px 0 0' }, el('button', { type: 'button', className: 'pi-del', textContent: 'Remove video', onclick: () => { if (state.video.file) URL.revokeObjectURL(state.video.url); state.video = null; state.removeVideo = true; renderVideo(); changed(); } })));
        t.style.aspectRatio = 'auto';
        vidHost.append(t);
    }
    vidInput.addEventListener('change', () => {
        const f = vidInput.files[0]; vidInput.value = '';
        if (!f) return;
        clearError('video');
        if (!['video/mp4', 'video/webm', 'video/quicktime'].includes(f.type)) return showError('video', 'Use an MP4, WebM or MOV video.');
        if (f.size > limits.videoMb * 1048576) return showError('video', `Video must be ${limits.videoMb} MB or smaller.`);
        const url = URL.createObjectURL(f);
        const probe = el('video', { preload: 'metadata', src: url });
        probe.onloadedmetadata = () => {
            if (probe.duration > limits.videoSec) { URL.revokeObjectURL(url); return showError('video', `Keep the video to ${limits.videoSec} seconds or less.`); }
            if (state.video?.file) URL.revokeObjectURL(state.video.url);
            state.video = { file: f, url }; renderVideo(); changed();
        };
        probe.onerror = () => { URL.revokeObjectURL(url); showError('video', 'This video could not be read. Try another file.'); };
    });

    /* ---------- additional specifications ---------- */
    const specsHost = $('#piSpecs');
    function addSpec(name = '', value = '') {
        const row = el('div', { className: 'pi-spec' });
        const n = el('div', { className: 'pi-field' }, el('input', { className: 'pi-input', placeholder: 'Specification name (e.g. Battery Capacity)', maxLength: 60, value: name, ariaLabel: 'Specification name' }));
        const v = el('div', { className: 'pi-field' }, el('input', { className: 'pi-input', placeholder: 'Value (e.g. 5000 mAh)', maxLength: 255, value, ariaLabel: 'Specification value' }), el('div', { className: 'pi-error' }));
        n.append(el('div', { className: 'pi-error' }));
        n.firstChild.dataset.spec = 'name'; v.firstChild.dataset.spec = 'value';
        const del = el('button', { type: 'button', className: 'pi-icon-btn', ariaLabel: 'Remove specification', innerHTML: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>', onclick: () => { row.remove(); changed(); } });
        row.append(n, v, del);
        specsHost.append(row);
        return row;
    }
    $('#piAddSpec').addEventListener('click', () => { addSpec().querySelector('input').focus(); });
    (cfg.specs || []).forEach(s => addSpec(s.name, s.value));
    const specRows = () => $$('.pi-spec', specsHost).map(r => ({ row: r, name: $('[data-spec=name]', r).value.trim(), value: $('[data-spec=value]', r).value.trim() }));

    /* ---------- variations ---------- */
    const varToggle = $$('input[name=has_variations]', form);
    const simple = $('#piSimple'), varBox = $('#piVarBox'), typesHost = $('#piTypes'), tableHost = $('#piVariantTable'), bulk = $('#piBulk');
    const combos = () => {
        const valid = state.types.filter(t => t.name.trim() && t.options.length);
        if (!valid.length) return [];
        return valid.reduce((acc, t) => acc.flatMap(prev => t.options.map(o => [...prev, { type: t.name.trim(), option: o }])), [[]]);
    };
    const labelOf = c => c.map(x => x.option).join(' / ');

    function addType(name = '') {
        if (isEdit && cfg.hasVariations) return;
        if (state.types.length >= MAX_VAR_TYPES) return toast(`You can add up to ${MAX_VAR_TYPES} variations.`);
        if (name && state.types.some(t => t.name.toLowerCase() === name.toLowerCase())) return;
        const empty = state.types.find(t => !t.name && !t.options.length);
        if (name && empty) empty.name = name; else state.types.push({ name, options: [] });
        renderTypes(); rebuildVariants();
    }
    function renderTypes() {
        typesHost.replaceChildren(...state.types.map((t, ti) => {
            const card = el('div', { className: 'pi-var' });
            card.append(el('div', { className: 'pi-var-head' }, el('span', { textContent: `Variation ${ti + 1}` }), ...((isEdit && cfg.hasVariations) ? [] : [el('button', { type: 'button', className: 'pi-btn pi-btn--sm pi-btn--ghost pi-btn--danger', textContent: 'Remove', onclick: () => { state.types.splice(ti, 1); renderTypes(); rebuildVariants(); } })])));
            const name = el('input', { className: 'pi-input pi-var-name', placeholder: 'Variation name (e.g. Color, Size, Flavor)', maxLength: 30, value: t.name, ariaLabel: 'Variation name' });
            name.dataset.field = `type.${ti}.name`;
            name.readOnly = isEdit && cfg.hasVariations;
            name.addEventListener('input', () => { t.name = name.value; clearError(`type.${ti}.name`); debounceVariants(); });
            card.append(name, el('div', { className: 'pi-error', dataset: { errorFor: `type.${ti}.name` } }));
            const opts = el('div', { className: 'pi-opts' });
            t.options.forEach((o, oi) => opts.append(el('span', { className: 'pi-tag' }, o, ...((isEdit && cfg.hasVariations) ? [] : [el('button', { type: 'button', ariaLabel: `Remove ${o}`, textContent: '×', onclick: () => { t.options.splice(oi, 1); renderTypes(); rebuildVariants(); } })]))));
            card.append(opts);
            const add = el('div', { className: 'pi-inline' });
            const optInput = el('input', { className: 'pi-input', placeholder: 'Add an option (e.g. Black)', maxLength: 30, ariaLabel: 'New option' });
            const commit = () => {
                const parts = optInput.value.split(',').map(s => s.trim()).filter(Boolean);
                let hit = false;
                parts.forEach(p => {
                    if (t.options.length >= MAX_OPTIONS) { toast(`Up to ${MAX_OPTIONS} options per variation.`); return; }
                    if (!t.options.some(x => x.toLowerCase() === p.toLowerCase())) { t.options.push(p); hit = true; }
                });
                if (hit) { renderTypes(); rebuildVariants(); card.parentNode && typesHost.children[ti]?.querySelector('input[aria-label="New option"]')?.focus(); }
                else optInput.value = '';
            };
            optInput.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); commit(); } });
            add.append(optInput, el('button', { type: 'button', className: 'pi-btn pi-btn--sm', textContent: '+ Add Option', onclick: commit }));
            if (!(isEdit && cfg.hasVariations)) card.append(add);
            card.append(el('div', { className: 'pi-error', dataset: { errorFor: `type.${ti}.options` } }));
            return card;
        }));
        $('#piAddType').hidden = state.types.length >= MAX_VAR_TYPES || (isEdit && cfg.hasVariations);
    }
    $('#piAddType').addEventListener('click', () => { addType(''); typesHost.lastElementChild?.querySelector('input')?.focus(); });

    let vt; const debounceVariants = () => { clearTimeout(vt); vt = setTimeout(rebuildVariants, 250); };
    function rebuildVariants() {
        const list = combos();
        const next = {};
        list.forEach(c => { const l = labelOf(c); next[l] = state.variants[l] || { price: '', stock: '', sku: '', image: null, url: '' }; next[l].combo = c; });
        state.variants = next;
        renderVariantTable(list.length);
        changed();
    }
    function renderVariantTable(count) {
        bulk.hidden = tableHost.hidden = !count;
        $('#piVarWarn').hidden = count <= MAX_VARIANTS;
        tableHost.replaceChildren();
        if (!count) return;
        const rows = Object.entries(state.variants).map(([label, v], i) => {
            const tr = el('tr', { style: `--i:${i}` });
            const num = (key, ph, step, min) => {
                const input = el('input', { className: 'pi-input', type: 'number', min, step, placeholder: ph, value: v[key], ariaLabel: `${key} for ${label}` });
                input.dataset.field = `variant.${label}.${key}`;
                if (key === 'stock' && isEdit && cfg.hasVariations) input.readOnly = true;
                input.addEventListener('input', () => { v[key] = input.value; clearError(`variant.${label}.${key}`); updateTotals(); changed(); });
                return el('td', {}, input, el('div', { className: 'pi-error', dataset: { errorFor: `variant.${label}.${key}` } }));
            };
            const priceCell = num('price', '0.00', '0.01', '0.01');
            const priceInput = priceCell.firstChild;
            priceCell.prepend(el('div', { className: 'pi-unit pi-unit--pre' }, el('span', { textContent: '₱' }), priceInput));
            const sku = el('input', { className: 'pi-input', placeholder: 'Assigned after submission', value: cfg.showGeneratedSku ? v.sku : '', ariaLabel: `SKU for ${label}`, readOnly: true });
            const pick = el('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp', hidden: true });
            const btn = el('button', { type: 'button', className: 'pi-vimg', ariaLabel: `Image for ${label}`, innerHTML: v.url ? `<img src="${v.url}" alt="">` : '+', disabled: isEdit && cfg.hasVariations, onclick: () => pick.click() });
            pick.addEventListener('change', () => {
                const f = pick.files[0]; if (!f) return;
                if (!IMG_OK.includes(f.type) || f.size > limits.maxMb * 1048576) return toast(`Variant image must be JPG, PNG or WebP up to ${limits.maxMb} MB.`);
                if (v.url) URL.revokeObjectURL(v.url);
                v.image = f; v.url = URL.createObjectURL(f); btn.innerHTML = `<img src="${v.url}" alt="">`; changed();
            });
            tr.append(el('td', { textContent: label }), priceCell, num('stock', '0', '1', '0'), el('td', {}, sku), el('td', {}, btn, pick));
            return tr;
        });
        const table = el('table', { className: 'pi-vtable' });
        table.append(el('thead', { innerHTML: '<tr><th>Variant</th><th>Price *</th><th>Stock *</th><th>SKU</th><th>Image</th></tr>' }), el('tbody', {}, ...rows));
        tableHost.append(el('div', { className: 'pi-vtable-wrap' }, table), el('div', { className: 'pi-total', id: 'piTotals' }));
        updateTotals();
    }
    function updateTotals() {
        const box = $('#piTotals'); if (!box) return;
        const vs = Object.values(state.variants);
        box.innerHTML = `${vs.length} variants · Total stock: <strong>${vs.reduce((s, v) => s + (parseInt(v.stock) || 0), 0)}</strong>`;
    }
    $('#piBulkApply').addEventListener('click', () => {
        const p = $('#piBulkPrice').value, s = $('#piBulkStock').value;
        Object.values(state.variants).forEach(v => { if (p !== '') v.price = p; if (s !== '' && !(isEdit && cfg.hasVariations)) v.stock = s; });
        renderVariantTable(Object.keys(state.variants).length); changed();
    });
    function setVariations(on) {
        state.hasVariations = on;
        simple.classList.toggle('pi-hidden', on);
        varBox.classList.toggle('pi-hidden', !on);
        $$('input, select', simple).forEach(i => { if (!(isEdit && i.name === 'stock')) i.disabled = on; });
        if (on && !state.types.length) { const first = schema[catName()]?.variation_hints?.[0]; addType(first || ''); }
        changed();
    }
    varToggle.forEach(r => r.addEventListener('change', () => setVariations(r.value === '1' && r.checked)));
    $('#piVarHints'); // populated by renderCategory
    function schemaDirty() { /* reserved: re-suggest hints when category changes */ }

    /* ---------- reload recovery ---------- */
    function draftFields() {
        const fields = {};
        for (const field of form.elements) {
            if (!field.name || field.readOnly ||
                ['file', 'hidden', 'password', 'submit', 'button'].includes(field.type)) continue;
            if (field.type === 'radio' || field.type === 'checkbox') {
                fields[field.name] ??= [];
                if (field.checked) fields[field.name].push(field.value);
            } else fields[field.name] = field.value;
        }
        return fields;
    }
    function applyDraftFields(fields) {
        for (const field of form.elements) {
            if (!field.name || field.readOnly || !(field.name in fields) ||
                ['file', 'hidden', 'password', 'submit', 'button'].includes(field.type)) continue;
            const value = fields[field.name];
            if (field.type === 'radio' || field.type === 'checkbox') field.checked = Array.isArray(value) && value.includes(field.value);
            else if (typeof value === 'string') field.value = value;
        }
    }
    function saveProductDraft() {
        if (!productDraftReady || !productDraftDirty || productDraftSaved) return;
        const draft = {
            savedAt: Date.now(),
            revision: form.elements.expected_revision?.value || null,
            fields: draftFields(),
            category: catSelect.value,
            subcategory: subSelect.value,
            noBrand: noBrand.checked,
            specs: specRows().map(({ name, value }) => ({ name, value })),
            hasVariations: state.hasVariations,
            types: state.types.map(t => ({ name: t.name, options: [...t.options] })),
            optionInputs: $$('.pi-var input[aria-label="New option"]', typesHost).map(input => input.value),
            variants: Object.fromEntries(Object.entries(state.variants).map(([label, v]) => [label, { price: v.price, stock: v.stock }])),
            imageIds: state.images.filter(i => i.kind === 'existing').map(i => i.id),
            removeVideo: state.removeVideo,
        };
        try { sessionStorage.setItem(productDraftKey, JSON.stringify(draft)); } catch { /* storage is optional */ }
    }
    function scheduleProductDraft() {
        if (!productDraftReady) return;
        productDraftDirty = true;
        clearTimeout(productDraftTimer);
        productDraftTimer = setTimeout(saveProductDraft, 250);
    }
    function restoreProductDraft() {
        let draft;
        try { draft = JSON.parse(sessionStorage.getItem(productDraftKey) || 'null'); } catch { /* optional */ }
        if (!draft) return;
        if (draft.savedAt <= Date.now() - 7200000 || draft.revision !== (form.elements.expected_revision?.value || null)) {
            try { sessionStorage.removeItem(productDraftKey); } catch { /* optional */ }
            clearRestoredDraftNotice(productDraftKey);
            return;
        }

        catSelect.value = draft.category || '';
        lastCat = catSelect.value;
        renderSubs(draft.subcategory || '');
        for (const [name, value] of Object.entries(draft.fields || {})) {
            const match = name.match(/^attributes\[([^\]]+)\]/);
            if (match) savedAttrs[match[1]] = Array.isArray(value) ? value : value;
        }
        renderCategory();
        noBrand.checked = !!draft.noBrand;
        applyDraftFields(draft.fields || {});
        syncBrand();
        syncCategoryId();

        specsHost.replaceChildren();
        (draft.specs || []).forEach(s => addSpec(s.name, s.value));
        if (!(isEdit && cfg.hasVariations)) {
            state.types = (draft.types || []).slice(0, MAX_VAR_TYPES).map(t => ({
                name: String(t.name || '').slice(0, 30),
                options: (Array.isArray(t.options) ? t.options : []).slice(0, MAX_OPTIONS),
            }));
        }
        state.hasVariations = !!draft.hasVariations;
        const variationChoice = varToggle.find(r => r.value === (state.hasVariations ? '1' : '0'));
        if (variationChoice) variationChoice.checked = true;
        setVariations(state.hasVariations);
        renderTypes();
        rebuildVariants();
        Object.entries(draft.variants || {}).forEach(([label, values]) => {
            if (!state.variants[label]) return;
            state.variants[label].price = values.price ?? state.variants[label].price;
            if (!(isEdit && cfg.hasVariations)) state.variants[label].stock = values.stock ?? state.variants[label].stock;
        });
        renderVariantTable(Object.keys(state.variants).length);
        $$('.pi-var input[aria-label="New option"]', typesHost).forEach((input, i) => { input.value = draft.optionInputs?.[i] || ''; });

        if (Array.isArray(draft.imageIds)) {
            const byId = new Map(state.images.filter(i => i.kind === 'existing').map(i => [String(i.id), i]));
            state.images = draft.imageIds.map(id => byId.get(String(id))).filter(Boolean);
            renderImages();
        }
        if (draft.removeVideo) { state.video = null; state.removeVideo = true; renderVideo(); }
        updateDescCount();
        productDraftDirty = true;
        showRestoredDraftNotice(productDraftKey, 'Your unsaved product details were restored. Select any new photos, video, or variant images again before submitting.');
    }

    /* ---------- validation ---------- */
    function validate(forSubmit) {
        errors.clear();
        const v = n => (form.elements[n]?.value ?? '').toString().trim();
        if (!v('name')) fail('name', 'basic', 'Enter a product name.');
        if (forSubmit) {
            if (!catSelect.value) fail('category_id', 'basic', 'Choose a category.');
            else if (subsOf().length && !subSelect.value) fail('subcategory_id', 'basic', 'Choose a subcategory.');
            if (!state.images.length) fail('images', 'basic', 'Add at least one product photo.');
            if (!v('description')) fail('description', 'basic', 'Enter a product description.');
            if (!form.elements.condition.value) fail('condition', 'basic', 'Select the product condition.');
            (schema[catName()]?.fields || []).forEach(f => {
                const name = `attributes[${f.key}]`;
                const value = f.type === 'chips' ? $$(`input[name="${name}[]"]:checked`, form).length : (form.elements[name]?.value ?? '').trim?.() ?? form.elements[name]?.value;
                if (f.required && !value) fail(`attr.${f.key}`, 'category', `${f.label} is required.`);
            });
            if (state.hasVariations) {
                state.types.forEach((t, i) => {
                    if (!t.name.trim()) fail(`type.${i}.name`, 'variations', 'Enter a variation name.');
                    if (!t.options.length) fail(`type.${i}.options`, 'variations', 'Add at least one option.');
                });
                const dup = state.types.map(t => t.name.trim().toLowerCase()).filter(Boolean);
                if (new Set(dup).size !== dup.length) fail('type.0.name', 'variations', 'Variation names must be different.');
                const entries = Object.entries(state.variants);
                if (!entries.length) fail('type.0.options', 'variations', 'Add variation options to create variants.');
                if (entries.length > MAX_VARIANTS) fail('type.0.options', 'variations', `Too many combinations (max ${MAX_VARIANTS}). Remove some options.`);
                entries.forEach(([l, x]) => {
                    if (x.price === '' || +x.price <= 0) fail(`variant.${l}.price`, 'variations', 'Enter a valid price.');
                    if (x.stock === '' || +x.stock < 0 || !Number.isInteger(+x.stock)) fail(`variant.${l}.stock`, 'variations', 'Enter a valid stock.');
                });
            } else {
                const price = v('price'), stock = v('stock');
                if (price === '' || isNaN(price) || +price <= 0) fail('price', 'variations', 'Enter a valid price greater than 0.');
                if (!isEdit && (stock === '' || isNaN(stock) || +stock < 0 || !Number.isInteger(+stock))) fail('stock', 'variations', 'Enter a valid stock quantity (0 or more).');
            }
            const sellingPrice = state.hasVariations
                ? Math.min(...Object.values(state.variants).map(x => +x.price || 0)) : +v('price');
            if (v('compare_at_price') && +v('compare_at_price') <= sellingPrice) {
                fail('compare_at_price', 'variations', 'Original price must be higher than the selling price.');
            }
            [['package_length', 'length'], ['package_width', 'width'], ['package_height', 'height']].forEach(([n, label]) => {
                if (v(n) === '' || isNaN(v(n)) || +v(n) <= 0) fail(n, 'shipping', `Enter the package ${label} in cm.`);
            });
            const w = v('weight_kg');
            if (w === '' || isNaN(w) || +w <= 0) fail('weight_kg', 'shipping', 'Enter the package weight in kg.');
            specRows().forEach(r => { if (r.name && !r.value) fail('spec', 'category', `Add a value for "${r.name}".`); if (!r.name && r.value) fail('spec', 'category', 'Add a name for each specification value.'); });
        }
        ['price', 'stock'].forEach(n => { if (!state.hasVariations && v(n) !== '' && +v(n) < 0) fail(n, 'variations', 'Cannot be negative.'); });
        ['package_length', 'package_width', 'package_height'].forEach(n => { if (v(n) !== '' && +v(n) <= 0) fail(n, 'shipping', 'Enter a number greater than 0.'); });
        (schema[catName()]?.fields || []).filter(f => f.type === 'date').forEach(f => {
            const val = form.elements[`attributes[${f.key}]`]?.value;
            if (val && new Date(val) <= new Date(new Date().toDateString())) fail(`attr.${f.key}`, 'category', 'Expiration date must be in the future.');
        });
        return errors.size === 0;
    }
    function renderErrors() {
        clearAllErrors();
        errors.forEach((e, key) => { if (key === 'spec') { const r = specRows().find(x => (x.name && !x.value) || (!x.name && x.value)); r && $('.pi-error', r.row.children[1]).classList.add('is-show'), r && ($('.pi-error', r.row.children[1]).textContent = e.message); } else showError(key, e.message); });
        const first = [...errors.keys()][0];
        if (first) (fieldOf(first) || $(`[data-error-for="${first}"]`, form))?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    // Live clear on edit
    form.addEventListener('input', e => { const f = e.target.dataset?.field; if (f) clearError(f); });
    form.addEventListener('change', e => { const f = e.target.dataset?.field; if (f) clearError(f); });

    /* ---------- progress + review ---------- */
    const sections = ['basic', 'category', 'variations', 'shipping', 'review'];
    let rt; function changed() { clearTimeout(rt); rt = setTimeout(refresh, 120); scheduleProductDraft(); }
    function refresh() {
        validate(true);
        const bad = new Set([...errors.values()].map(e => e.section));
        const chosen = !!catSelect.value && (!subsOf().length || !!subSelect.value);
        const done = { basic: !bad.has('basic'), category: chosen && !bad.has('category'), variations: !bad.has('variations'), shipping: !bad.has('shipping') };
        done.review = Object.values(done).every(Boolean);
        $$('[data-step]').forEach(li => li.classList.toggle('is-done', !!done[li.dataset.step]));
        $('#piBar').style.width = `${(Object.values(done).filter(Boolean).length / sections.length) * 100}%`;
        errors.clear();
        renderReview();
    }
    function renderReview() {
        const host = $('#piReview'), v = n => (form.elements[n]?.value ?? '').toString().trim();
        const dl = rows => { const d = el('dl'); rows.filter(r => r[1] !== '' && r[1] != null).forEach(([k, val]) => d.append(el('div', {}, el('dt', { textContent: k }), el('dd', { textContent: val }))));
            return d; };
        const group = (title, rows) => { const d = dl(rows); return d.children.length ? el('section', { className: 'pi-rv-group' }, el('h3', { textContent: title }), d) : ''; };
        const imgs = state.images;
        const top = el('div', { className: 'pi-rv-top' });
        const pic = el('div');
        pic.append(el('div', { className: 'pi-rv-img' }, imgs[0] ? el('img', { src: imgs[0].url, alt: 'Main product photo' }) : 'No photo yet'));
        if (imgs.length > 1) pic.append(el('div', { className: 'pi-rv-thumbs' }, ...imgs.slice(1).map(i => el('img', { src: i.url, alt: '' }))));
        const brandTxt = noBrand.checked ? 'No Brand' : v('brand');
        const info = el('div', {}, el('div', { className: 'pi-rv-title', textContent: v('name') || 'Untitled product' }), el('p', { className: 'pi-muted', style: 'white-space:pre-line', textContent: v('description').slice(0, 280) + (v('description').length > 280 ? '…' : '') }), dl([['Category', [catSelect.selectedOptions[0]?.textContent.trim(), subSelect.value && subSelect.selectedOptions[0]?.textContent.trim()].filter(Boolean).join(' › ')], ['Brand', brandTxt], ['Condition', form.elements.condition.value ? form.elements.condition.value[0].toUpperCase() + form.elements.condition.value.slice(1) : ''], ['Video', state.video ? (state.video.file?.name || 'Current video') : '']]));
        info.lastChild.style.marginTop = '14px';
        top.append(pic, info);
        const attrRows = (schema[catName()]?.fields || []).map(f => {
            const n = `attributes[${f.key}]`;
            return [f.label, f.type === 'chips' ? $$(`input[name="${n}[]"]:checked`, form).map(i => i.value).join(', ') : (form.elements[n]?.value ?? '')];
        });
        const specRowsData = specRows().filter(r => r.name && r.value).map(r => [r.name, r.value]);
        let priceRows;
        if (state.hasVariations) {
            const vs = Object.entries(state.variants);
            const prices = vs.map(([, x]) => +x.price).filter(p => p > 0);
            priceRows = [['Variations', state.types.map(t => `${t.name || '—'}: ${t.options.join(', ')}`).join(' • ')], ['Variants', vs.length], ['Price', prices.length ? (Math.min(...prices) === Math.max(...prices) ? peso(prices[0]) : `${peso(Math.min(...prices))} – ${peso(Math.max(...prices))}`) : ''], ['Total stock', vs.reduce((s, [, x]) => s + (parseInt(x.stock) || 0), 0)]];
        } else {
            priceRows = [['Price', v('price') ? peso(v('price')) : ''], ['Stock', v('stock')], ['SKU', cfg.showGeneratedSku ? cfg.productCode : 'Assigned after submission']];
        }
        const dims = ['package_length', 'package_width', 'package_height'].map(n => v(n)).filter(Boolean);
        host.replaceChildren(top, group('Category details', attrRows), group('Additional specifications', specRowsData), group('Price & stock', priceRows), group('Shipping', [['Package weight', v('weight_kg') ? `${v('weight_kg')} kg` : ''], ['Package size (L × W × H)', dims.length ? `${dims.join(' × ')} cm` : ''], ['Fragile', form.elements.fragile.value === '1' ? 'Yes' : 'No']]));
    }
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);

    /* ---------- submit ---------- */
    function buildBody(action) {
        const body = new FormData(form); // all named inputs (simple price/stock, attributes[], shipping, etc.)
        body.set('action', action);
        body.set('no_brand', noBrand.checked ? '1' : '0');
        // Photos: first item is the main photo. Existing photos are kept unless removed.
        const keep = state.images.filter(i => i.kind === 'existing').map(i => i.id);
        (cfg.existingImages || []).forEach(i => { if (!keep.includes(i.id)) body.append('remove_images[]', i.id); });
        const news = state.images.filter(i => i.kind === 'new');
        if (state.images[0]?.kind === 'new') { body.set('main_image', news[0].file); news.slice(1).forEach(i => body.append('gallery_images[]', i.file)); }
        else news.forEach(i => body.append('gallery_images[]', i.file));
        if (state.video?.file) body.set('video', state.video.file);
        if (state.removeVideo) body.set('remove_video', '1');
        if (isEdit && state.images[0]?.kind === 'existing') body.set('main_image_id', state.images[0].id);
        specRows().filter(r => r.name && r.value).forEach((r, i) => { body.set(`specs[${i}][name]`, r.name); body.set(`specs[${i}][value]`, r.value); });
        const completeVariations = state.types.length > 0 && state.types.every(t => t.name.trim() && t.options.length)
            && Object.values(state.variants).length > 0 && Object.values(state.variants).every(x => x.combo?.length === state.types.length
                && x.price !== '' && +x.price > 0 && x.stock !== '' && Number.isInteger(+x.stock) && +x.stock >= 0);
        const persistVariations = state.hasVariations && (action !== 'draft' || completeVariations);
        body.set('has_variations', persistVariations ? '1' : '0');
        if (persistVariations) {
            state.types.forEach((t, i) => { body.set(`variation_types[${i}][name]`, t.name.trim()); t.options.forEach(o => body.append(`variation_types[${i}][options][]`, o)); });
            Object.entries(state.variants).forEach(([label, x], i) => {
                body.set(`variants[${i}][label]`, label);
                x.combo.forEach(c => body.set(`variants[${i}][options][${c.type}]`, c.option));
                body.set(`variants[${i}][price]`, x.price); body.set(`variants[${i}][stock]`, x.stock);
                if (x.image) body.set(`variants[${i}][image]`, x.image);
            });
            const vs = Object.values(state.variants);
            body.set('price', Math.min(...vs.map(x => +x.price || 0)).toFixed(2));
            if (!isEdit) body.set('stock', vs.reduce((s, x) => s + (parseInt(x.stock) || 0), 0));
        }
        if (!body.get('brand')) body.set('brand', '');
        return body;
    }
    let submitting = false;
    const uploadPanel = $('#piUploadProgress', form);
    const uploadBar = $('#piUploadBar', form);
    const uploadPercent = $('#piUploadPercent', form);
    const uploadStatus = $('#piUploadStatus', form);
    function updateUploadProgress(loaded, total, complete = false) {
        if (!uploadPanel || !uploadBar || !uploadPercent || !uploadStatus) return;
        uploadPanel.hidden = false;
        if (complete) {
            uploadBar.value = 100;
            uploadPercent.textContent = '100%';
            uploadStatus.textContent = 'Upload finished. Waiting for Vendo to save the product…';
            return;
        }
        const percent = total > 0 ? Math.min(99, Math.floor((loaded / total) * 100)) : 0;
        uploadBar.value = percent;
        uploadPercent.textContent = `${percent}%`;
        uploadStatus.textContent = 'Uploading selected files and product details…';
    }
    async function submit(action, button) {
        if (submitting) return;
        clearAllErrors();
        if (!validate(action === 'submit')) { renderErrors(); const b = $('#piFormError'); b.textContent = `Please fix ${errors.size} highlighted ${errors.size === 1 ? 'field' : 'fields'} before ${action === 'submit' ? 'submitting' : 'saving'}.`; b.classList.add('is-show'); return; }
        submitting = true;
        $$('#piActions button', form).forEach(b => { b.disabled = true; });
        button.classList.add('is-loading');
        const body = buildBody(action);
        const hasFiles = [...body.values()].some(value => typeof File !== 'undefined' && value instanceof File && value.size > 0);
        if (uploadPanel) uploadPanel.hidden = !hasFiles;
        if (hasFiles) {
            updateUploadProgress(0, 1);
            uploadPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        try {
            const data = await postForm(form.action, body, hasFiles ? updateUploadProgress : null);
            productDraftSaved = true;
            clearTimeout(productDraftTimer);
            try { sessionStorage.removeItem(productDraftKey); } catch { /* optional */ }
            clearRestoredDraftNotice(productDraftKey);
            try { sessionStorage.setItem('sellerOperationMessage', data.message || (action === 'draft' ? 'Draft saved.' : 'Product submitted for admin review.')); } catch { /* optional */ }
            location.assign(form.dataset.redirect);
        } catch (ex) {
            if (hasFiles && uploadStatus) uploadStatus.textContent = 'Vendo did not save this upload. Review the error below, then try again.';
            // Server messages are shown beside the matching field when possible; the form is never cleared.
            const map = k => k.startsWith('attributes.') ? `attr.${k.slice(11)}` : k.startsWith('gallery_images') || k === 'main_image' ? 'images' : k;
            let general = ex.errors ? '' : ex.message;
            Object.entries(ex.errors || {}).forEach(([k, msgs]) => {
                const key = map(k);
                const message = key === 'video' && /failed to upload/i.test(msgs[0])
                    ? 'PHP or the web server rejected the video before Vendo could receive it. Increase upload_max_filesize and post_max_size, then restart php artisan serve.'
                    : msgs[0];
                if ($(`[data-error-for="${key}"]`, form)) showError(key, message);
                else general += msgs.join(' ') + ' ';
            });
            const b = $('#piFormError');
            b.textContent = general.trim() || 'Please review the highlighted fields.'; b.classList.add('is-show');
            const videoError = ex.errors?.video && $('[data-error-for="video"]', form);
            (videoError || b).scrollIntoView({ behavior: 'smooth', block: 'center' });
            $$('#piActions button', form).forEach(x => { x.disabled = false; });
            button.classList.remove('is-loading'); submitting = false;
        }
    }
    $('#piDraft')?.addEventListener('click', e => submit('draft', e.currentTarget));
    $('#piSubmit').addEventListener('click', e => submit('submit', e.currentTarget));
    form.addEventListener('submit', e => e.preventDefault());

    /* ---------- init ---------- */
    renderSubs(cfg.selectedSub || '');
    renderCategory();
    if (cfg.hasVariations) { varToggle.find(r => r.value === '1').checked = true; setVariations(true); renderTypes(); rebuildVariants(); }
    renderVideo();
    restoreProductDraft();
    productDraftReady = true;
    window.addEventListener('pagehide', saveProductDraft);
    refresh();
}
