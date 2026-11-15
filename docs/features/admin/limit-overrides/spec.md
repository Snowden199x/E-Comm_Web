# Admin Limits & Overrides

**Status:** Implemented in code; owner migration and browser verification pending.
**Reviewed:** 11 November 2026

Admin manages the Buyer **7-day return window**. Delivery attempt exceptions belong to the destination Station Hub because it manages the last-mile delivery. Money stays display-only; these are lifecycle overrides, not refunds or payouts.

## Routes

| Route | Method | Purpose |
|---|---|---|
| `admin.limit-overrides.index` (`GET admin/limits`) | GET | Search an order and see both limits |
| `admin.limit-overrides.window` (`POST admin/limits/{order}/window`) | POST | Push the return deadline later |

All three sit inside the Admin group (`auth:admin`, `check.admin.active`, `force.password.change`). The sidebar entry is **Limits & Overrides** (`rotate-ccw` icon), directly after Reports.

## Page

- Without a search it lists up to 30 orders that have a failed attempt, an extra attempt, an extended window or any return request, newest first.
- A search accepts `VN-000123` / `#123` / the bare id, or a tracking number.
- Each row shows the order and tracking number, buyer and seller, parcel status, `delivery_attempts of max` with the last failure time and any granted extras, and the current return deadline. Overrides use `<details>` forms so the table stays compact, and a failed save reopens the page with the error and the typed values.

Delivery attempts remain visible as platform rules; this page no longer grants extra attempts. That action is available in the destination Station Hub Delivery Queue.

## Extending the return window

- Allowed only for `delivered` or `completed` orders with a `delivered_at`.
- The new date must be later than the current deadline; the stored value is the chosen date at 23:59:59 (`orders.return_window_until`).
- `OrderReturnService::windowClosesAt()` uses the later of `delivered_at + 7 days` and the override, so an override can never shorten the default window; `canRequest()` and the Buyer pages follow it automatically.
- Notifies the Buyer with the new deadline.

## Audit

Every Admin return-window override writes an `order_status_events` row with the same `from_status`/`to_status` (no lifecycle change), plus the Admin, time and reason on the order (`limit_override_by`, `limit_override_at`, `limit_override_note`). Logistics attempt grants are recorded in the same order timeline and override fields, attributed to the Station Hub manager. There is no separate audit table.

## Not built

- No revoke/undo action and no bulk override; a second grant is a separate action and a shorter window cannot be set.
- No reason categories, attachment or approval-of-override workflow.
- The page does not create refunds or payouts; it only moves deadlines and attempt limits.

## 15 November 2026: ownership update

The Admin page now grants return-window extensions only. Extra delivery attempts are granted by the destination Station Hub from its Delivery Queue; the Admin attempt route and form were removed. The standard attempt count and maximum of three extras are unchanged.

## 13 November 2026: page restyle

The page follows the Admin design system. Two tiles show the rules (return window days, attempts per parcel). Search runs on Enter with **Clear filters**. Each row shows the order status as a pill, attempts used out of allowed, and the return deadline with how long is left (amber under two days). The row buttons **Grant attempt** and **Extend window** open one popup each, with a reason box (5 to 450 characters, with a counter and two quick reasons), and the window popup adds a date picker with 3, 7 and 14 days later shortcuts. The reason and date are checked in the browser first. If the server refuses (for example the parcel changed status in the meantime, or the order has no delivery date), the page returns with a message that says what to do and the popup reopens with what was typed. Success messages name the order. The rules, notifications, order timeline entries and audit log are unchanged.

## Source evidence

`app/Http/Controllers/Admin/LimitOverrideController.php`, `resources/views/admin/limit-overrides/index.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `app/Services/OrderReturnService.php`, `app/Services/DeliveryCutoff.php`, `database/migrations/2026_11_11_160000_add_limit_overrides_to_orders.php`.
