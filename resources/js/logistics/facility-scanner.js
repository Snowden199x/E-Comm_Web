import { BrowserMultiFormatReader } from '@zxing/browser';

document.querySelectorAll('[data-facility-scanner]').forEach((root) => {
    const modal = root.querySelector('[data-scanner-modal]');
    const video = root.querySelector('[data-scanner-video]');
    const message = root.querySelector('[data-scanner-message]');
    const tracking = root.querySelector('[data-scanner-tracking]');
    const openButton = root.querySelector('[data-scanner-open]');
    const closeButton = modal?.querySelector('[data-scanner-close]');
    const restartButton = modal?.querySelector('[data-scanner-restart]');
    const form = modal?.querySelector('[data-scanner-form]');

    if (!modal || !video || !message || !tracking || !openButton || !closeButton || !restartButton || !form) return;

    document.body.append(modal);
    const reader = new BrowserMultiFormatReader();
    let controls = null;
    let cameraRun = 0;
    let open = false;
    let busy = false;
    let refreshOnClose = false;
    let lastTrigger = null;
    let pendingKey = null;
    let pendingFingerprint = null;

    function status(text, state = '') {
        message.textContent = text;
        message.dataset.state = state;
    }

    function stopCamera() {
        cameraRun += 1;
        controls?.stop();
        controls = null;
        if (video.srcObject) {
            video.srcObject.getTracks().forEach((track) => track.stop());
            video.srcObject = null;
        }
    }

    async function startCamera() {
        stopCamera();
        if (!open) return;
        const run = cameraRun;
        if (!navigator.mediaDevices?.getUserMedia) {
            status('Camera access is unavailable here. Allow it in your browser site settings, or enter the Tracking ID below.', 'error');
            tracking.focus();
            return;
        }
        status('Requesting camera access…');
        try {
            // Request the stream directly from the button's click flow so the
            // browser can present its camera permission prompt consistently.
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
            if (!open || run !== cameraRun) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }
            video.srcObject = stream;
            await video.play();
            const activeControls = await reader.decodeFromVideoElement(video, (result) => {
                if (!result || busy || !open) return;
                tracking.value = result.getText().trim();
                stopCamera();
                submitScan();
            });
            if (!open || run !== cameraRun) activeControls.stop();
            else {
                controls = activeControls;
                status('Camera ready. Aim at the parcel label.');
            }
        } catch {
            status('Camera permission was denied or unavailable. Allow camera access in your browser site settings, or enter the Tracking ID below.', 'error');
            tracking.focus();
        }
    }

    async function submitScan() {
        const code = tracking.value.trim();
        if (!code || busy) return;
        stopCamera();
        busy = true;
        const fingerprint = `auto:${code}`;
        if (fingerprint !== pendingFingerprint) {
            pendingFingerprint = fingerprint;
            pendingKey = crypto.randomUUID();
        }
        status('Recording scan…');
        try {
            const response = await fetch(root.dataset.scanUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ tracking_number: code, action: 'auto', scan_key: pendingKey }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                status(data.errors ? Object.values(data.errors)[0]?.[0] : (data.message || 'Scan could not be recorded.'), 'error');
                return;
            }
            refreshOnClose = true;
            pendingKey = null;
            pendingFingerprint = null;
            const actionLabel = data.action === 'arrival_sort'
                ? 'received and sorted'
                : data.action.replaceAll('_', ' ');
            status(`${data.tracking_number} · ${actionLabel} at ${data.facility}${data.next_facility ? ` · Next: ${data.next_facility}` : ''}${data.duplicate ? ' (already recorded)' : ''}`, 'success');
        } catch {
            status('Connection lost. Scan the parcel again; the same scan key will prevent a duplicate.', 'error');
        } finally {
            busy = false;
        }
    }

    function closeModal() {
        if (busy) return;
        open = false;
        stopCamera();
        modal.hidden = true;
        document.body.style.overflow = '';
        lastTrigger?.focus();
        if (refreshOnClose) window.location.reload();
    }

    openButton.addEventListener('click', (event) => {
        lastTrigger = event.currentTarget;
        modal.hidden = false;
        open = true;
        document.body.style.overflow = 'hidden';
        closeButton.focus();
        // This call is made directly from the user's click so the browser can
        // show its camera permission prompt for this scanner instance.
        startCamera();
    });
    closeButton.addEventListener('click', closeModal);
    restartButton.addEventListener('click', startCamera);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitScan();
    });
    tracking.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitScan();
        }
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not([disabled]), input, select')];
        const first = focusable[0];
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    });
    window.addEventListener('beforeunload', stopCamera);
});
