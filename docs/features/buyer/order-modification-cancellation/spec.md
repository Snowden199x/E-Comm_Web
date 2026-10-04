# Order Cancellation/Modification

**Status:** Buyer and Seller pre-pickup cancellation implemented
**Reviewed:** 4 October 2026

## Current behavior

Buyer can cancel an owned order while it is `placed`, `confirmed`, `preparing`, or `ready_for_pickup`. A reason modal requires a selection, and selecting Other requires details. Seller can decline a newly placed order or cancel an order through the same pre-pickup stages from Orders or Shipments; Seller cancellation also requires a reason modal.

Cancellation is serialized under an order row lock. It restores reserved product or variant stock through `InventoryService`, changes the order to `cancelled`, records the actor and reason in `order_status_events.note`, and notifies the other party. An assigned rider and a Logistics Center with a pending pickup request are notified as well. Cancellation is unavailable after pickup; there is no refund flow because the current checkout uses COD.

## Gaps and acceptance direction

There is no return/refund flow, buyer order change request, or time-based cancellation cutoff. Cancellation eligibility is currently based on status: it closes when the Rider has picked up the parcel.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`, `app/Http/Controllers/Buyer/OrderController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
