# Courier Notifications

**Status:** Rider notification feed, assignment alerts and in-app banner implemented; push delivery not implemented
**Reviewed:** 30 October 2026

## Current behavior

- `GET /api/v1/rider/notifications` returns the signed-in Rider's rows from the shared `notifications` table, newest first (50 max), with `unread_count` and `latest_id`. `?since=<id>` returns only rows newer than the app's watermark, so the mobile app can poll cheaply.
- `POST /api/v1/rider/notifications/{notification}/read` and `POST /api/v1/rider/notifications/read-all` change read state. A Rider cannot read another Rider's row (404).
- A Rider is notified when:
  - a Station Hub assigns a pickup or delivery task (`StationOperationsController::assign`);
  - a Main Hub assigns a pickup courier (`DispatchController::assignCourier`);
  - a Main Hub assigns a delivery rider, single or bulk (`assignDeliveryLocked`);
  - a Main Hub assigns a Truck linehaul rider (`assignLinehaulRider`);
  - a Station Hub pickup assignment is released after 60 unscanned minutes (`StationPickupExpiry`).
- The Rider mobile app polls the feed every 25 seconds while the dashboard is open and again on resume, shows an unread badge on the Notifications tab, and slides a dismissible banner over the top of the dashboard for new unread items. Tapping the banner opens the Notifications tab.

## Gaps and acceptance direction

This is not push delivery: a closed app receives nothing until it is opened and polls. There are no websockets, device notifications, per-type routing, or deep links from a notification to the specific parcel. The banner is in-app only.

## Source evidence

- `app/Http/Controllers/Api/RiderNotificationController.php`
- `routes/api.php`
- `app/Http/Controllers/Logistics/StationOperationsController.php`
- `app/Http/Controllers/Logistics/DispatchController.php`
- `app/Services/StationPickupExpiry.php`
- `tests/Feature/RiderNotificationApiTest.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), [testing](../../../testing.md), and the Rider integration notes in the mobile repository.
