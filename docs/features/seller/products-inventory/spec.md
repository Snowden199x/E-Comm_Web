# Products & Inventory

**Status:** Implemented with persisted seller-owned products and stock history.  
**Updated:** 24 September 2026

## Routes and UI

- `GET /seller/products`: search name/SKU, category and stock-status filters, ten-row pagination, inventory cards, category counts, and low-stock alerts.
- `GET /seller/products/create`, `POST /seller/products`: product form and creation with main photo, up to five gallery photos, material, weight, Philippines origin, selectable sizes/colors, selected selling category, and opening stock; status starts at `for_review` for existing admin product review.
- `GET /seller/products/{product}`: details and paginated stock history; `mode=edit` or `mode=restock` opens the corresponding form.
- `PATCH /seller/products/{product}`: update listing details using a revision token; changed listings return to admin review. Stock cannot be overwritten in this endpoint.
- `POST /seller/products/{product}/restock`: positive quantity and required reason; a unique request key prevents retry/double-submit stock duplication.

All routes require an approved, active seller and scope records to that seller. Product/category counts include all seller listings, including listings awaiting review. Category selection is limited to the seller's registered categories and their children. Category View all opens the complete list; selecting a row filters the product list. Product row actions open details/history, edit, or restock. There is no destructive product-delete action.

The product form offers XS–XXL multi-select sizes and a searchable color picker with up to ten selected colors, including a custom named color. Material and weight are required. Weight accepts a value with `g` or `kg`; origin is assigned as `Philippines` by the server. The main photo is required for new listings. Seller may upload up to five extra photos, two megabytes per file and seven megabytes combined per save. On edit, seller can replace the main photo, add gallery photos, and remove old ones while retaining at least one and at most six total. Image changes re-enter admin review. Buyer product details show the gallery and require selection of offered color/size before adding to cart. Product photos are stored in the existing `product_images` table in display order.

## Stock rules

`Product::LOW_STOCK_THRESHOLD` is 10: out of stock = 0, low stock = 1–10, in stock = above 10. These buckets are mutually exclusive. The seller dashboard uses the same threshold. New opening stock, restocks, checkout deductions, and seller cancellations create `inventory_movements` with quantity, before/after balances, actor, optional order, and reason. Prior stock movements are not invented for legacy products.

Restock, checkout, and cancellation use transactions/product row locks. Checkout rejects products awaiting review and reads prices from locked product rows. Product-edit revision tokens reject stale form submissions.

## Files

`Seller/ProductController`, `Product`, `Category`, `InventoryMovement`, `InventoryService`, `resources/views/seller/products/`, `resources/js/seller/operations.js`, and the buyer product/cart files.

[Inventory](../inventory/spec.md) · [Shipments](../shipments/spec.md)
