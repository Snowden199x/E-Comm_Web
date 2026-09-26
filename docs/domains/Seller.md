# Seller Domain

## Purpose

Sellers register their identity and business, select categories, await admin approval, then review orders and prepare goods for logistics pickup.

## Implemented scope

Seller registration is OTP-gated and stores seller detail, identity/permit paths, and selected categories. Dashboard data is seller-scoped and includes orders, delivered sales, recent orders, top products, low/out-of-stock alerts, notifications, announcements, and an unavailable rating state until reviews exist. Orders supports ERP status tabs, search/date filters, order details/history, origin-logistics-branded shipping label printing, accepting or declining, preparing, and ready-for-pickup. Pickup is recorded by the assigned rider's scan, not a seller action.

## Products, inventory and shipments

[Products & Inventory](../features/seller/products-inventory/spec.md) is connected to seller product records, admin review, stock/status filters, categories, audited restocks and stock history. [Shipments](../features/seller/shipments/spec.md) reads the same ERP orders as Orders, with tracking metadata, real summaries, customer/item details and pre-pickup cancellation. Seller actions stop at ready for pickup; the versioned rider scan backend updates the seller notification and timeline, while the mobile client remains future work.

## Delivered orders and feedback

[Delivered Orders](../features/seller/completed-orders/spec.md) is a seller-scoped read-only view of orders currently `delivered` or `completed`, with delivery metrics, filters, top products and order details. [Feedback Management](../features/seller/review-management/spec.md) handles verified product reviews created by buyers after confirming receipt, seller replies and admin moderation.

## Seller workspace

- [Reports](../features/seller/generate-report/spec.md) shows seller-scoped delivery-period summaries with matching preview and PDF download for today, this week, this month and this year.
- [Messages](../features/seller/chat-messaging/spec.md) provides a Vendo Support inbox. Customer conversations still require a buyer/seller participant model and a buyer companion flow.
- [Account Management](../features/seller/account-management/spec.md) lets sellers update contact details, a shop description, profile photo, banner and password. Approved business details remain read-only.
- [Notifications](../features/seller/notification/spec.md) provides the bell, unread state and inbox for seller activity.
- The shared seller header remains visible while scrolling. Its bell and message shortcut open the corresponding seller pages.

These pages and routes have been added; seller-side manual verification is still pending.

## Remaining gaps

Logistics rider assignment, manual sorting and hub handoffs are implemented. Delivery completion, proof and exceptions are not. Product deletion/bulk import, configurable inventory thresholds, shipping quotes and buyer-to-seller messaging remain separate work. Delivered Orders uses recorded delivery timestamps; only orders with an actual `delivered` or `completed` status appear. No external carrier API is connected; shipment metadata is manually recorded from carrier details.

See [order flow](../order-logistics-flow-decisions.md) for ownership of transitions.
