# Shipment Fulfillment

**Status:** Seller workflow implemented; logistics/courier fulfillment remains partial.

Orders store a courier reference, stable Vendo tracking reference, optional carrier/ETA details, shipping fee, ERP status and event history. Seller Orders and Shipments use one transaction-backed transition service. Seller can prepare, confirm active-rider handoff and cancel before pickup, with audited stock restoration. Buyer can confirm receipt after delivery.

A shipment currently corresponds to one seller order; split parcels, courier assignment, hub scans, automated ETA/quote integration and courier delivery actions are not implemented. Checkout currently charges item totals only, with shipping fee zero; the seller cannot change buyer charges via tracking edits.

[Seller Shipments](../../seller/shipments/spec.md) · [Order flow](../../../order-logistics-flow-decisions.md)
