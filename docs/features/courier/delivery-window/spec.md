# Daily Delivery Window

**Status:** Web cutoff, Rider reschedule endpoint, two-attempt limit and automatic cancellation implemented in code (11 November 2026); owner device verification pending.

Every delivery attempt is due before the cutoff in `config/delivery.php` (`DELIVERY_CUTOFF`, default `17:00`, app timezone). `App\Services\DeliveryCutoff` runs at that moment and marks every order still `out_for_delivery` as `delivery_failed`:

- sets `delivery_failed_at` and writes an `order_status_events` row (`out_for_delivery → delivery_failed`);
- notifies the assigned Rider (`delivery_window_closed`) and the destination Logistics Center;
- the existing Order status listener notifies the Buyer and Seller and adds the parcel conversation message;
- leaves COD cash at `not_collected`; nothing is charged, settled or returned.

The sweep is lazy: the Rider assignment API and the Station Hub operations lists call it before reading, so a rider or staff member loading a list always sees the closed window. `routes/console.php` also schedules it daily at the cutoff for hosts that run the scheduler.

## Two attempts, then automatic cancellation

`orders.delivery_attempts` counts closed attempts; `DeliveryCutoff::MAX_DELIVERY_ATTEMPTS` is **2**.

- **First failure** (`delivery_attempts` becomes 1): the order is `delivery_failed` as above, and the status note says the parcel returns to the destination Station Hub for one final attempt the next day. The Buyer gets an `order_update` explaining the failed attempt, the return to the hub, and that one more failure cancels the order; the Rider and hub notices say the same.
- **Second failure** (an order still `out_for_delivery` at a cutoff with `delivery_attempts = 1`): the sweep calls `cancelUndelivered()`. In the same transaction and row lock it returns every item quantity to stock (`inventory_movements` type `cancellation`, reason `Cancelled automatically after 2 failed delivery attempts`), sets the order to `cancelled`, voids the Seller voucher, writes an `order_status_events` note naming the holding hub, and notifies the Buyer (`order_update`), Seller (`order_update`, stock restored), assigned Rider (`shipment_cancelled`) and destination hub (`shipment_update`).
- **No delivery on the next day**: the same sweep cancels a parcel that is still `delivery_failed` with `delivery_failed_at` before today, and also one that was rescheduled to an earlier day (`assigned_to_rider` with `delivery_scheduled_for` before today and at least one attempt used). A parcel rescheduled for today is untouched, so it cannot sit failed or half-scheduled forever.

A cancelled parcel is terminal: the Buyer sees the normal cancelled order, the Seller earnings page drops it (it is outside `Order::SALES_STATUSES`), and no COD cash was ever collected.

## Reschedule

`POST /api/v1/rider/deliveries/{order}/reschedule` uses the approved Rider Sanctum token. The order must be assigned to that Rider at the Rider's approved center and be `delivery_failed`.

The endpoint sets the order back to `assigned_to_rider`, sets `delivery_scheduled_for` to the next day, and records a status event. It rejects an order that already used its final attempt (`delivery_attempts >= 2`, 409). It saves quietly so the generic "rider assigned" update does not fire, then notifies the Buyer, the Seller and the Station Hub that this is the final attempt. The Rider app shows **Scheduled for &lt;date&gt;** and keeps the parcel on the same Rider for that day's run.

`OrderScanWorkflow` enforces the same window on every scan: `out_for_delivery` and `delivered` are rejected after the cutoff, and `out_for_delivery` is rejected before `delivery_scheduled_for`. A rescheduled parcel must be scanned out on its scheduled day.

Migrations: `2026_10_30_100000_add_delivery_window_to_orders.php` adds `delivery_scheduled_for` and `delivery_failed_at`; `2026_11_11_150000_add_delivery_attempts_to_orders.php` adds `delivery_attempts`.
