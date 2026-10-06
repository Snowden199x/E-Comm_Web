// Admin document viewer: one popup shared by every Admin screen that shows
// private verification files (Registrations, User Management, Dashboard).
//
// Open it from any Blade view with:
//   @click="$dispatch('open-document', { url, title, subtitle, filename, kind })"
//
// The file is fetched with the admin session and shown in the popup, so the
// admin never leaves the page. The server route and its authorization are
// unchanged; this only changes how the response is displayed.

const ERRORS = {
    404: {
        title: 'File not found',
        body: 'The record points to a document that is not in private storage. It may have been uploaded before documents were made private, or the file was removed.',
        retry: false,
    },
    403: {
        title: 'Access denied',
        body: 'Your admin account is not allowed to open this document.',
        retry: false,
    },
    session: {
        title: 'Session expired',
        body: 'Sign in again, then reopen this document.',
        retry: false,
    },
    server: {
        title: 'The server had a problem',
        body: 'The document could not be read right now. Try again in a moment.',
        retry: true,
    },
    network: {
        title: 'Could not load the document',
        body: 'Check your connection and try again.',
        retry: true,
    },
    type: {
        title: 'Unsupported file',
        body: 'This file is not an image or a PDF, so it cannot be shown here.',
        retry: false,
    },
    corrupt: {
        title: 'The file could not be displayed',
        body: 'The file was found but looks damaged or incomplete.',
        retry: false,
    },
};

document.addEventListener('alpine:init', () => {
    Alpine.data('adminDocViewer', () => ({
        open: false,
        status: 'idle', // idle | loading | ready | error
        kind: null, // image | pdf
        url: '',
        title: '',
        subtitle: '',
        filename: '',
        hint: null,
        src: '',
        error: { title: '', body: '', retry: false },

        zoom: 1,
        base: 1,
        nw: 0,
        nh: 0,

        trigger: null,
        ctrl: null,
        revokeTimer: null,

        show(detail = {}) {
            if (!detail.url) return;

            clearTimeout(this.revokeTimer);
            this.trigger = document.activeElement;
            this.url = detail.url;
            this.title = detail.title || 'Document';
            this.subtitle = detail.subtitle || '';
            this.filename = detail.filename || '';
            this.hint = detail.kind || null;
            this.zoom = 1;
            this.base = 1;
            this.open = true;

            this.$nextTick(() => this.$refs.close && this.$refs.close.focus());
            this.load();
        },

        async load() {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;

            this.releaseSource();
            this.status = 'loading';
            this.kind = null;

            try {
                const res = await fetch(this.url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'image/*,application/pdf;q=0.9,*/*;q=0.5' },
                    signal: ctrl.signal,
                });

                if (ctrl.signal.aborted) return;
                if (!res.ok) return this.fail(res.status);

                const type = (res.headers.get('Content-Type') || '').split(';')[0].trim().toLowerCase();

                // A redirect to the login page comes back as HTML.
                if (type.startsWith('text/html')) return this.fail('session');

                let kind = null;
                if (type === 'application/pdf') kind = 'pdf';
                else if (type.startsWith('image/')) kind = 'image';
                else if ((type === '' || type === 'application/octet-stream') && this.hint) kind = this.hint;
                if (!kind) return this.fail('type');

                let blob = await res.blob();
                if (ctrl.signal.aborted) return;

                // Force the type so the browser's PDF viewer is used.
                if (kind === 'pdf' && blob.type !== 'application/pdf') {
                    blob = new Blob([blob], { type: 'application/pdf' });
                }

                this.src = URL.createObjectURL(blob);
                this.kind = kind;
                this.status = 'ready';
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.fail('network');
            }
        },

        fail(code) {
            const key = ERRORS[code] ? code : code >= 500 ? 'server' : code === 401 || code === 419 ? 'session' : 'server';
            this.error = ERRORS[key];
            this.status = 'error';
        },

        onImageError() {
            this.fail('corrupt');
        },

        onImageLoad(event) {
            this.nw = event.target.naturalWidth || 0;
            this.nh = event.target.naturalHeight || 0;
            this.fit();
        },

        // "100%" means the whole image fits the stage; zoom is relative to that.
        fit() {
            const stage = this.$refs.stage;
            if (!stage || !this.nw || !this.nh || this.kind !== 'image') return;
            const cw = stage.clientWidth - 32;
            const ch = stage.clientHeight - 32;
            if (cw <= 0 || ch <= 0) return;
            this.base = Math.min(cw / this.nw, ch / this.nh, 1) || 1;
        },

        get imageStyle() {
            if (!this.nw) return {};
            return { width: Math.round(this.nw * this.base * this.zoom) + 'px' };
        },

        get zoomLabel() {
            return Math.round(this.zoom * 100) + '%';
        },

        zoomIn() {
            this.zoom = Math.min(4, Math.round((this.zoom + 0.25) * 100) / 100);
        },

        zoomOut() {
            this.zoom = Math.max(0.5, Math.round((this.zoom - 0.25) * 100) / 100);
        },

        zoomReset() {
            this.zoom = 1;
            const stage = this.$refs.stage;
            if (stage) {
                stage.scrollTop = 0;
                stage.scrollLeft = 0;
            }
        },

        onKey(event) {
            if (!this.open) return;

            if (event.key === 'Tab') return this.trap(event);

            if (this.kind !== 'image' || this.status !== 'ready') return;
            if (event.key === '+' || event.key === '=') {
                event.preventDefault();
                this.zoomIn();
            } else if (event.key === '-') {
                event.preventDefault();
                this.zoomOut();
            } else if (event.key === '0') {
                event.preventDefault();
                this.zoomReset();
            }
        },

        // Runs in the capture phase so a profile or registration dialog behind
        // this one does not also close on the same Escape press.
        onEscape(event) {
            if (!this.open) return;
            event.preventDefault();
            event.stopPropagation();
            this.close();
        },

        trap(event) {
            const dialog = this.$refs.dialog;
            if (!dialog) return;
            const items = [...dialog.querySelectorAll('button:not([disabled]), [href], iframe')].filter(
                el => el.offsetParent !== null,
            );
            if (!items.length) return;

            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },

        close() {
            if (!this.open) return;
            this.open = false;
            if (this.ctrl) this.ctrl.abort();

            // Keep the image until the fade-out ends, then free the memory.
            clearTimeout(this.revokeTimer);
            this.revokeTimer = setTimeout(() => {
                this.releaseSource();
                this.status = 'idle';
            }, 250);

            const back = this.trigger;
            this.trigger = null;
            if (back && back.isConnected && typeof back.focus === 'function') back.focus();
        },

        releaseSource() {
            if (this.src) URL.revokeObjectURL(this.src);
            this.src = '';
            this.nw = 0;
            this.nh = 0;
        },

        destroy() {
            if (this.ctrl) this.ctrl.abort();
            clearTimeout(this.revokeTimer);
            this.releaseSource();
        },
    }));
});