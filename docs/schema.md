# Data Schema Overview

This overview is derived from Eloquent models and migrations, not a replacement for the migrations. Field-level details should be confirmed from `database/migrations/` before changing the schema.

## Identity and profiles

- `users`: credentials, role, registration/account states, contact data, suspension/rejection metadata, profile picture, and archival fields.
- `buyer_details`, `seller_details`, `courier_details`, `logistics_centers`: one-to-one role profile records. Courier detail optionally belongs to a logistics center.
- `buyer_saved_items` stores unique Buyer/product pairs; `buyer_settings` stores one row per Buyer for text size, reduced motion, and notification sound. Both are added by `2026_10_07_100000_create_buyer_saved_items_and_settings.php` and require owner migration.
- `categories` is hierarchical through nullable `parent_id`; `seller_categories` links sellers to selected categories.
- `otp_verifications` stores buyer registration OTP records. Seller and logistics-center OTP verification currently uses cache keys instead.

## Logistics province network and facilities

Additive migration `2026_10_14_120000_create_logistics_facilities.php` adds unique nullable `logistics_centers.coverage_province_code`, its province name, and `logistics_facilities`. Every Logistics center has one Main Hub facility; `main_hub_owner_id` is unique for Main Hub rows, while stations are owned by the same center and require an actual operating address. Pending/approved accounts reserve province coverage, and Admin rejection releases the code. Backfill claims a province only when one eligible legacy account maps to its PSGC code. Duplicate legacy province accounts remain without a claim for Admin resolution; old users, centers, Rider links, orders, and scan history are preserved. New Seller/Rider routing uses the approved active center's province claim rather than nearest municipality.

Migration `2026_10_14_121000_create_order_facility_scans.php` adds ordered `order_facility_stops` with arrival/sort/departure timestamps and `facility_scan_events` with a globally unique retry key and one accepted action per parcel/facility. New sorts choose actual facility IDs, placing the destination Main Hub between origin and destination stations on cross-province routes. Old checkpoint and SH scanner tables remain historical virtual-checkpoint data; map an old checkpoint to a real facility only after operator confirmation. See [province network and stations](features/logistics/province-network-and-stations/spec.md).

## Commerce

- `products` belongs to a seller and optionally to a category; product images, warnings, and violations are separate records. Product approval state is stored on the product.
- `cart_items` belongs to a buyer and a product; optional variation/color/size fields are carried to order items.
- `orders` belongs to buyer and seller, optionally references a courier, and stores gross product subtotal (`total_amount`), nullable `buyer_note`, Seller `voucher_code`/`voucher_discount` snapshots, shipping fee, payment mode, shipping address, and workflow status. Buyer payable total is subtotal minus voucher discount plus shipping. One buyer checkout can create separate orders for each seller.
- `order_items` snapshots product reference, quantity, selected variation, and price.
- `order_status_events` records status changes, actor, optional note, and event time; it is the basis for the order timeline.
- `orders.logistics_center_id` is the origin center; `destination_logistics_center_id` is the destination hub. `courier_id` and `delivery_courier_id` hold pickup and delivery riders; `linehaul_rider_id` holds the origin hub's assigned Truck Rider for a configured cross-hub route. Structured shipping province/city codes and names are snapshotted at checkout; legacy rows remain null. `order_logistics_assignments` records pickup, linehaul, and delivery rider assignments; status transitions remain in `order_status_events`. Additive migration `2026_09_28_200000_add_linehaul_rider_to_orders.php` adds the nullable FK and status lookup index; owner runs it.
- Legacy `logistics_route_checkpoints` stores a virtual SH code and nearby locality name (for example SH3 / Pagsanjan), with municipality-level representative codes/coordinates and an inactive-by-default flag. It has no Logistics Center owner or street address. The route migration inserts inactive examples SH5/Muntinlupa, SH6/Calamba, and SH3/Pagsanjan. Legacy `logistics_route_plans` connects origin and destination Main Hubs; `logistics_route_plan_stops` stores ordered virtual checkpoint IDs. `orders.route_plan_id`, `route_step`, and `next_route_checkpoint_id` save a selected plan and next planned locality; they do not mean the parcel arrived or custody changed. The earlier `logistics_sub_hubs` and `orders.next_sub_hub_id` physical-site draft may remain for migration compatibility but are no longer used by current application code. Preserve all these rows during future facility migration.
- Legacy `sh_scanner_stations` belongs to one Logistics Main Hub and one virtual SH checkpoint. It holds station name, hashed five-minute pairing code, hashed expiring device token, pairing time, and revocation time; no operator public account is created. `sh_checkpoint_scan_events` records the accepted station/order/checkpoint/route plan/step and unique scan UUID. These records are historical legacy data, not the agreed future browser-camera/facility workflow. Migration `2026_10_12_130000_create_sh_scanner_stations_and_events.php` must not be dropped or repurposed destructively.
- `personal_access_tokens` holds expiring Sanctum rider bearer tokens. `order_scan_events` records the assigned rider, verified center, scan type, from/to statuses, unique per-order retry UUID, and server time. Both tables use additive 26 September migrations that the owner must run.

## Platform operations

- `notifications` may be user-targeted or platform-level. `announcements` support publication/audience/scheduling; `platform_policies` store policy content/versioning.
- `conversations`, `messages`, and `message_attachments` support messaging and optional complaint linkage. Case-linked conversations now hold private Buyer/Seller and Admin text threads; ordinary support threads have `complaint_id = null`. Conversations can be open/closed.
- `complaints`, `complaint_evidences`, `complaint_activities` represent dispute intake, attachments, and status history.
- `commission_settings` retains a legacy rate row, but `CommissionSetting::currentRate()` now always returns a fixed 10.00%; Admin product commission screens calculate from gross order items. `categories.commission_rate` also exists but is not used. No immutable Admin rate snapshot, shipping quote, Logistics/Rider allocation, or settlement ledger exists.
- `seller_category_requests` and `seller_category_request_categories` store Seller category applications, private permit paths, Admin decisions and reviewer IDs. Approved requests attach categories through the existing `seller_categories` pivot.
- `seller_vouchers`, `seller_voucher_products`, `seller_voucher_categories`, and `seller_voucher_redemptions` store Seller-scoped offers, applicability, usage limits, and one redemption per order. Retiring a voucher sets `deleted_at` instead of deleting historical redemptions; cancellation sets `voided_at` and releases `used_count`. The 13 October migration adds first-activation and expiration-notification timestamps plus `seller_voucher_notification_recipients`, uniquely keyed by voucher and Buyer with a link to the generated notification.
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
