# Order History and Tracking

**Status:** Partial but buyer timeline is wired  
**Reviewed:** 24 September 2026

## Current behavior

Buyer sees owned orders and status events. The assigned delivery rider's `delivered` scan marks the parcel delivered; the buyer can then confirm receipt. The final progress label reads **Rate Product** and links to the product review section once receipt is confirmed. The internal order status remains `completed`, and reviews require that status.

## Gaps and acceptance direction

Independent delivery proof, failed delivery and return/refund handling still need implementation.

## Source evidence

`app/Http/Controllers/Buyer/OrderController.php`, `resources/views/buyer/orders/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
