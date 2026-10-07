# Order History and Tracking

**Status:** Partial but buyer timeline is wired  
**Reviewed:** 6 October 2026

## Current behavior

**My Orders** (`buyer.orders.index`) lists the Buyer's orders newest first as cards with the shop, up to two items with photos, the item count, the total, a status badge, and actions (Chat with seller; View details, Confirm receipt, or View and rate depending on status). Tabs filter in the browser: All, Placed, To ship, In transit, Delivered, and Cancelled and returned, each with a count and its own empty state. **Order detail** (`buyer.orders.show`) shows a progress stepper, an order updates timeline (newest first), the items with the review form, an order summary, the delivery address, the seller card, and shipment tracking. Buyer sees owned orders and status events. The assigned delivery rider's `delivered` scan marks the parcel delivered; the buyer can then confirm receipt. The final progress label reads **Rate Product** and links to the product review section once receipt is confirmed. The internal order status remains `completed`, and reviews require that status.

## Gaps and acceptance direction

The 6 October pages read items, products, variants, and the shop name lazily; eager loading is listed in [backend needs](../../../backend-needs.md), item 6. The tabs and list are not paginated.

Buyer and Seller may cancel orders before pickup with a recorded reason; cancellation restores reserved stock. Independent delivery proof, failed delivery handling and return/refund handling still need implementation.

## Source evidence

`app/Http/Controllers/Buyer/OrderController.php`, `resources/views/buyer/orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).