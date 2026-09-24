# Seller Domain

## Purpose

Sellers register their identity and business, select categories, await admin approval, then review orders and prepare goods for logistics pickup.

## Implemented scope

Seller registration is OTP-gated and stores seller detail, identity/permit paths, and selected categories. Dashboard data is seller-scoped and includes orders, delivered sales, recent orders, top products, low/out-of-stock alerts, notifications, announcements, and an unavailable rating state until reviews exist. Orders supports ERP status tabs, search/date filters, order details/history/waybill, accepting or declining, preparing, ready-for-pickup, and confirming a pickup after a courier is assigned.

## Boundaries and gaps

Seller access requires authentication and an approved/active account. Product/inventory navigation, shipment assignment, seller reports, reviews, messages, and account-management destinations are not all backed by current seller routes. The courier-assignment prerequisite has no assignment workflow in this repository.

See `features/seller/` and [order flow](../order-logistics-flow-decisions.md).
