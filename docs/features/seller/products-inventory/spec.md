# Products & Inventory

**Status:** Implemented with persisted seller-owned products and stock history.  
**Updated:** 24 September 2026

## Routes and UI

- `GET /seller/products`: search name/SKU, category and stock-status filters, ten-row pagination, inventory cards, category counts, and low-stock alerts.
- `GET /seller/products/create`, `POST /seller/products`: product form and creation, optional image, selected selling category, opening stock; status starts at `for_review` for existing admin product review.
- `GET /seller/products/{product}`: details and paginated stock history; `mode=edit` or `mode=restock` opens the corresponding form.
- `PATCH /seller/products/{product}`: update listing details using a revision token; changed listings return to admin review. Stock cannot be overwritten in this endpoint.
- `POST /seller/products/{product}/restock`: positive quantity and required reason; a unique request key prevents retry/double-submit stock duplication.

All routes require an approved, active seller and scope records to that seller. Product/category counts include all seller listings, including listings awaiting review. Category selection is limited to the seller's registered categories and their children. Category View all opens the complete list; selecting a row filters the product list. Product row actions open details/history, edit, or restock. There is no destructive product-delete action.

## Stock rules

`Product::LOW_STOCK_THRESHOLD` is 10: out of stock = 0, low stock = 1–10, in stock = above 10. These buckets are mutually exclusive. The seller dashboard uses the same threshold. New opening stock, restocks, checkout deductions, and seller cancellations create `inventory_movements` with quantity, before/after balances, actor, optional order, and reason. Prior stock movements are not invented for legacy products.

Restock, checkout, and cancellation use transactions/product row locks. Checkout rejects products awaiting review and reads prices from locked product rows. Product-edit revision tokens reject stale form submissions.

## Files and checks

`Seller/ProductController`, `Product`, `Category`, `InventoryMovement`, `InventoryService`, `resources/views/seller/products/`, and `resources/js/seller/operations.js`. Manual acceptance will be performed by the user; no test files are included in this change.

[Design reference](design-functions.md) · [Inventory](../inventory/spec.md) · [Shipments](../shipments/spec.md)
