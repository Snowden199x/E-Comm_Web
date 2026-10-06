# Products & Inventory

**Status:** Product persistence, annual codes, variants, stock movements and Buyer checkout are implemented in code; owner migration and end-to-end verification pending.
**Updated:** 5 October 2026

## Routes and UI

- `GET /seller/products`: search name/SKU, category and stock-status filters (selects apply instantly, search after a short pause), ten-row pagination ("Showing X out of N entries"), four inventory stat cards that also act as stock filters, and a Low Stock Alert panel. The old Categories side card was removed; the Category filter replaces it. An **Add Product** button sits in the page header.
- Clicking a product row, name, or a menu entry opens a **details modal** (not a drawer): photo gallery, Details, **Variations** (only when the product has variations), Restock and Stock history tabs, Edit button. Row menu: View details, Edit product, Restock, and (below a divider) **Delete product**. The delete menu item and confirmation dialog are built, but the server route does not exist yet (see below and [backend needs](../backend-needs.md)). Direct visits to `GET /seller/products/{product}` (e.g. from notifications) render the same details inside the seller layout.
- `GET /seller/products/create`, `POST /seller/products`, and `GET /seller/products/{product}?mode=edit` + `PATCH` render one full-page form (`seller/products/form.blade.php`). Submitted products start at `for_review`; saved drafts stay private.
- `POST /seller/products/{product}/restock`: positive quantity and required reason; a unique request key prevents double-submit. When variants exist the form sends a product-owned `variant_id`, which the server validates and records.

All routes require an approved, active seller and scope records to that seller. Category selection is limited to the seller's registered categories and their children. **Delete product is UI-only for now.** The row menu opens a confirmation dialog and sends `DELETE /seller/products/{id}`; that route is not implemented, so the dialog reports that deleting is not available on the server yet. Do not treat deletion as implemented until the backend items in [backend needs](../backend-needs.md) are built.

## Add / Edit Product form (UI contract)

One page, five card sections, with a sticky **Listing progress** panel (a step is checked only when it is valid *and* applicable — Category Details is unchecked until a category is chosen).

