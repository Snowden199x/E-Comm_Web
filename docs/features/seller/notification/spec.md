# Seller Notifications

**Status:** Seller inbox and bell implemented in code; manual verification pending.

## Current behavior

Checkout creates a targeted `new_order` notification. Buyer review submission creates a targeted `product_review` notification. Admin policy publication can create a targeted `platform_announcement` notification. Product review outcomes, stock threshold crossings, shipment status changes and admin support replies now create seller notifications. The seller bell opens recent items; the full inbox filters All/Unread and marks items read. The seller inbox excludes records with `user_id = null`.

## Seller experience

The bell stays in the pinned seller header. It shows an unread badge when needed and opens a compact list of recent notifications. **View all** opens a paginated inbox with All and Unread filters. Opening a notification marks it read and follows its destination. Sellers can also mark one item or all items read. The badge refreshes while the page is open.

Each notification should link to the relevant seller-owned order, product, review or message. The destination must be an internal route the seller is authorized to open. A deleted or inaccessible record should leave a readable notification and route safely to the relevant list. Do not accept an arbitrary external URL from notification data.

## Event coverage

| Event | Current state | Destination and rule |
|---|---|---|
| New order | Created at checkout | Seller Orders; one notification per seller order. |
| Product review | Created after verified purchase | Feedback detail for the reviewed seller product. |
| Policy or platform announcement | Targeted policy notification exists; announcements also appear on dashboard | The inbox displays the notice. A dedicated seller policy view remains future work. |
| Product approved or rejected | Implemented | Product detail and review outcome/reason. |
| Low or out of stock | Implemented | Product detail; emitted when stock crosses the threshold. |
| Shipment status or delivery outcome | Implemented | Shipment or Delivered Orders detail after a status transition. |
| Admin support reply | Implemented | Opens the seller's support thread. |

Stock and shipment events are emitted on changes rather than page reads. A stable event key for broader retry deduplication remains future work. Notification text avoids private buyer contact information. Email and push settings can follow when those channels exist.

## Backend contract

`GET /seller/notifications`, `GET /seller/notifications/recent`, `POST /seller/notifications/{notification}/open`, `POST /seller/notifications/{notification}/read` and `POST /seller/notifications/read-all` run under `auth` and `EnsureActiveSeller`. Every query and update is scoped to `user_id = auth()->id()`; `NULL` recipients are excluded. The full inbox is paginated and newest first. A seller cannot mark another user's notification read.

The dashboard's recent list and the header badge should use the same notification source so their counts agree. Future mobile clients can consume the same recipient-scoped event records without changing the event definitions.

**Related:** [Order notifications](../order-notification/spec.md) · [Seller Messages](../chat-messaging/spec.md) · [Seller domain](../../../domains/Seller.md)
## 25 September 2026 update

The dashboard notification panel and header bell reveal roughly three recent items before scrolling. Both expose View all; the Inventory Alerts panel opens the low-stock inventory filter. The seller bell and dashboard card refresh every three seconds. The bell plays a short tone for newly observed notifications and notification controls, subject to browser audio permission after interaction.
