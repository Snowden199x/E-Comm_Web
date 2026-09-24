# Seller Domain

## Purpose

Sellers register their identity and business, select categories, await admin approval, then review orders and prepare goods for logistics pickup.

## Implemented scope

Seller registration is OTP-gated and stores seller detail, identity/permit paths, and selected categories. Dashboard data is seller-scoped and includes orders, delivered sales, recent orders, top products, low/out-of-stock alerts, notifications, announcements, and an unavailable rating state until reviews exist. Orders supports ERP status tabs, search/date filters, order details/history/waybill, accepting or declining, preparing, ready-for-pickup, and confirming a pickup after a courier is assigned.

## Products, inventory and shipments

[Products & Inventory](../features/seller/products-inventory/spec.md) is connected to seller product records, admin review, stock/status filters, categories, audited restocks and stock history. [Shipments](../features/seller/shipments/spec.md) reads the same ERP orders as Orders, with tracking metadata, real summaries, customer/item details, pre-pickup cancellation and assigned-rider handoff.

## Next seller screens documented

[Completed Orders](../features/seller/completed-orders/spec.md) is planned as a seller-scoped read-only view of orders currently `delivered` or `completed`. Its functional requirements cover cards, filters, delivery performance, top products and details; the backend contract defines delivery timestamps, revenue and completion-rate calculations. [Feedback Management](../features/seller/review-management/spec.md) is planned around verified product reviews created by buyers after confirming receipt, with seller replies and admin moderation. Both screens are documentation only; their sidebar links still have no working destinations.

## Remaining gaps

Logistics courier assignment and downstream sorting/delivery actions are not yet implemented. Product deletion/bulk import, configurable inventory thresholds, shipping quotes, seller reports, reviews, messages and account-management workflows remain separate work. Completed Orders needs a reliable recorded delivery time and actual delivered orders from the logistics flow. No external carrier API is connected; shipment metadata is manually recorded from carrier details.

See [order flow](../order-logistics-flow-decisions.md) for ownership of transitions.
