import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const body = document.body;
const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST;
const role = body?.dataset.realtimeRole;
const userId = body?.dataset.realtimeUserId;

if (key && host && role && (role === 'admin' || userId)) {
    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? (scheme === 'https' ? 443 : 80));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: body.dataset.broadcastAuthUrl || '/broadcasting/auth',
        auth: {
            headers: {
                Accept: 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
        },
    });

    const announce = (detail) => {
        window.dispatchEvent(new CustomEvent('vendo:notification', { detail }));
        window.dispatchEvent(new CustomEvent('vendo:live-update', { detail }));
    };

    const channel = role === 'admin' ? 'admin.notifications' : `users.${userId}`;
    window.Echo.private(channel).listen('.notification.created', announce);

    // An approved listing affects the public Buyer catalog even though its
    // notification belongs to the Seller.
    if (role === 'buyer') {
        window.Echo.channel('catalog').listen('.notification.created', announce);
    }

    window.vendoRealtime = {
        enabled: true,
        channel,
        disconnect() {
            window.Echo?.leave(channel);
            if (role === 'buyer') window.Echo?.leave('catalog');
        },
    };

    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        window.dispatchEvent(new CustomEvent('vendo:realtime-status', {
            detail: { connected: current === 'connected', state: current },
        }));
    });
} else {
    window.vendoRealtime = { enabled: false };
}
