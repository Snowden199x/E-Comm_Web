# Products & Inventory

**Status:** List, details modal and Add/Edit Product **UI redesigned (3 Oct 2026)**. Backend for drafts, variations, category details, specifications, video, package size and `PRD-YYYY-NNNN` IDs is **not implemented yet** — see [redesign backend needs](redesign-backend-needs.md). Everything under "Implemented today" still works.  
**Updated:** 3 October 2026

## Routes and UI

- `GET /seller/products`: search name/SKU, category and stock-status filters (selects apply instantly, search after a short pause), ten-row pagination ("Showing X out of N entries"), four inventory stat cards that also act as stock filters, and a Low Stock Alert panel. The old Categories side card was removed; the Category filter replaces it. An **Add Product** button sits in the page header.
- Clicking a product row, name, or a menu entry opens a **details modal** (not a drawer): photo gallery, Details, Restock and Stock history tabs, Edit button. Row menu: View details, Edit product, Restock. Direct visits to `GET /seller/products/{product}` (e.g. from notifications) render the same details inside the seller layout.
- `GET /seller/products/create`, `POST /seller/products`, and `GET /seller/products/{product}?mode=edit` + `PATCH` render one full-page form (`seller/products/form.blade.php`). Status starts at `for_review`.
- `POST /seller/products/{product}/restock`: positive quantity and required reason; a unique request key prevents double-submit. When variants exist the form also sends `variant_id` (backend pending).

All routes require an approved, active seller and scope records to that seller. Category selection is limited to the seller's registered categories and their children. There is no destructive product-delete action.

## Add / Edit Product form (UI contract)

One page, five card sections, with a sticky **Listing progress** panel (a step is checked only when it is valid *and* applicable — Category Details is unchecked until a category is chosen).

1. **Basic Information** — Product Name*, Category*, Subcategory* (required when the category has subcategories; options come from the seller's approved children), Brand (+ "No Brand"), Condition* (New/Used), Description* (≤ 5000), Product Images* (1–6 photos, JPG/PNG/WebP, ≤ 2 MB each, ≤ 7 MB new per save, preview, remove, set as main — first photo is main), optional one video (MP4/WebM/MOV, ≤ 30 MB, ≤ 60 s).
2. **Category Details** — fields change with the selected category, defined in `config/product-attributes.php` (15 schemas). A subcategory with its own schema overrides its parent's (e.g. Health and Beauty › Makeup & Cosmetics). Only universally applicable fields are required (e.g. Clothing Type, Pet Type + Product Type, Food Type + Expiration Date); everything else may stay blank. Expiration date, when given, must be in the future. **Additional Specifications**: free name/value rows (add, edit, remove).
3. **Variations, Price & Stock** — "Does this product have variations?" No → Price*, Stock*, SKU (auto). Yes → up to 3 free-named variation types (≤ 20 options each, quick-add hints per category); all combinations are generated (≤ 100) with Price*, Stock*, SKU, optional image per variant, bulk apply, and a total-stock line. Stock is per variant; product stock = sum of variants.
4. **Shipping Information** — Package weight* (kg), Package length*, width*, height* (cm), Fragile (Yes/No). Package size is separate from the product's own dimensions.
5. **Review & Submit** — live summary (main + other images, name, category › subcategory, brand, condition, description, category details, specifications, variations, price, stock, shipping). Actions: **Save as Draft** (name only required; private, not sent to admin) and **Submit for Review** (full validation → `for_review`, hidden from buyers until approved). Validation errors appear under each field and the form is never cleared.

Review workflow: Draft → Pending Review (`for_review`) → Admin Product Review → Approved (published) / Warned (needs changes, seller edits and resubmits) / Rejected (reason visible to seller).

## Implemented today (unchanged backend)

Simple products only: name, description, category, brand, photos, price, opening stock, weight. The form sends extra fields that the current `store()` ignores (condition, video, `attributes[]`, `specs[]`, variations, package size, fragile, `action`). `LEGACY-COMPAT` lines in `products.js` fill the `weight` string and `material` that `store()` still requires. `Save as Draft` is hidden while `$backendReady = false` in `form.blade.php`. Existing photo can't yet be set as main on edit; backend only replaces main via upload.

## Product ID / SKU (planned)

Format `PRD-{year}-{0001}`, sequential and gap-tolerant, starting at 0001, generated server-side with a locked counter. Current code still uses `PRD-` + ULID.

## Stock rules

`Product::LOW_STOCK_THRESHOLD` is 10: out of stock = 0, low stock = 1–10, in stock = above 10 (mutually exclusive). Status colors: In Stock `#15803D`/`#DCFCE7`, Low Stock `#2563EB`/`#DBEAFE`, Out of Stock `#8D0000`/`#FFD3D3`. Category pills use `Category::colors` of the **main** category, with the subcategory name shown beneath. New stock, restocks, checkout deductions and cancellations create `inventory_movements`. Restock/checkout/cancellation use transactions and row locks. When variants are introduced, movements and restocks become per-variant and `products.stock` must equal the variant sum.

## Files

`Seller/ProductController`, `Product`, `Category`, `InventoryMovement`, `InventoryService`; views `resources/views/seller/products/{index,form,detail}.blade.php` and `partials/{detail-body,pagination,thumb}.blade.php`; `resources/css/seller/products.css`, `resources/js/seller/products.js` (the older `operations.css/js` remain for other seller pages); `config/product-attributes.php`; icons in `public/assets/icons/seller/`.

[Redesign backend needs](redesign-backend-needs.md) · [Design functions](design-functions.md) · [Inventory](../inventory/spec.md) · [Shipments](../shipments/spec.md)