# Seller Shipments

**Status:** Implemented seller shipment listing/actions, using the shared ERP order record.  
**Updated:** 24 September 2026

## Scope

One order represents one seller shipment in this implementation; no split-parcel aggregate or external carrier API is introduced. Orders with status `placed` stay in Orders until accepted. All other states appear here, including cancellations/returns. Database-backed list, counts, overview percentages, recent updates, order/customer/items/tracking details, and print label use seller-owned records.

## Routes

- `GET /seller/shipments`: search order ID, buyer name, Vendo tracking reference, or carrier tracking; filter by status group, order-created date range, and assigned courier/unassigned; eight-row pagination.
- `GET /seller/shipments/{order}`: right-hand detail drawer and order event history.
- `PATCH /seller/shipments/{order}`: `ship` or `cancel`, with expected ERP status; shipping requires handoff confirmation.
- `PATCH /seller/shipments/{order}/tracking`: persist carrier name/tracking and optional paired ETA dates, with expected status and revision token. Assignment and monetary fields cannot be changed here.

All routes use active-seller middleware and ownership queries. Cards/chart follow search/date/courier filters across all status groups; the table/recent list also apply the selected status. Dates are order-created dates; Recent Shipments sorts by updated time. View all clears the relevant grouping or opens the complete result list.

## States and actions

- To Ship: `confirmed`, `preparing`, `ready_for_pickup`.
- In Transit: `picked_up`, `at_sorting_center`, `sorted`, `assigned_to_rider`, `out_for_delivery`, `delivery_failed` (exact ERP stage remains visible).
- Delivered: `delivered`, `completed`; Cancelled and Returned remain separate groups.
- Mark as Shipped only permits `ready_for_pickup → picked_up`, with an assigned, approved, active courier and seller confirmation of physical handoff.
- Cancel Shipment only permits `confirmed/preparing/ready_for_pickup → cancelled`. It requires a reason, restores reserved stock once, writes stock/status history, and notifies the buyer and assigned courier. In-transit and closed orders cannot be canceled by this screen.
- Tracking details are editable before closure; delivered/completed/cancelled/returned records are read-only.

Both Orders and Shipments call `SellerOrderWorkflow`, so they cannot diverge in status or inventory behavior. Assignment, hub scans, delivery proof and subsequent rider/logistics transitions remain separate unfinished logistics work. No refund/payment-gateway action is performed; checkout currently supports COD.

## Tracking and totals

A stable Vendo reference is persisted for every new order; the migration backfills legacy orders. Carrier references and estimates are optional recorded details, not live API data. Missing carrier/ETA/assignment uses explicit empty states. Shipping fee is a persisted field initialized to zero for existing and new COD orders because checkout currently charges item totals only. Sellers cannot add charges through shipment editing. Carrier tracking/ETA also appear on buyer order details, and labels display the tracking references.

## Files and verification

`Seller/ShipmentController`, `SellerOrderWorkflow`, `Order`, `InventoryService`, the inventory/shipment migration, and `resources/views/seller/shipments/`. Manual acceptance should cover ownership, filters, stale transitions, active rider requirements, tracking persistence and cancellation restoring stock only once. No test files are included, as requested.

[Design reference](design-functions.md) · [Order flow](../../../order-logistics-flow-decisions.md) · [Products & Inventory](../products-inventory/spec.md)
