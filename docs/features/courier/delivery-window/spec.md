# Daily Delivery Window

**Status:** Web cutoff, Rider reschedule endpoint, and Rider app UI implemented in code; owner device verification pending.

Every delivery attempt is due before the cutoff in `config/delivery.php` (`DELIVERY_CUTOFF`, default `17:00`, app timezone). `App\Services\DeliveryCutoff` runs at that moment and marks every order still `out_for_delivery` as `delivery_failed`:

- sets `delivery_failed_at` and writes an `order_status_events` row (`out_for_delivery → delivery_failed`);
- notifies the assigned Rider (`delivery_window_closed`) and the destination Logistics Center;
- the existing Order status listener notifies the Buyer and Seller and adds the parcel conversation message;
- leaves COD cash at `not_collected`; nothing is charged, settled or returned.

The sweep is lazy: the Rider assignment API and the Station Hub operations lists call it before reading, so a rider or staff member loading a list always sees the closed window. `routes/console.php` also schedules it daily at the cutoff for hosts that run the scheduler.

## Reschedule

`POST /api/v1/rider/deliveries/{order}/reschedule` uses the approved Rider Sanctum token. The order must be assigned to that Rider at the Rider's approved center and be `delivery_failed`.

The endpoint sets the order back to `assigned_to_rider`, sets `delivery_scheduled_for` to the next day, and records a status event. It saves quietly so the generic "rider assigned" update does not fire, then notifies the Buyer, the Seller and the Station Hub with the new date. The Rider app shows **Scheduled for &lt;date&gt;** and keeps the parcel on the same Rider for that day's run.

`OrderScanWorkflow` enforces the same window on every scan: `out_for_delivery` and `delivered` are rejected after the cutoff, and `out_for_delivery` is rejected before `delivery_scheduled_for`. A rescheduled parcel must be scanned out on its scheduled day.

Migration `2026_10_30_100000_add_delivery_window_to_orders.php` adds `delivery_scheduled_for` and `delivery_failed_at`.
