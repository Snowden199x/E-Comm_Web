# Seller Feedback Management — backend implementation contract

**Status:** Implemented for the seller and buyer web flows, with admin moderation.
**Functional requirements:** [Feedback functions](functions.md).

## Product and actor rules

One product review belongs to one purchased `order_items` row. A buyer may submit it only for their own order after `orders.status = completed` (buyer-confirmed receipt). The item must belong to that order, and the order's `seller_id` must match the item's product seller. The buyer may create at most one review per item; quantity greater than one does not create extra review slots. Multiple separate purchases can each have a review. Only the original buyer controls the review text/rating; the seller may post one public reply or report it to admin. Seller cannot delete, hide, rate, or edit customer feedback. Initial implementation treats submitted buyer reviews and seller replies as immutable; corrections require a documented admin process, avoiding silent rating changes.

This page tracks product reviews only. A separate service-rating feature would need its own buyer input and metric. Do not combine courier performance or delivery completion with product stars.

## Database changes to implement

Create `product_reviews` with: `id`, unique `order_item_id`, indexed `order_id`, indexed `product_id`, indexed `seller_id`, `buyer_id`, `rating` (integer 1–5), `comment` (plain text, 10–2000 characters), `visibility` (`published` or `hidden`, default `published`), nullable `moderated_by`/`moderated_at`/`moderation_reason`, and timestamps. Use foreign keys to existing order/item/product/users records and keep reviews through normal account suspension; never cascade-delete a review merely because a product listing is archived. The `order_item_id` unique constraint is the final duplicate-submission guard.

Create `product_review_replies` with unique `product_review_id`, `seller_id`, reply body (1–2000 plain-text characters), and timestamps. Create `product_review_reports` with unique (`product_review_id`, `seller_id`), reason (10–500 characters), status (`open`, `resolved`, `dismissed`), nullable admin resolution fields, and timestamps. Admin moderation changes are audit events: persist actor, previous/new visibility, reason and time (either a dedicated review moderation history table or the project's audit mechanism, if it then covers these events). Do not allow sellers to set visibility through form fields.

The order-item/product links are authoritative. If product deletion is added later, retain a reviewable product/line snapshot (name, SKU, image) before allowing deletion so historic review and seller order pages do not break. In the current code products have no seller delete endpoint.

## Routes and permissions

| Route | Purpose and guard |
|---|---|
| `GET /seller/feedback` (`seller.feedback.index`) | `auth` + `EnsureActiveSeller`; list/metrics for `seller_id = auth()->id()` only. Optional `order` filter must still be seller-owned. |
| `GET /seller/feedback/{review}` (`seller.feedback.show`) | Seller-owned review details/related order item; return 404 for another seller's review. |
| `POST /seller/feedback/{review}/reply` (`seller.feedback.reply`) | One reply to seller-owned, published review; validate body and enforce unique review reply in a transaction. Duplicate/stale submission returns a useful conflict. |
| `POST /seller/feedback/{review}/report` (`seller.feedback.report`) | Seller-owned review report with reason; one report per review/seller, routed to admin queue. |
| `POST /buyer/order-items/{orderItem}/review` (`buyer.reviews.store`) | Authenticated owner, parent order `completed`, item/product ownership rechecked inside transaction. |
| `GET /admin/review-reports` (`admin.review-reports.index`) | Existing admin active-account middleware; list open/resolved/dismissed reports with review, order and seller context. |
| `GET /admin/review-reports/{report}` (`admin.review-reports.show`) | Inspect the original review, seller report, order item and any seller reply. |
| `POST /admin/reviews/{review}/visibility` (`admin.reviews.visibility`) | Set `published`/`hidden` with required reason; persist actor and audit event in one transaction. |
| `POST /admin/review-reports/{report}/resolve` (`admin.review-reports.resolve`) | Resolve or dismiss with required admin note; this action alone does not alter review visibility. |

The buyer's completed-order detail should expose the review form or link for each eligible item. Public product pages may show only published ratings/reviews; seller dashboard average rating should use the same published set. Buyer should see their own submitted review and, if hidden, a moderation label. Admin and the owning seller may inspect hidden records; hidden rows are excluded from public rating aggregates, seller metric cards, lower panels, and default table, with an explicit admin/moderation view as needed. Never leak buyer contact details in public reviews.

## Feedback page query contract

- Base query: published `product_reviews` for the authenticated seller. Default sort `created_at DESC, id DESC`; eight rows/page with preserved filters.
- Search (trimmed, max 100) by order ID (`VN-...` or numeric), buyer name, product name/SKU, or review comment. Order, product and buyer joins stay within seller scope. `order` query parameter from Completed Orders is an additional seller-owned filter.
- Rating filter: `1` through `5` or all. Reply filter: all, replied, unanswered. Date range filters `product_reviews.created_at` inclusively in the app timezone; reject invalid/reversed dates. No courier or shipment-status filter is applied to product reviews.
- Card counts follow search/order/date filters but ignore rating/reply filter so the seller can compare the rating and response distribution while narrowing the table; table and recent list apply all filters. **Average Rating** = sum(rating)/review count, rounded to one decimal; **Total Reviews** = count; **Customers** = distinct buyer IDs; **Response Rate** = published reviews with replies / published reviews × 100. On zero reviews, show `—` for average and `0%` for response rate, not `0/5` as a real score.
- Rating Breakdown lists five integer counts and percentages over the same card scope. Recent Feedback lists the newest four reviews from the fully filtered query. Product names/images are loaded in batches; avoid one query per row.
- Table response state is `Replied` or `Awaiting reply`. A review reported for moderation can show `Reported` separately without implying it is hidden. Display star glyphs with a textual `n out of 5` label for accessibility.

## Write flow and notifications

Buyer review creation validates owner, item, completed order, rating/comment, and absence of an existing review inside a transaction; the database unique key settles concurrent requests. Do not accept buyer/seller/product/order IDs or visibility from the submitted form. Derive them from the locked order item and parent order. Store plain text and escape it when rendering; no HTML or files in the initial review form. Notify the seller of a new published review with a link to the owned feedback detail.

Seller reply creation validates ownership and publication state inside a transaction, saves the single reply, and notifies the buyer. Seller reporting validates ownership, persists the reason, and notifies/admin-queues moderators; reporting does not change publication or rating metrics. Admin hide/restore actions require a reason, preserve the original review and reply, and refresh public product rating aggregates. All writes use CSRF protection, role middleware, and clear 403/404/409/422 responses for unauthorized, missing, stale/duplicate, and invalid requests.

## Connection to Completed Orders and shipments

`View Customer Feedback` on a completed order opens `seller.feedback.index` with an owned order filter. An order with no reviews shows an empty state. One order may have several reviewed line items, so do not assume one feedback row per order. Review detail links back to that order and its shipment timeline. Carrier name and rider, if relevant to a complaint, are read from the linked order; this product-review page does not rate a courier. Buyer confirmation (`delivered → completed`) is the review eligibility event; seller does not perform it.

## Implementation sequence

1. Add review/reply/report/moderation schema and Eloquent relations. Keep existing orders/products intact.
2. Add buyer submission and completed-order eligibility UI, then seller list/detail/reply/report UI, and finally admin moderation and public product aggregates. Wire the seller sidebar only when its route works.
3. Replace the seller dashboard's unavailable rating state using the same published-review query; share the query for future mobile API rather than duplicating the rules.

[Functional requirements](functions.md) · [Buyer review plan](../../buyer/product-review-ratings/spec.md) · [Completed Orders](../completed-orders/spec.md) · [Seller domain](../../../domains/Seller.md)
