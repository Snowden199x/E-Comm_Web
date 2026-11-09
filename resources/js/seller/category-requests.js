// Seller > Account Management > "Request new categories".
// Posts multipart form data to the endpoint rendered in data-cr-endpoint.
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
        if (!root.dataset.crEndpoint) return showError('Category requests are unavailable right now. Message Vendo Support to add a category.');

        saving = true;
        submit.disabled = true;
        submit.textContent = 'Submitting…';
        submit.classList.add('is-loading');
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
            if (response.ok) { sessionStorage.setItem('ac-flash', data.message || 'Category request sent for Admin review.'); sessionStorage.setItem('ac-tab', 'business'); location.hash = 'business'; window.location.reload(); return; }
            if (response.status === 419) return showError('Your session expired. Reload the page, then submit again.');
            if (response.status === 404 || response.status === 405) return showError('Category requests are unavailable right now. Message Vendo Support to add a category.');
            const first = data.errors ? Object.values(data.errors).flat()[0] : null;
            showError(first || data.message || 'Your request did not send. Check the details and try again.');
        } catch {
            showError('Connection lost. Check your internet and submit again.');
        } finally {
            saving = false;
            submit.disabled = false;
            submit.textContent = 'Submit request';
            submit.classList.remove('is-loading');
        }
    });
}
