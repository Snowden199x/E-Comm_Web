# Seller Domain

## Purpose

Sellers register their identity and business, select categories, await admin approval, then review orders and prepare goods for logistics pickup.

## Implemented scope

Seller registration is OTP-gated and stores seller detail, identity/permit paths, and selected categories. Dashboard data is seller-scoped and includes orders, delivered sales, recent orders, top products, low/out-of-stock alerts, notifications, announcements, and an unavailable rating state until reviews exist. Orders supports ERP status tabs, search/date filters, order details/history/waybill, accepting or declining, preparing, ready-for-pickup, and confirming a pickup after a courier is assigned.

## Products, inventory and shipments

[Products & Inventory](../features/seller/products-inventory/spec.md) is connected to seller product records, admin review, stock/status filters, categories, audited restocks and stock history. [Shipments](../features/seller/shipments/spec.md) reads the same ERP orders as Orders, with tracking metadata, real summaries, customer/item details, pre-pickup cancellation and assigned-rider handoff. The supplied Figma designs are retained as references alongside the implementation specs.

## Remaining gaps

Logistics courier assignment and downstream sorting/delivery actions are not yet implemented. Product deletion/bulk import, configurable inventory thresholds, shipping quotes, seller reports, reviews, messages and account-management workflows remain separate work. No external carrier API is connected; shipment metadata is manually recorded from carrier details.

See [order flow](../order-logistics-flow-decisions.md) for ownership of transitions.
