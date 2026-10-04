// Restore explicitly opted-in, ordinary forms after an accidental reload in this tab.
import { showRestoredDraftNotice, clearRestoredDraftNotice } from './draft-notice.js';
const draftLifetime = 2 * 60 * 60 * 1000;
const ignoredNames = new Set(['_token', '_method', 'password', 'password_confirmation', 'current_password', 'otp', 'code']);
const initializedForms = new WeakSet();

function eligibleField(form, field) {
    const allowedHidden = (form.dataset.draftHidden || '').split(',').map(name => name.trim());
    return field.name && !field.disabled && !field.readOnly && !ignoredNames.has(field.name) &&
        !['file', 'password', 'submit', 'button'].includes(field.type) &&
        (field.type !== 'hidden' || allowedHidden.includes(field.name));
}

function draftFields(form) {
    const fields = {};
    for (const field of form.elements) {
        if (!eligibleField(form, field)) continue;
        if (field.type === 'checkbox' || field.type === 'radio') {
            fields[field.name] ??= [];
            if (field.checked) fields[field.name].push(field.value);
        } else {
            fields[field.name] = field.value;
        }
    }
    return fields;
}

function restoreFields(form, fields) {
    for (const field of form.elements) {
        if (!eligibleField(form, field) || !(field.name in fields)) continue;
        const value = fields[field.name];
        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = Array.isArray(value) && value.includes(field.value);
        } else if (typeof value === 'string') {
            field.value = value;
        }
    }
}

function startDraft(form) {
    if (initializedForms.has(form)) return;
    initializedForms.add(form);
    const key = `vendo.form.${form.dataset.draftKey}`;
    let draft;
    try { draft = JSON.parse(sessionStorage.getItem(key) || 'null'); } catch (_) {}
    if (draft?.savedAt > Date.now() - draftLifetime && draft.fields) {
        restoreFields(form, draft.fields);
        form.dataset.draftRestored = '1';
        if (form.dataset.draftCascade) {
            form.elements.namedItem(form.dataset.draftCascade)?.dispatchEvent(new Event('change', { bubbles: true }));
            restoreFields(form, draft.fields);
        }
        for (const field of form.elements) {
            if (!eligibleField(form, field) || !(field.name in draft.fields)) continue;
            const event = field.type === 'checkbox' || field.type === 'radio' ? 'change' : 'input';
            field.dispatchEvent(new Event(event, { bubbles: true }));
        }
        showRestoredDraftNotice(key, 'Your unsaved details were restored. Please review them before submitting.' +
            (form.querySelector('input[type="file"]') ? ' Reattach any files.' : ''));
    } else if (draft) {
        try { sessionStorage.removeItem(key); } catch (_) {}
        clearRestoredDraftNotice(key);
    }

    let dirty = false;
    let submitted = false;
    let timer;
    const save = () => {
        if (!dirty || submitted) return;
        try { sessionStorage.setItem(key, JSON.stringify({ savedAt: Date.now(), fields: draftFields(form) })); } catch (_) {}
    };
    const scheduleSave = () => {
        dirty = true;
        submitted = false;
        clearTimeout(timer);
        timer = setTimeout(save, 250);
    };
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    const clearDraft = () => {
        submitted = true;
        clearTimeout(timer);
        try { sessionStorage.removeItem(key); } catch (_) {}
        clearRestoredDraftNotice(key);
        delete form.dataset.draftRestored;
    };
    form.addEventListener('submit', () => {
        if (form.hasAttribute('data-draft-ajax')) save();
        else clearDraft();
    });
    form.addEventListener('vendo:draft-committed', clearDraft);
    window.addEventListener('pagehide', save);
}

function initFormDrafts() {
    document.querySelectorAll('form[data-draft-key]').forEach(startDraft);
    new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (!(node instanceof Element)) continue;
                if (node.matches('form[data-draft-key]')) startDraft(node);
                node.querySelectorAll('form[data-draft-key]').forEach(startDraft);
            }
        }
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFormDrafts);
else initFormDrafts();
