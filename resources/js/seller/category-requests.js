// Seller > Account Management > "Request new categories".
// Posts multipart form data to the endpoint rendered in data-cr-endpoint. While the backend route does not exist the
// endpoint is empty and the seller gets a clear message instead of a failed request.
const root = document.getElementById('sellingCategories');

if (root) {
    const dialog = document.getElementById('crDialog');
    const form = document.getElementById('crForm');
    const errorBox = document.getElementById('crError');
    const submit = document.getElementById('crSubmit');
    const count = document.getElementById('crCount');
    const permit = document.getElementById('crPermit');
    const MAX_BYTES = 5 * 1024 * 1024;
    const ALLOWED = ['image/jpeg', 'image/png', 'application/pdf'];
    let saving = false;

    const showError = message => { errorBox.textContent = message; errorBox.hidden = !message; };
    const checked = () => [...form.querySelectorAll('input[name="categories[]"]:checked')];
    const updateCount = () => { count.textContent = `${checked().length} selected`; };

    const reset = () => { form.reset(); updateCount(); showError(''); };

    root.addEventListener('click', event => {
        if (event.target.closest('[data-cr-open]')) { reset(); dialog.showModal(); }
        if (event.target.closest('[data-cr-close]') && !saving) dialog.close();
    });
    dialog.addEventListener('click', event => { if (event.target === dialog && !saving) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    form.addEventListener('change', updateCount);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        showError('');

        const file = permit.files[0];
        if (!checked().length) return showError('Choose at least one category.');
        if (!file) return showError('Attach a permit or license for these categories.');
        if (!ALLOWED.includes(file.type)) return showError('The permit must be a JPG, PNG or PDF file.');
        if (file.size > MAX_BYTES) return showError('The permit is larger than 5 MB. Choose a smaller file.');
        if (!root.dataset.crEndpoint) return showError('Category requests are not available on the server yet. Please contact Vendo Support to add a category.');

        saving = true;
        submit.disabled = true;
        submit.textContent = 'Submitting…';
        try {
            const response = await fetch(root.dataset.crEndpoint, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok) { window.location.reload(); return; }
            if (response.status === 419) return showError('Your session expired. Reload the page and try again.');
            if (response.status === 404 || response.status === 405) return showError('Category requests are not available on the server yet.');
            const first = data.errors ? Object.values(data.errors).flat()[0] : null;
            showError(first || data.message || 'We could not submit your request. Please try again.');
        } catch {
            showError('Network problem. Check your connection and try again.');
        } finally {
            saving = false;
            submit.disabled = false;
            submit.textContent = 'Submit for approval';
        }
    });
}