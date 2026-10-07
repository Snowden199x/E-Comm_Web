# Data Schema Overview

This overview is derived from Eloquent models and migrations, not a replacement for the migrations. Field-level details should be confirmed from `database/migrations/` before changing the schema.

## Identity and profiles

- `users`: credentials, role, registration/account states, contact data, suspension/rejection metadata, profile picture, and archival fields.
- `buyer_details`, `seller_details`, `courier_details`, `logistics_centers`: one-to-one role profile records. Courier detail optionally belongs to a logistics center.
- `buyer_saved_items` stores unique Buyer/product pairs; `buyer_settings` stores one row per Buyer for text size, reduced motion, and notification sound. Both are added by `2026_10_07_100000_create_buyer_saved_items_and_settings.php` and require owner migration.
- `categories` is hierarchical through nullable `parent_id`; `seller_categories` links sellers to selected categories.
- `otp_verifications` stores buyer registration OTP records. Seller and logistics-center OTP verification currently uses cache keys instead.

## Commerce

- `products` belongs to a seller and optionally to a category; product images, warnings, and violations are separate records. Product approval state is stored on the product.
- `cart_items` belongs to a buyer and a product; optional variation/color/size fields are carried to order items.
- `orders` belongs to buyer and seller, optionally references a courier, and stores total, payment mode, shipping address, and workflow status. One buyer checkout can create separate orders for each seller.
- `order_items` snapshots product reference, quantity, selected variation, and price.
- `order_status_events` records status changes, actor, optional note, and event time; it is the basis for the order timeline.
- `orders.logistics_center_id` is the origin center; `destination_logistics_center_id` is the destination hub. `courier_id` and `delivery_courier_id` hold pickup and delivery riders; `linehaul_rider_id` holds the origin hub's assigned Truck Rider for a configured cross-hub route. Structured shipping province/city codes and names are snapshotted at checkout; legacy rows remain null. `order_logistics_assignments` records pickup, linehaul, and delivery rider assignments; status transitions remain in `order_status_events`. Additive migration `2026_09_28_200000_add_linehaul_rider_to_orders.php` adds the nullable FK and status lookup index; owner runs it.
- `logistics_route_checkpoints` stores a virtual SH code and nearby locality name (for example SH3 / Pagsanjan), with municipality-level representative codes/coordinates and an inactive-by-default flag. It has no logistics-center owner or street address. The route migration inserts inactive examples SH5/Muntinlupa, SH6/Calamba, and SH3/Pagsanjan. `logistics_route_plans` connects one origin Main Hub to one destination Main Hub; `logistics_route_plan_stops` stores the plan's ordered checkpoint sequence. Plans are inactive by default and are selected only for the order's exact Main Hub pair. `orders.route_plan_id`, `route_step`, and `next_route_checkpoint_id` save the chosen plan and next planned locality; they do not mean the parcel arrived or custody changed. The earlier `logistics_sub_hubs` and `orders.next_sub_hub_id` physical-site draft may remain in databases for migration compatibility but are no longer used by application code. Migration `2026_09_28_190000_add_virtual_logistics_route_plans.php` copies legacy checkpoint labels into the virtual registry as inactive entries when present.
- `personal_access_tokens` holds expiring Sanctum rider bearer tokens. `order_scan_events` records the assigned rider, verified center, scan type, from/to statuses, unique per-order retry UUID, and server time. Both tables use additive 26 September migrations that the owner must run.

## Platform operations

- `notifications` may be user-targeted or platform-level. `announcements` support publication/audience/scheduling; `platform_policies` store policy content/versioning.
- `conversations`, `messages`, and `message_attachments` support messaging and optional complaint linkage. Case-linked conversations now hold private Buyer/Seller and Admin text threads; ordinary support threads have `complaint_id = null`. Conversations can be open/closed.
- `complaints`, `complaint_evidences`, `complaint_activities` represent dispute intake, attachments, and status history.
- `commission_settings` stores the global commission rate (10.00% default); Admin commission screens calculate the product commission from order items. `categories.commission_rate` also exists but is not currently used. No immutable Admin rate snapshot, shipping quote, Logistics/Rider allocation, or settlement ledger exists.
- `product_warnings` and `product_violations` record compliance actions. `admin_login_sessions` records admin login/device/session information.

## Important integrity notes

- Current checkout decrements product stock when placing an order, inside a transaction with locked product rows.
- Seller decline restores reserved stock under a transaction. Any additional cancel/refund path must be idempotent and audited to prevent duplicate restoration.
- The order workflow migration changes status from the original limited enum to a wider string to accommodate ERP stages. Validate all status writes against `Order::STATUSES`.
- Foreign-key cascade/null behavior is defined in migration files; inspect it before deleting or archiving records.

## Seller operations additions (24 September 2026)

- `inventory_movements`: product, nullable actor/order references, type, signed quantity, before/after stock, reason, unique optional request key and timestamps. Opening stock/restock/checkout/cancellation writes are transactional. Legacy balances are preserved; historical movements are not fabricated.
- `orders`: unique stable `tracking_number`, nullable carrier name/reference and ETA range, `shipping_fee` default zero. Legacy references are backfilled by migration; new references are generated on order creation. ERP order status remains the only fulfillment status source.

## 4 October 2026 additions (pending owner migration)

- `product_sequences` holds a locked annual counter for new `PRD-YYYY-NNNN` codes; existing ULID codes are retained. `products.status` adds `draft`, and product rows add condition, video path, packed weight/size, fragile flag, has-variations flag and optional compare-at price.
- `product_attribute_values`, `product_specifications`, `product_variation_types`, `product_variation_options`, and `product_variants` store Seller listing details and purchasable options. Category field definitions remain in `config/product-attributes.php`; each variant stores its selected type/value map as JSON.
- `cart_items.product_variant_id`, `order_items.product_variant_id` and `inventory_movements.product_variant_id` tie selection, purchase and stock movement to the selected variant. Product aggregate stock equals the sum of variant stocks for variant products.
- `orders.pickup_request_status`, `pickup_decline_reason` and `pickup_verified_at` record Main Hub review of a Seller pickup request. A decline returns the order to `preparing` and lets the Seller mark it ready again.
- `admin_action_logs` records actor Admin ID, target user ID, action, reason and timestamps for registration/account actions. These tables/columns are added by three new `2026_10_04_*` migrations.
