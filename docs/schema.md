# Data Schema Overview

This overview is derived from Eloquent models and migrations, not a replacement for the migrations. Field-level details should be confirmed from `database/migrations/` before changing the schema.

## Identity and profiles

- `users`: credentials, role, registration/account states, contact data, suspension/rejection metadata, profile picture, and archival fields.
- `buyer_details`, `seller_details`, `courier_details`, `logistics_centers`: one-to-one role profile records. Courier detail optionally belongs to a logistics center.
- `categories` is hierarchical through nullable `parent_id`; `seller_categories` links sellers to selected categories.
- `otp_verifications` stores buyer registration OTP records. Seller and logistics-center OTP verification currently uses cache keys instead.

## Commerce

- `products` belongs to a seller and optionally to a category; product images, warnings, and violations are separate records. Product approval state is stored on the product.
- `cart_items` belongs to a buyer and a product; optional variation/color/size fields are carried to order items.
- `orders` belongs to buyer and seller, optionally references a courier, and stores total, payment mode, shipping address, and workflow status. One buyer checkout can create separate orders for each seller.
- `order_items` snapshots product reference, quantity, selected variation, and price.
- `order_status_events` records status changes, actor, optional note, and event time; it is the basis for the order timeline.

## Platform operations

- `notifications` may be user-targeted or platform-level. `announcements` support publication/audience/scheduling; `platform_policies` store policy content/versioning.
- `conversations`, `messages`, and `message_attachments` support messaging and optional complaint linkage. Conversations can be open/closed.
- `complaints`, `complaint_evidences`, `complaint_activities` represent dispute intake, attachments, and status history.
- `commission_settings` stores category commission configuration; seller commission screens aggregate order data.
- `product_warnings` and `product_violations` record compliance actions. `admin_login_sessions` records admin login/device/session information.

## Important integrity notes

- Current checkout decrements product stock when placing an order, inside a transaction with locked product rows.
- Seller decline restores reserved stock under a transaction. Any additional cancel/refund path must be idempotent and audited to prevent duplicate restoration.
- The order workflow migration changes status from the original limited enum to a wider string to accommodate ERP stages. Validate all status writes against `Order::STATUSES`.
- Foreign-key cascade/null behavior is defined in migration files; inspect it before deleting or archiving records.

## Seller operations additions (24 September 2026)

- `inventory_movements`: product, nullable actor/order references, type, signed quantity, before/after stock, reason, unique optional request key and timestamps. Opening stock/restock/checkout/cancellation writes are transactional. Legacy balances are preserved; historical movements are not fabricated.
- `orders`: unique stable `tracking_number`, nullable carrier name/reference and ETA range, `shipping_fee` default zero. Legacy references are backfilled by migration; new references are generated on order creation. ERP order status remains the only fulfillment status source.
