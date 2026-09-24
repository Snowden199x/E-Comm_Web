# Order and Logistics Flow Decisions

## Current order status vocabulary

The `Order` model defines: `placed`, `confirmed`, `preparing`, `ready_for_pickup`, `picked_up`, `at_sorting_center`, `sorted`, `assigned_to_rider`, `out_for_delivery`, `delivered`, `completed`, `delivery_failed`, `returned`, and `cancelled`. Buyer checkout creates `placed`. `order_status_events` stores each transition and note.

## Current actor ownership

| Transition/action | Current owner in code | Current implementation |
|---|---|---|
| Place order; reserve stock | Buyer checkout | Implemented; stock is checked/locked and decremented at checkout. |
| Accept or decline `placed` order | Seller | Implemented; decline requires a reason and restores stock once in its transaction. |
| `confirmed` → `preparing` | Seller | Implemented. |
| `preparing` → `ready_for_pickup` | Seller | Implemented. |
| `ready_for_pickup` → `picked_up` | Seller confirms handoff only after a courier is assigned | Implemented with courier assignment precondition; assignment itself is not implemented in this repository. |
| Sorting scans, hub/linehaul, rider assignment, out-for-delivery | Logistics / courier | Statuses exist, but corresponding operational action routes are not implemented. |
| `delivered` → `completed` | Buyer confirms receipt | Implemented on buyer order detail for delivered orders. |
| Failed delivery, return, refund | Logistics/admin policy still needed | Status labels exist; complete exception/refund actions are not implemented. |

## Buyer-visible grouping

Seller Orders groups map multiple ERP states into UI tabs: New, To Pack, Ready for Pickup, Pending Delivery, Delivered/Completed, Cancelled, and Returned. “Pending Delivery” includes states after pickup through delivery failure; the logistics team still needs to supply those transitions.

## Decisions still required

- Define who creates an assignment and whether one shipment may contain multiple seller orders.
- Define scan events, hub arrival/departure, service-level timestamps, failed-attempt reasons, return-to-seller, and proof requirements.
- Decide cancellation cutoffs and refund/payment behavior for COD and any future online payments.
- Define status-event retention, customer-facing wording, and notifications for each transition.
- Replace coarse order-level shipping with a shipment/parcel model if split packages or consolidation are required.
