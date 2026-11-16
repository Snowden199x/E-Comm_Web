# Logistics Portal Notifications

**Status:** Role-scoped in-app notifications use Reverb with polling fallback; Azure service and owner verification pending.
**Reviewed:** 17 November 2026

Company, Province Hub and Station Hub users receive only notification rows addressed to their authenticated user. Operational notification rows cover assigned Rider/Driver work, parcel scans and handoffs, trip/network actions, support messages and account/review events produced by current workflows. The shared bell in the Logistics portal reads the user's own notifications and opens only authorized portal destinations.

The bell refreshes immediately from the private `users.{id}` channel. If Reverb is offline or not configured, `GET /logistics/portal/notifications/feed` is polled every 30 seconds and when the browser tab becomes visible. The WebSocket payload is limited to notification ID and type; the portal retrieves titles and links from its authenticated feed. The shared `logistics_center` user role covers company managers, and membership/approval remains enforced on Logistics HTTP routes.

This does not create an Admin copy of parcel or Rider notifications. Admin remains limited to platform-operation events. Rider and Transport Driver app notification delivery stays in the separate mobile repository and is unchanged by this web implementation.

**Code:** `app/Http/Controllers/Logistics/PortalNotificationController.php`, `resources/views/components/logistics/shell.blade.php`, `routes/channels.php`, `app/Events/NotificationCreated.php`.

**Infrastructure:** Local and Azure Reverb settings, systemd, and Nginx/Cloudflare WSS proxy requirements are documented in [Web real-time updates](../../shared/realtime-web-updates/spec.md).
