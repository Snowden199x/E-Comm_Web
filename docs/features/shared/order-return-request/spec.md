# Order Return Request (Buyer asks, Seller decides)

**Status:** Implemented in code, including the 7-day window and the rider trip back to the Seller; owner migration and browser/device verification pending. Money stays display-only: approving a return records what the Buyer is owed and moves no cash.
**Reviewed:** 11 November 2026

## Routes

| Route | Method | Actor | Purpose |
|---|---|---|---|
| `buyer.orders.return.create` (`GET buyer/orders/{order}/return`) | GET | Buyer (owner) | Return request form |
| `buyer.orders.return.store` (`POST buyer/orders/{order}/return`) | POST, throttle 10/min | Buyer (owner) | Submits the request |
| `seller.returns.index` (`GET seller/returns`) | GET | Seller | Requests waiting for a decision, plus recent decisions |
| `seller.returns.approve` (`PATCH seller/returns/{returnRequest}/approve`) | PATCH | Seller (owner) | Approves: `returned`, stock back, refund recorded |
| `seller.returns.reject` (`PATCH seller/returns/{returnRequest}/reject`) | PATCH | Seller (owner) | Declines with a required note |
| `seller.returns.received` (`PATCH seller/returns/{returnRequest}/received`) | PATCH | Seller (owner) | Confirms the parcel is back after the rider collected it |

The Seller route group keeps `auth` + `EnsureActiveSeller`; the Buyer routes keep `auth`. Every lookup is scoped: an order by `buyer_id`, a request by `seller_id`, so another account's id is a 404, not a redirect.

## Buyer behavior

- **7-day window.** A request is accepted only while `now()` is within 7 days of `delivered_at` (`OrderReturnService::RETURN_WINDOW_DAYS = 7`, checked in `canRequest()` and again under the order lock when the request is created). The order page shows the closing date and the days left (`OrderReturnService::daysLeft()`), and after the deadline the button is replaced by a note naming the date. The window is measured from delivery, not from receipt confirmation, so a Buyer who confirms late still gets the same deadline. The Seller-side approval is not windowed: a request made in time can still be approved later. An Admin can extend the deadline per order from [Limits & Overrides](../../admin/limit-overrides/spec.md); the later of the default and `orders.return_window_until` always wins, so an override can never shorten it.
- The form (`buyer/orders/return.blade.php`) offers fixed reasons (`damaged`, `wrong_item`, `missing_item`, `not_as_described`, `other`) and optional details; `other` requires details (max 450 characters). The refund preview is the order's `payable_total` (items − voucher + delivery fee), the cash the Buyer actually paid.
- One pending request at a time per order. A rejected request can be submitted again; an approved one cannot.
- Orders that are not delivered/completed (including already `returned`) are rejected with a 409.

## Seller behavior

- **Returns** in the seller sidebar (Order Management submenu) lists pending requests with order, buyer, reason, details, date, and the refund that approval would record. Approve is one button; Decline requires a note (5–450 characters).
- **Approve** (`OrderReturnService::approve`), in one transaction with the order and request rows locked:
  1. Verifies the request is still `pending` and the order still `delivered`/`completed`.
  2. Returns every item quantity to stock through `InventoryService` (type `return`, reason `Return approved by seller`), restoring variants too.
  3. Sets `orders.status = 'returned'` and writes an `order_status_events` row with the refund amount in the note.
  4. Stores `status = approved`, `decided_by`, `decided_at`, `refund_cents` (frozen at approval), `return_status = pending_pickup` and the assigned delivery Rider as `pickup_rider_id`.
  5. Notifies the assigned Rider to collect the parcel and the destination hub to keep the pickup scheduled.
- **Reject** stores `status = rejected`, the reviewer, the time and `decision_note`; the order is untouched.
- A handled request cannot be handled twice (409).

## Notifications

## Return trip back to the Seller (11 November 2026)

Approving a return restores stock immediately, but the parcel still has to travel back. `order_return_requests.return_status` follows that trip:

