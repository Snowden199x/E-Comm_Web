# Order Return Request (Buyer asks, Seller decides)

**Status:** Implemented in code, including the 7-day window; owner migration and browser verification pending. Money stays display-only: approving a return records what the Buyer is owed and moves no cash.
**Reviewed:** 11 November 2026

## Routes

| Route | Method | Actor | Purpose |
|---|---|---|---|
| `buyer.orders.return.create` (`GET buyer/orders/{order}/return`) | GET | Buyer (owner) | Return request form |
| `buyer.orders.return.store` (`POST buyer/orders/{order}/return`) | POST, throttle 10/min | Buyer (owner) | Submits the request |
| `seller.returns.index` (`GET seller/returns`) | GET | Seller | Requests waiting for a decision, plus recent decisions |
| `seller.returns.approve` (`PATCH seller/returns/{returnRequest}/approve`) | PATCH | Seller (owner) | Approves: `returned`, stock back, refund recorded |
| `seller.returns.reject` (`PATCH seller/returns/{returnRequest}/reject`) | PATCH | Seller (owner) | Declines with a required note |

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
  4. Stores `status = approved`, `decided_by`, `decided_at`, `refund_cents` (frozen at approval).
- **Reject** stores `status = rejected`, the reviewer, the time and `decision_note`; the order is untouched.
- A handled request cannot be handled twice (409).

## Notifications

| Event | Recipient | Type | Link |
|---|---|---|---|
| Buyer requests a return | Seller | `return_request` | `seller.returns.index` |
| Seller approves | Buyer | `order_update` ("Return approved", refund amount) | Buyer order page |
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
