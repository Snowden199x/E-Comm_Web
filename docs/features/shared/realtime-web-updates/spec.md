# Web real-time updates and notifications

**Status:** WebSocket delivery implemented in code; local Reverb and Azure service configuration required; owner browser verification pending.
**Reviewed:** 17 November 2026

## Behavior

The web app uses Laravel Reverb and Laravel Echo to announce newly-created in-app notification records to the intended recipient. The notification database row remains the source of truth. The websocket payload contains only the notification ID and type; the browser refreshes its existing role-authorized feed to render text, destinations, unread count and alert UI.

| Recipient | Private channel | Feed / authorization |
|---|---|---|
| Buyer, Seller, Logistics Company, Province Hub or Station Hub | `users.{authenticated user id}` | Existing role feed; channel callback checks the session user ID, role, approval and active state. |
| Admin and Super Admin | `admin.notifications` | Shared Admin feed; channel callback requires the `admin` guard and an active Admin account. |
| Buyer catalog viewers | Public `catalog` channel for `product_approved` only | The event carries no product data; the page checks its existing scoped catalog revision. |

Every row created through `App\Models\Communication\Notification` is broadcast after the database transaction commits. A failed Reverb publish is logged by notification ID and exception class; it does not undo a committed order, scan or account action. Legacy null-recipient `new_order` rows remain excluded from the Admin channel, matching the current Admin feed.

The notification bell refreshes immediately after the websocket event. Existing role-specific polling remains as a fallback at 30-second intervals and on tab visibility. Current `data-live-revision` pages also check their server-side revision after a live event. If the revision changes, pages configured for automatic refresh reload in place only when no dialog or unsaved form is present; otherwise they show the existing refresh notice. This is not a partial-DOM replacement and only pages with a revision endpoint participate. Message threads keep their own polling and endpoints.

## Event and recipient boundaries

The event does not invent new notification rows. Transaction coverage and recipients are the rows created by current workflows:

- **Buyer:** owned order, delivery, return, message, voucher, account-standing and support updates.
- **Seller:** new order, product review, stock threshold, product decision, shipment/return, voucher and support updates.
- **Logistics:** assigned rider/driver work, station or network operations, parcel scans, trips/handoffs and support activity targeted by existing workflows.
- **Admin:** registration, product review, reports/complaints, support, account-profile-photo and platform-operation events only. Buyer purchases and routine fulfillment scans do not create Admin notifications.

Rider and Transport Driver user interfaces and clients remain in `E-Comm_Mobile`; this change does not modify or add mobile broadcasting.

## Local setup

`php artisan install:broadcasting --reverb` and `php artisan reverb:install` create the Laravel/Echo/Reverb scaffolding. Keep `REVERB_APP_SECRET` server-side. Run `php artisan reverb:start --host=127.0.0.1 --port=8080` in a persistent local terminal. For local Vite, the `VITE_REVERB_*` host/scheme/port must point at that local listener. If credentials or host are incomplete, Laravel falls back to the null broadcaster and the web app uses polling.

## Azure production setup

Set the following in `/var/www/vendo/.env` and keep the secret private:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<app-id>
REVERB_APP_KEY=<public-key>
REVERB_APP_SECRET=<private-secret>
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_ALLOWED_ORIGINS=https://vendo-ph.app,https://www.vendo-ph.app,http://localhost:8000,http://127.0.0.1:8000
```

Keep the private Reverb process bound to loopback. Run `php artisan reverb:start --host=127.0.0.1 --port=8080` under a persistent systemd service (not an SSH shell). Nginx must proxy both `/app/` WebSocket traffic and `/apps/` publish requests to `http://127.0.0.1:8080` with HTTP/1.1 and the WebSocket `Upgrade`/`Connection` headers. Cloudflare Tunnel should continue to expose only the site's HTTPS hostname; do not expose port 8080 publicly.

The browser bundle needs the same public key but a public endpoint. Before the production Vite build, set:

```dotenv
VITE_REVERB_APP_KEY=<same-public-key>
VITE_REVERB_HOST=vendo-ph.app
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Then copy the resulting `public/build` assets to the VM, deploy the Laravel code, run `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`, and start/enable the Reverb service. Configure the Cloudflare/Nginx WebSocket proxy before expecting browser events. The repository cannot configure the external VM, its systemd state, or the Cloudflare Tunnel.

## Source files

- `app/Events/NotificationCreated.php`
- `app/Models/Communication/Notification.php`
- `routes/channels.php`
- `config/broadcasting.php`, `config/reverb.php`
- `resources/js/echo.js`, `resources/js/shared/app.js`
- `resources/views/shared/live-revision-script.blade.php`

## Verification limits

PHP syntax and static frontend source review are distinct from runtime acceptance. The owner must start a local Reverb process and verify simultaneous Buyer, Seller, Logistics and Admin sessions, subscription authorization, notification sounds, transaction refreshes, browser reconnection, form preservation, Nginx/Cloudflare WebSocket proxying and Azure service restart behavior. No automated tests or browser walkthrough were run for this change.