- `pending_pickup` — set at approval; the Buyer page reads "Waiting for rider pickup" and the Seller Returns page shows the same.
- `picked_up` — the assigned Rider scans the parcel at the Buyer's address through `POST /api/v1/rider/deliveries/{order}/return-pickup`. `pickup_rider_id` and `picked_up_at` are recorded, a `return_pickup` scan event and a status note are written, and both sides are notified.
- `delivered_to_seller` — the Rider hands it over through `POST /api/v1/rider/deliveries/{order}/return-handover`, or the Seller presses **Mark received** on `seller.returns.index` when the app cannot finish the scan (`ReturnTripService::confirmReceived`). `delivered_to_seller_at` is recorded and both sides are notified.

Only `status = returned` orders with an approved request can start the trip; only the assigned delivery Rider can scan pickup, and only the Rider who collected it can scan the handover. Each scan is idempotent by `scan_key`. The order status stays `returned` throughout: the parcel is already out of the sale and the refund is already recorded. See the [scan API](../../courier/scan-api/spec.md).

## Notifications

| Event | Recipient | Type | Link |
|---|---|---|---|
| Buyer requests a return | Seller | `return_request` | `seller.returns.index` |
| Seller approves | Buyer | `order_update` ("Return approved", refund amount, rider will collect) | Buyer order page |
| Seller approves | Assigned Rider + destination hub | `shipment_update` (collect the return) | Rider notification feed / hub delivery queue |
| Rider collects the return | Buyer and Seller | `order_update` ("picked up", "on the way back") | Order / Returns page |
| Rider hands over / Seller marks received | Buyer and Seller | `order_update` ("handed back", "received") | Order / Returns page |
| Seller declines | Buyer | `order_update` ("Return declined", reason) | Buyer order page |
| Buyer cancels | Seller | `order_update` | Seller order page |
| Seller cancels | Buyer | `order_update` | Buyer order page |
| Either cancels | Assigned Rider(s) / pickup hub | `shipment_cancelled` / `logistics_pickup_cancelled` | — |

Cancellation notifications are unchanged and still sent by `OrderCancellationService::cancelLocked`. The order observer also sends the normal `shipment_update`/`order_update` notice when the status becomes `returned`, so both sides see the state change in the notification feed.

## Effect on earnings (display-only)

`Order::SALES_STATUSES` is `delivered` + `completed`, so a `returned` order drops out of the Seller earnings page and out of the Rider/Driver accrued earnings API automatically. Nothing reverses a settlement row: `order_shipping_settlements` and `order_shipping_earning_lines` keep their original amounts, they are simply no longer counted.

## Not built (deliberate gaps)

- No way to shorten a window or revoke an Admin extension, and no bulk extension.
- No partial or per-line returns, no quantity selection.
- No Seller-side evidence upload or Buyer photos.
- No Admin override, escalation, or appeal after a decision.
- No real refund execution: `refund_cents` is a recorded amount, not a transfer, and there is no refund status beyond `approved`.
- Rider pay and the Logistics share are not adjusted for a return; the display simply stops counting the order.
- The return leg (who ships the item back) is not modelled.

The policy options for real payouts and refund execution stay in [settlement ledger, payouts and return/refund policy](../../../design/settlement-ledger-and-return-policy.md) (unapproved, no code).

## Source evidence

`app/Http/Controllers/Buyer/OrderController.php` (`returnForm`, `requestReturn`), `app/Http/Controllers/Seller/ReturnRequestController.php`, `app/Services/OrderReturnService.php`, `app/Models/Ecommerce/OrderReturnRequest.php`, `app/Models/Ecommerce/Order.php` (`returnRequests`, `latestReturnRequest`), `resources/views/buyer/orders/return.blade.php`, `resources/views/buyer/orders/show.blade.php`, `resources/views/seller/returns/index.blade.php`, `database/migrations/2026_11_11_140000_create_order_return_requests_table.php`.
