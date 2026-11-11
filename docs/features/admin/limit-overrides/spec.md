# Admin Limits & Overrides

**Status:** Implemented in code; owner migration and browser verification pending.
**Reviewed:** 11 November 2026

Admin can change the two hard limits the Buyer and Logistics flows enforce: the **7-day return window** and the **two delivery attempts**. Money stays display-only; these are lifecycle overrides, not refunds or payouts.

## Routes

| Route | Method | Purpose |
|---|---|---|
| `admin.limit-overrides.index` (`GET admin/limits`) | GET | Search an order and see both limits |
| `admin.limit-overrides.attempt` (`POST admin/limits/{order}/attempt`) | POST | Grant one more delivery attempt |
| `admin.limit-overrides.window` (`POST admin/limits/{order}/window`) | POST | Push the return deadline later |

All three sit inside the Admin group (`auth:admin`, `check.admin.active`, `force.password.change`). The sidebar entry is **Limits & Overrides** (`rotate-ccw` icon), directly after Reports.

## Page

- Without a search it lists up to 30 orders that have a failed attempt, an extra attempt, an extended window or any return request, newest first.
- A search accepts `VN-000123` / `#123` / the bare id, or a tracking number.
- Each row shows the order and tracking number, buyer and seller, parcel status, `delivery_attempts of max` with the last failure time and any granted extras, and the current return deadline. Overrides use `<details>` forms so the table stays compact, and a failed save reopens the page with the error and the typed values.

## Granting one more attempt

- Allowed only while the order is `delivery_failed` or `assigned_to_rider`, at least one attempt has failed, and fewer than **3** extras were already granted.
- In one transaction with the order row locked: `delivery_extra_attempts + 1`, `delivery_failed_at = now()` (which restarts the next-day clock), and `limit_override_by` / `limit_override_at` / `limit_override_note` are set from the signed-in Admin and the required reason (5–450 characters).
- The effective maximum becomes `DeliveryCutoff::MAX_DELIVERY_ATTEMPTS + delivery_extra_attempts`, which the cutoff sweep, the stale-parcel sweep and the Rider reschedule endpoint all read.
- Notifies the Buyer, the assigned Rider and the destination hub.

## Extending the return window

- Allowed only for `delivered` or `completed` orders with a `delivered_at`.
- The new date must be later than the current deadline; the stored value is the chosen date at 23:59:59 (`orders.return_window_until`).
- `OrderReturnService::windowClosesAt()` uses the later of `delivered_at + 7 days` and the override, so an override can never shorten the default window; `canRequest()` and the Buyer pages follow it automatically.
- Notifies the Buyer with the new deadline.

## Audit

Every override writes an `order_status_events` row with the same `from_status`/`to_status` (no lifecycle change) whose note is `Admin <name> granted one more delivery attempt (...)` or `Admin <name> extended the return window to <date>: <reason>`. The same Admin, time and reason are also stored on the order (`limit_override_by`, `limit_override_at`, `limit_override_note`) for the page and for later reports. There is no separate audit table.

## Not built

- No revoke/undo action and no bulk override; a second grant is a separate action and a shorter window cannot be set.
- No reason categories, attachment or approval-of-override workflow.
- The page does not create refunds or payouts; it only moves deadlines and attempt limits.

## Source evidence

`app/Http/Controllers/Admin/LimitOverrideController.php`, `resources/views/admin/limit-overrides/index.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `app/Services/OrderReturnService.php`, `app/Services/DeliveryCutoff.php`, `database/migrations/2026_11_11_160000_add_limit_overrides_to_orders.php`.
