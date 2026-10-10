// Seller > Vouchers: create / edit dialog, live preview, pause / resume, delete.
// Every action posts to a URL rendered by the page. While a backend route is missing that URL is empty and the seller
// sees "not available on the server yet" instead of a failed request.
const app = document.getElementById('vcApp');

if (app) {
    const $ = (selector, root = app) => root.querySelector(selector);
    const $$ = (selector, root = app) => [...root.querySelectorAll(selector)];
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const modal = $('#vcModal');
    const form = $('#vcForm');
    const errorBox = $('#vcError');
    const submit = $('#vcSubmit');
    const confirmDialog = $('#vcConfirm');
    const toast = $('#vcToast');
    let editingUrl = '';
    let saving = false;
    let toastTimer;

    const money = n => '₱' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    const say = message => { toast.textContent = message; toast.classList.add('is-show'); clearTimeout(toastTimer); toastTimer = setTimeout(() => toast.classList.remove('is-show'), 3600); };
    const showError = (box, message) => { box.textContent = message; box.hidden = !message; };
    const field = name => form.elements[name];

    async function send(url, method, body) {
        if (!url) throw Object.assign(new Error('Vouchers are not available on the server yet.'), { soft: true });
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await response.json().catch(() => ({}));
        if (response.ok) return data;
        if (response.status === 419) throw new Error('Your session expired. Reload the page and try again.');
        if (response.status === 404 || response.status === 405) throw new Error('Vouchers are not available on the server yet.');
        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(first || data.message || 'Something went wrong. Please try again.');
    }

    /* ---------- dialog ---------- */
    const syncType = () => {
        const percent = field('type').value === 'percent';
        $('[data-vc-maxwrap]').hidden = !percent;
        $('[data-vc-value-label]').textContent = percent ? 'Discount (%)' : 'Discount amount (₱)';
        field('value').placeholder = percent ? '10' : '50';
        field('value').max = percent ? '90' : '';
    };
    const syncScope = () => $$('[data-vc-picker]').forEach(box => { box.hidden = box.dataset.vcPicker !== field('scope').value; });
    const preview = () => {
        const percent = field('type').value === 'percent';
        const value = Number(field('value').value || 0);
        $('[data-vc-pv-big]').textContent = percent ? `${value || 10}%` : money(value || 50);
        $('[data-vc-pv-name]').textContent = field('name').value || 'Voucher name';
        $('[data-vc-pv-code]').textContent = field('code').value || 'CODE';
        const min = Number(field('min_spend').value || 0);
        $('[data-vc-pv-min]').textContent = min > 0 ? `Min. spend ${money(min)}` : 'No minimum spend';
        const end = field('ends_at').value;
        $('[data-vc-pv-valid]').textContent = end ? `Valid until ${new Date(end).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}` : 'Valid now';
    };
    const refresh = () => { syncType(); syncScope(); preview(); };

    function openForm(voucher = null, prefillProduct = null) {
        form.reset();
        showError(errorBox, '');
        editingUrl = voucher?.update_url ?? '';
        $('#vcModalTitle').textContent = voucher ? 'Edit voucher' : 'Create voucher';
        submit.textContent = voucher ? 'Save changes' : 'Save voucher';
        if (voucher) {
            ['name', 'code', 'type', 'value', 'max_discount', 'min_spend', 'usage_limit', 'per_buyer_limit', 'starts_at', 'ends_at', 'scope'].forEach(key => {
                const el = field(key);
                if (!el) return;
                if (el instanceof RadioNodeList) el.value = voucher[key] ?? '';
                else el.value = voucher[key] ?? '';
            });
            field('paused').checked = voucher.status === 'paused';
            $$('input[name="product_ids[]"]').forEach(box => { box.checked = (voucher.product_ids ?? []).map(String).includes(box.value); });
            $$('input[name="category_ids[]"]').forEach(box => { box.checked = (voucher.category_ids ?? []).map(String).includes(box.value); });
        } else if (prefillProduct) {
            field('scope').value = 'products';
            const box = $(`input[name="product_ids[]"][value="${CSS.escape(String(prefillProduct))}"]`);
            if (box) { box.checked = true; box.closest('.vc-pick')?.scrollIntoView({ block: 'nearest' }); }
        }
        refresh();
        modal.showModal();
        field('name').focus();
    }

    app.addEventListener('click', async event => {
        if (event.target.closest('[data-vc-create]')) return openForm();
        const edit = event.target.closest('[data-vc-edit]');
        if (edit) return openForm(JSON.parse(edit.dataset.vcEdit));
        if (event.target.closest('[data-vc-close]') && !saving) return modal.close();

        const copy = event.target.closest('[data-vc-copy]');
        if (copy) {
            try { await navigator.clipboard.writeText(copy.dataset.vcCopy); say(`Code ${copy.dataset.vcCopy} copied`); } catch { say('Could not copy the code'); }
            return;
        }
        if (event.target.closest('[data-vc-generate]')) {
            const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            field('code').value = [...crypto.getRandomValues(new Uint8Array(8))].map(n => alphabet[n % alphabet.length]).join('');
            return preview();
        }
        const toggle = event.target.closest('[data-vc-toggle]');
        if (toggle) {
            toggle.disabled = true;
            try {
                const data = await send(toggle.dataset.vcToggle, 'PATCH', { status: toggle.dataset.next });
                sessionStorage.setItem('vcToast', data.message || (toggle.dataset.next === 'paused' ? 'Voucher paused.' : 'Voucher resumed.'));
                location.reload();
            } catch (error) { toggle.disabled = false; say(error.message); }
            return;
        }
        const del = event.target.closest('[data-vc-delete]');
        if (del) {
            confirmDialog.dataset.url = del.dataset.vcDelete;
            const used = Number(del.dataset.vcUsed || 0);
            $('#vcConfirmText').textContent = used > 0
                ? `“${del.dataset.vcName}” was used ${used} time${used === 1 ? '' : 's'}. Past orders keep their discount; the code stops working right away.`
                : `“${del.dataset.vcName}” will stop working right away.`;
            showError($('#vcConfirmError'), '');
            confirmDialog.showModal();
        }
    });
    modal.addEventListener('click', event => { if (event.target === modal && !saving) modal.close(); });
    modal.addEventListener('cancel', event => { if (saving) event.preventDefault(); });

    confirmDialog.addEventListener('click', async event => {
        if (event.target.closest('[data-vc-confirm-cancel]')) return confirmDialog.close();
        const ok = event.target.closest('[data-vc-confirm-ok]');
        if (!ok) return;
        ok.disabled = true;
        try {
            const data = await send(confirmDialog.dataset.url, 'DELETE');
            sessionStorage.setItem('vcToast', data.message || 'Voucher deleted.');
            location.reload();
        } catch (error) { showError($('#vcConfirmError'), error.message); ok.disabled = false; }
    });

    form.addEventListener('input', event => {
        if (event.target.name === 'code') event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        preview();
    });
    form.addEventListener('change', event => {
        if (event.target.name === 'type') syncType();
        if (event.target.name === 'scope') syncScope();
        preview();
    });
    $('[data-vc-picker-search]')?.addEventListener('input', event => {
        const term = event.target.value.trim().toLowerCase();
        $$('.vc-pick[data-text]').forEach(row => { row.hidden = term !== '' && !row.dataset.text.includes(term); });
    });

    /* ---------- validate + save ---------- */
    function collect() {
        const num = name => (field(name).value === '' ? null : Number(field(name).value));
        const scope = field('scope').value;
        return {
            name: field('name').value.trim(), code: field('code').value.trim(), type: field('type').value, value: num('value'),
            max_discount: field('type').value === 'percent' ? num('max_discount') : null, min_spend: num('min_spend') ?? 0,
            usage_limit: num('usage_limit'), per_buyer_limit: num('per_buyer_limit') ?? 1,
            starts_at: field('starts_at').value || null, ends_at: field('ends_at').value || null, scope,
            product_ids: scope === 'products' ? $$('input[name="product_ids[]"]:checked').map(b => Number(b.value)) : [],
            category_ids: scope === 'categories' ? $$('input[name="category_ids[]"]:checked').map(b => Number(b.value)) : [],
            status: field('paused').checked ? 'paused' : 'active',
        };
    }
    function validate(d) {
        if (!d.name) return 'Give the voucher a name.';
        if (!/^[A-Z0-9]{4,12}$/.test(d.code)) return 'The code must be 4–12 letters or numbers.';
        if (!d.value || d.value <= 0) return 'Enter a discount value above zero.';
        if (d.type === 'percent' && d.value > 90) return 'A percentage voucher can give at most 90% off.';
        if (d.type === 'fixed' && d.min_spend > 0 && d.value > d.min_spend) return 'The discount cannot be bigger than the minimum spend.';
        if (d.usage_limit !== null && d.usage_limit < 1) return 'Total uses must be at least 1.';
        if (d.per_buyer_limit < 1) return 'Uses per buyer must be at least 1.';
        if (d.usage_limit !== null && d.per_buyer_limit > d.usage_limit) return 'Uses per buyer cannot exceed the total uses.';
        if (d.starts_at && d.ends_at && d.ends_at <= d.starts_at) return 'The end time must be after the start time.';
        if (d.ends_at && new Date(d.ends_at) <= new Date()) return 'The end time must be in the future.';
        if (d.scope === 'products' && !d.product_ids.length) return 'Choose at least one product, or apply the voucher to all products.';
        if (d.scope === 'categories' && !d.category_ids.length) return 'Choose at least one category.';
        return '';
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        const data = collect();
        const problem = validate(data);
        if (problem) return showError(errorBox, problem);
        showError(errorBox, '');
        saving = true; submit.disabled = true; const label = submit.textContent; submit.textContent = 'Saving…';
        try {
            const result = await send(editingUrl || app.dataset.storeUrl, editingUrl ? 'PUT' : 'POST', data);
            sessionStorage.setItem('vcToast', result.message || 'Voucher saved.');
            location.reload();
        } catch (error) {
            showError(errorBox, error.message);
            saving = false; submit.disabled = false; submit.textContent = label;
        }
    });

    const flash = sessionStorage.getItem('vcToast');
    if (flash) { sessionStorage.removeItem('vcToast'); say(flash); }
    if (app.dataset.openCreate === '1') openForm(null, app.dataset.prefillProduct || null);
}