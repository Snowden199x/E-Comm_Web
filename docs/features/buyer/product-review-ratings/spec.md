# Buyer Product Reviews and Ratings

**Status:** Implemented for buyer review submission and public product review display.
**Backend contract:** [Seller Feedback Management](../../seller/review-management/spec.md).

## Buyer journey to implement

The buyer opens an order after confirming receipt. The existing `POST /buyer/orders/{order}/complete` changes `delivered → completed`; only then does each purchased `order_items` row offer **Write a review**. This includes the product name, variation, quantity and purchased price so the buyer knows which item is being rated. An order can have several eligible items; quantity of one item does not grant extra reviews. A merely delivered, cancelled or returned order is not review-eligible.

Buyer selects 1–5 stars and enters a 10–2000-character plain-text comment. `POST /buyer/order-items/{orderItem}/review` derives buyer, seller, order and product from the owned order item; the request supplies only rating and comment. Enforce one review per item with a database unique constraint and recheck order completion in the write transaction. If already reviewed, show the submitted review and any seller reply instead of another form. The initial policy is one-time submission with no silent edit/delete; admin handles correction requests through the future moderation process.

Published reviews may appear on the product page with verified-purchase context. A hidden review remains visible to its author with a moderation label but does not affect public product or seller ratings. Do not reveal the buyer's phone, address or email publicly. Seller reply is shown below the buyer's text with its timestamp. The seller cannot change the buyer's star count or comment.

## Connection to seller UI

Every published buyer review feeds the seller [Feedback page](../../seller/review-management/spec.md), its rating cards and response status, and eventually the seller dashboard average. The seller's Completed Orders drawer links to reviews for that order. A separate shop-service or courier rating is not created from these product stars.

See [domain status](../../../domain-feature-status.md), [order flow](../../../order-logistics-flow-decisions.md), and [feature implementation guide](../../../feature-implementation-guide.md).