1. **Basic Information** — Product Name*, Category*, Subcategory* (required when the category has subcategories; options come from the seller's approved children), Brand (+ "No Brand"), Condition* (New/Used), Description* (≤ 5000), Product Images* (1–6 photos, JPG/PNG/WebP, ≤ 2 MB each, ≤ 10 MB combined per save, preview, remove, set as main — first photo is main), optional one video (MP4/WebM/MOV, ≤ 10 MB, ≤ 60 s).
2. **Category Details** — fields change with the selected category, defined in `config/product-attributes.php` (15 schemas). A subcategory with its own schema overrides its parent's (e.g. Health and Beauty › Makeup & Cosmetics). Only universally applicable fields are required (e.g. Clothing Type, Pet Type + Product Type, Food Type + Expiration Date); everything else may stay blank. Expiration date, when given, must be in the future. **Additional Specifications**: free name/value rows (add, edit, remove).
3. **Variations, Price & Stock** — "Does this product have variations?" No → Price*, Stock*, generated SKU display. Yes → up to 3 free-named variation types (≤ 20 options each, quick-add hints per category); all combinations are generated (≤ 100) with Price*, Stock*, generated SKU display, optional image per variant, bulk apply, and a total-stock line. Seller SKU fields are read-only; the server rejects supplied SKU values. An optional original price can be displayed crossed out when higher than the selling price. Stock is per variant; product stock = sum of variants.
4. **Shipping Information** — Package weight* (kg), Package length*, width*, height* (cm), Fragile (Yes/No). Package size is separate from the product's own dimensions.
5. **Review & Submit** — live summary (main + other images, name, category › subcategory, brand, condition, description, category details, specifications, variations, price, stock, shipping). Actions: **Save as Draft** (name only required; private, not sent to admin) and **Submit for Review** (full validation → `for_review`, hidden from buyers until approved). Validation errors appear under each field and the form is never cleared.

A same-tab reload restores unfinished Add/Edit Product values for up to two hours, including category details, specifications and partial variation entries. A small side notice appears only on the first reload that restores the draft; returning after login restores it silently. Existing image order/removals return on edit; new media uploads must be selected again. This browser copy is cleared only after a successful product save. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

Review workflow: Draft → Pending Review (`for_review`) → Admin Product Review → Approved (published) / Warned (needs changes, seller edits and resubmits) / Rejected (reason visible to seller).

## Backend implementation (4 October 2026)

The new migration `2026_10_04_000001_expand_products_for_variants.php` adds the `draft` status, annual `product_sequences` counter, product condition/video/shipping fields, `compare_at_price`, attribute/specification tables, variation type/option/variant tables, and variant references on cart, order items and inventory movements. Run `php artisan migrate` before opening these screens. Existing ULID product codes remain unchanged. New codes restart each year as `PRD-YYYY-0001`; variant SKUs are generated as `{product_code}-01`, etc. The backend reserves codes when saving a draft too, because drafts are database products, but the Seller UI shows the codes only after Submit for Review. No Seller request can choose or change a SKU.

Seller submission validates seller-approved category/subcategory, schema-required attributes from `config/product-attributes.php`, expiration dates, dimensions, image type/size/count, variant combinations, price and stock. It stores photos and video on the public product-media disk. Photos are limited to 2 MB each and 10 MB combined per request; video is limited to 10 MB. Each submitted product and resubmission creates an Admin platform notification; saving a draft does not. When the form contains files, it submits with XMLHttpRequest and shows browser-reported request upload progress directly below the video preview; at 100%, the UI says it is waiting for Vendo to save the product. A PHP “failed to upload” validation error explains that PHP/web-server request limits may be rejecting the file. The indicator cannot make a server accept a request it rejects. The form now offers Save as Draft. A name-only draft is accepted; the form omits incomplete variation rows when saving a draft, so those partial rows must be re-entered later. Category attribute definitions remain in config; `category_attributes` is not a database table. The server limits video type/size; the 60-second duration is checked in the browser only. Local PHP settings must allow at least a 10 MB file and a request containing up to 10 MB of photos plus a 10 MB video and form overhead (for example, `upload_max_filesize=12M`, `post_max_size=32M`). Production storage setup remains a separate deployment task.

The product detail modal reads stored variants and accepts variant-specific restocks. `InventoryService` writes variant movements while keeping `products.stock` equal to the variant stock sum. Checkout locks both product and variant rows, records the chosen variant and variant price on order items, then deducts the selected variant's stock. Seller cancellation restores it. Buyer product detail offers variant selection; product cards link to that page for variants. Existing variant combinations, SKUs, and stock are locked on Seller edit; prices can change and stock changes use Restock. A simple product may be converted to variants only with zero stock and no order items. This protects existing carts and order history.

Existing product photos can be made the main image by ID/order on edit. Admin Product Review loads and displays condition, category details, specifications, variants, video, package data and original price; submitting or resubmitting notifies Admin and links to the filtered review queue. Its action endpoints accept only submitted `for_review` listings, so drafts cannot be approved through direct POSTs. Owner testing and Vite compilation have not been run.

### Remaining work

- Add server-side video-duration inspection if production requires a strict 60-second limit.
- Persist incomplete variation drafts without requiring complete rows; the current name-only draft flow does not preserve partial variant configuration.
- Decide whether category schemas should move from `config/product-attributes.php` into a managed `category_attributes` table.
- Review historical stock movement display for products converted from simple stock to variants. Such conversion is currently limited to zero-stock products with no orders.

## Product details modal (5 October 2026 UI update)

- **Details tab:** name, category pills, price (a range when variants have different prices, with the original price crossed out when higher), stock, brand, condition, only the legacy fields that hold a value (material, weight, country of origin), one row per **variation type** (for example Color: black, red · Size: small, medium, large) instead of the old empty Colors/Sizes rows, date added, category details, additional specifications, shipping information (weight, package size, fragile) and the product video.
- **Variations tab:** summary cards (variation types, combinations, total stock, out of stock), the types with their options as chips, and one row per variant with image, name, option text, SKU, price, stock, status pill and a **Restock** shortcut that opens the Restock tab with that variant preselected. Variant rows are a grid list rather than a table so long names and SKUs cannot overlap. A product marked as having variations but with no stored variants shows an explanatory empty state. SKUs read "assigned after submission" for drafts.
- Direct visits to `GET /seller/products/{product}` now have working tabs too.
- Reads only data `ProductController::show` already eager-loads (`attributeValues`, `specifications`, `variationTypes.options`, `variants`); no backend change.

## Delete product (UI built, backend pending)

Intended behavior, as requested by the owner: the Seller deletes their own product from the row's 3-dot menu; the product disappears from the Seller's system; **Admin is notified**. The Admin side is deliberately **not** built; it is recorded in [backend needs](../backend-needs.md).

Built now: the menu item, an accessible confirmation dialog (focus returns to the menu button, Esc/backdrop closes), a loading state, error messages (including 404/405 "route not available yet" and 419 session expiry), an animated row removal and a success toast through the existing `sellerOperationMessage` mechanism.

Not built: route, controller action, soft delete, open-order blocking rule, cart cleanup, Admin notification. Product deletion is only safe as a soft delete because `order_items.product_id` and `product_reviews.product_id` restrict hard deletes.

## Stock rules

`Product::LOW_STOCK_THRESHOLD` is 10: out of stock = 0, low stock = 1–10, in stock = above 10 (mutually exclusive). Status colors: In Stock `#15803D`/`#DCFCE7`, Low Stock `#2563EB`/`#DBEAFE`, Out of Stock `#8D0000`/`#FFD3D3`. Category pills use `Category::colors` of the **main** category, with the subcategory name shown beneath. New stock, restocks, checkout deductions and cancellations create `inventory_movements`. Restock/checkout/cancellation use transactions and row locks. When variants are introduced, movements and restocks become per-variant and `products.stock` must equal the variant sum.

## Files

`Seller/ProductController`, `Product`, `Category`, `InventoryMovement`, `InventoryService`; views `resources/views/seller/products/{index,form,detail}.blade.php` and `partials/{detail-body,pagination,thumb}.blade.php`; `resources/css/seller/products.css` (original rules unchanged; the 5 October additions are appended at the end), `resources/js/seller/products.js` (the older `operations.css/js` remain for other seller pages); `config/product-attributes.php`; icons in `public/assets/icons/seller/`.

[Backend needs](../backend-needs.md) · [Role workspace redesign and backend gaps](../../../design/2026-10-04-role-workspace-redesign.md) · [Design functions](design-functions.md) · [Inventory](../inventory/spec.md) · [Shipments](../shipments/spec.md)