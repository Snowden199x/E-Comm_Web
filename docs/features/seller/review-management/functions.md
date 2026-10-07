# Feedback Management — Functional Requirements

**Status:** Planned; no review backend or seller Feedback route exists yet. See [backend implementation spec](spec.md).

## Entry and purpose

The seller opens **Order Management → Feedback** to read ratings and comments left by buyers for purchased products and to respond to them. Use the common seller sidebar/top bar, summary cards, search/filter row, review table, and compact secondary panels.

## Visible functions and intended data

| Page element | Function when implemented |
|---|---|
| Average Rating card | Mean of published product ratings, shown as a value out of five (for example `4.6 / 5`). |
| Total Reviews card | Integer count of published reviews. |
| Customers card | Distinct buyers who wrote published reviews for this seller. |
| Response Rate card | Portion of published reviews with a seller reply. Delivery completion is measured on Completed Orders. |
| Search | Find feedback by order ID, customer, product, or review text. |
| Filters | Rating and reply-status filters. Date range means review creation date. Courier details remain available through the linked order if needed. |
| Review table | Star rating, review snippet, customer, product thumbnail/name, order ID, response state, review date/time, and action. Row count and pagination come from the real query. |
| View | Open a detail drawer showing the complete review, purchased item/variation, order link, current visibility, seller reply, and eligible actions. |
| Overflow menu | View, Reply when unanswered, and Report for admin moderation. There is no seller Delete/Hide command. |
| Lower panels | **Rating Breakdown** (1–5-star distribution) and **Recent Feedback**, each based on published reviews. |

This implementation contract covers **verified product reviews**. A separate shop-service rating needs its own buyer question and data source; it must not be derived from product stars.

## Detail and empty states

An unanswered review offers a seller reply form. After submission, show the public reply and timestamp; do not permit the seller to change the buyer's rating or comment. A reported review remains visible unless an admin moderates it. Empty feedback uses a neutral invitation to await verified buyer reviews. Links from Completed Orders can prefilter this page by `order` and show **No feedback for this order yet** if empty.

## Relationship to the order flow

Buyers can review a purchased line item after they confirm receipt (`orders.status = completed`). `delivered` without buyer confirmation remains visible in Completed Orders but is not yet review-eligible. The seller sees only reviews for their own order items; a buyer cannot review an unpurchased product through this screen.

[Backend implementation spec](spec.md) · [Buyer review plan](../../buyer/product-review-ratings/spec.md) · [Completed Orders](../completed-orders/spec.md)
