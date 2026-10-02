const mountGoogleSignIn = (container) => {
    const button = container.querySelector('[data-google-button]');
    const error = container.querySelector('[data-google-error]');
    if (!button || button.dataset.ready === 'true') return;

    const attempts = Number(container.dataset.attempts || 0);
    if (!window.google?.accounts?.id) {
        if (attempts > 40) {
            error.textContent = 'Google sign-in did not load. Check your connection and try again.';
            error.classList.remove('hidden');
            return;
        }
        container.dataset.attempts = String(attempts + 1);
        window.setTimeout(() => mountGoogleSignIn(container), 150);
        return;
    }

    button.dataset.ready = 'true';
    window.google.accounts.id.initialize({
        client_id: container.dataset.clientId,
        callback: async ({ credential }) => {
            error.classList.add('hidden');
            button.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(container.dataset.exchangeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': container.dataset.csrf,
                    },
                    body: JSON.stringify({ credential, role: container.dataset.role }),
                });
                const data = await response.json();
                if (!response.ok || !data.redirect) {
                    throw new Error(data.message || data.errors?.credential?.[0] || 'Google sign-in could not be completed.');
                }
                window.location.assign(data.redirect);
            } catch (exception) {
                error.textContent = exception.message || 'Google sign-in could not be completed.';
                error.classList.remove('hidden');
            } finally {
                button.removeAttribute('aria-busy');
            }
        },
        auto_select: false,
        cancel_on_tap_outside: true,
    });
    window.google.accounts.id.renderButton(button, {
        type: 'standard',
        theme: 'outline',
        size: 'large',
        text: 'continue_with',
        locale: 'en',
        shape: 'rectangular',
        logo_alignment: 'left',
        width: Math.min(button.clientWidth || 360, 420),
    });
};

const initGoogleSignIn = () => {
    document.querySelectorAll('[data-google-signin]').forEach(mountGoogleSignIn);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGoogleSignIn, { once: true });
} else {
    initGoogleSignIn();
}
