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

## 13 November 2026: page restyle

The page follows the Admin design system. Two tiles show the rules (return window days, attempts per parcel). Search runs on Enter with **Clear filters**. Each row shows the order status as a pill, attempts used out of allowed, and the return deadline with how long is left (amber under two days). The row buttons **Grant attempt** and **Extend window** open one popup each, with a reason box (5 to 450 characters, with a counter and two quick reasons), and the window popup adds a date picker with 3, 7 and 14 days later shortcuts. The reason and date are checked in the browser first. If the server refuses (for example the parcel changed status in the meantime, or the order has no delivery date), the page returns with a message that says what to do and the popup reopens with what was typed. Success messages name the order. The rules, notifications, order timeline entries and audit log are unchanged.

## Source evidence

`app/Http/Controllers/Admin/LimitOverrideController.php`, `resources/views/admin/limit-overrides/index.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `app/Services/OrderReturnService.php`, `app/Services/DeliveryCutoff.php`, `database/migrations/2026_11_11_160000_add_limit_overrides_to_orders.php`.
