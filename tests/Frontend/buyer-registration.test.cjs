const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/buyer/auth/registration-scripts.blade.php'), 'utf8').replace(/<\/?script>/g, '');

function setup(draft = null, proof = { email: '', expires_at: 0 }) {
    const store = new Map(draft ? [['vendo.buyer.registration.draft', JSON.stringify(draft)]] : []);
    const fields = {};
    const names = ['last_name', 'first_name', 'middle_initial', 'sex', 'email', 'birthday', 'street', 'zip_code', 'contact_number', 'id_type', 'id_type_1', 'id_type_2', 'password', 'password_confirmation', 'province', 'municipality', 'barangay'];
    class Option {
        constructor(text, value, defaultSelected, selected) { Object.assign(this, { text, value, selected, disabled: false, dataset: {} }); }
    }
    for (const name of names) {
        fields[name] = { name, value: '', options: [], selectedOptions: [], disabled: false, files: [],
            dispatchEvent() {}, addEventListener() {}, append(option) {
                this.options.push(option);
                if (option.selected) { this.value = option.value; this.selectedOptions = [option]; }
            }, replaceChildren(option) { this.options = []; this.value = ''; this.selectedOptions = []; this.append(option); },
        };
    }
    let factory;
    const context = { Option, Event: class {}, console, setTimeout: () => 1, clearTimeout() {},
        sessionStorage: { getItem: k => store.get(k), setItem: (k, v) => store.set(k, v), removeItem: k => store.delete(k) },
        window: { addEventListener() {}, removeEventListener() {} },
        document: { addEventListener: (_, fn) => fn(), getElementById: () => ({ textContent: JSON.stringify(proof) }) },
        Alpine: { data: (_, fn) => { factory = fn; } },
        fetch: async url => ({ ok: true, json: async () => url.includes('cities-municipalities/P1') ? [] : url.endsWith('/provinces/') ? [{ name: 'Laguna', code: 'P1' }] : url.includes('/provinces/P1/') ? [{ name: 'Santa Cruz', code: 'M1' }] : [{ name: 'Test Barangay', code: 'B1' }] }),
    };
    vm.runInNewContext(source, context);
    const component = factory();
    component.$nextTick = async fn => { if (fn) return fn(); };
    component.$watch = () => {};
    component.$refs = { form: { elements: { namedItem: name => fields[name] }, addEventListener() {}, querySelectorAll: () => [] } };
    return { component, fields, store, context };
}

const draft = () => ({ savedAt: Date.now(), step: 3, idCategory: 'secondary',
    fields: { first_name: 'Test', last_name: 'Buyer', email: 'buyer@example.test', house_no: '12', street: 'Test Street', password: 'must-not-restore' },
    address: { province: { name: 'Laguna', code: 'P1' }, municipality: { name: 'Santa Cruz', code: 'M1' }, barangay: { name: 'Test Barangay', code: 'B1' } },
});

test('reload restores step, names, address and server verification without passwords', async () => {
    const { component, fields, store } = setup(draft(), { email: 'buyer@example.test', expires_at: Date.now() / 1000 + 1800 });
    await component.restoreDraft();
    assert.equal(component.step, 3);
    assert.equal(component.emailVerified, true);
    assert.equal(fields.first_name.value, 'Test');
    assert.equal(fields.street.value, '12 Test Street');
    assert.equal(fields.barangay.value, 'Test Barangay');
    assert.equal(fields.password.value, '');
    const saved = JSON.parse(store.get('vendo.buyer.registration.draft'));
    assert.equal(saved.fields.password, undefined);
    assert.equal(saved.fields.house_no, undefined);
    assert.equal(saved.fields.street, '12 Test Street');
    assert.equal(saved.emailVerified, undefined);
});

test('browser draft cannot establish verification for a different email or expired proof', async () => {
    for (const proof of [{ email: 'different@example.test', expires_at: Date.now() / 1000 + 1800 }, { email: 'buyer@example.test', expires_at: 1 }]) {
        const { component } = setup(draft(), proof);
        await component.restoreDraft();
        assert.equal(component.emailVerified, false);
        assert.equal(component.step, 3);
    }
});

test('address API failure preserves saved selections and shows an error', async () => {
    const { component, context, fields } = setup(draft());
    context.fetch = async () => { throw new Error('offline'); };
    await component.restoreDraft();
    assert.equal(fields.province.value, 'Laguna');
    assert.equal(fields.municipality.value, 'Santa Cruz');
    assert.match(component.formError, /could not load/);
});

test('missing files on a hidden step route back to that step before posting', async () => {
    const { component } = setup();
    const file = { name: 'valid_id', required: true, disabled: false, willValidate: true, validity: { valid: false }, type: 'file', getClientRects: () => [], closest: () => null };
    component.step = 3;
    component.$refs.step1 = { querySelectorAll: () => [file] };
    await component.submitForm({});
    assert.equal(component.step, 1);
    assert.equal(component.submitting, false);
    assert.match(component.formError, /Files must be selected again/);
});

test('a completed registration cannot recreate its deleted draft', async () => {
    const { component, store } = setup();
    component.restoring = false;
    component.showSuccessModal = true;
    component.saveDraft();
    assert.equal(store.has('vendo.buyer.registration.draft'), false);
});
