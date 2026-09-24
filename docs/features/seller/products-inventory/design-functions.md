# Products & Inventory — Figma Design Functions

**Reference:** Seller `Products & Inventory` Figma screenshot supplied by the user.  
**Design status:** Hardcoded visual reference; this document records the visible functions only.  
**Implementation status:** Connected to persisted seller products and stock movements. See [implementation spec](spec.md) for the implemented action rules. Screenshot values remain reference-only.

## Page purpose

Let a seller review products, categories, and stock levels from one page. The subtitle in the design describes managing products, stock levels, and categories. The screenshot does not define the exact create/edit/delete flow, so those actions remain unspecified until a fuller design or requirement is provided.

## Visible functions

### Inventory summary

Four summary cards show total products, in-stock products, low-stock products, and out-of-stock products. Values in the screenshot (48, 42, 6, and 0) are visual sample data, not live counts or required production defaults.

### Product list

- Search products by text.
- Filter by category.
- Filter by product/stock status.
- Display product image, name, SKU/code, category, price, stock quantity, and status.
- Provide a per-row overflow/action menu; its options are not specified by this screenshot.
- Paginate the results and show the visible result range and total count. The shown count and rows are sample content and should be driven by the real result set after implementation.

### Categories panel

Show category names with an icon and product count, provide a `View all` affordance, and allow opening a category row (arrow). The screenshot does not specify whether category management includes create, rename, delete, or reorder actions.

### Low Stock Alert panel

Show low-stock products with image/name/current quantity, provide a `Restock` action for each item, and a `View all` affordance. The design does not define the restock form, stock adjustment history, threshold rule, or confirmation behavior.

### Stock status labels

The UI distinguishes `In Stock`, `Low Stock`, and `Out of Stock`. Thresholds are not stated in the screenshot and must be configured/decided before backend behavior is implemented.

## Data and behavior required when wired

- Scope every product and stock change to the authenticated, approved seller.
- Derive card totals, category counts, list rows, pagination, and alert contents from persisted seller-owned records; never hardcode the screenshot values.
- Define the low-stock threshold and whether it is global, per product, or seller-configurable.
- Validate stock adjustments as positive/authorized changes, persist the resulting quantity, and record actor, prior quantity, new quantity, reason, and timestamp in an inventory history.
- Recheck available quantity during checkout under a transaction/lock, regardless of what the seller page displayed.
- Keep search, category filter, status filter, and pagination composable and preserve them when returning from a product action.
- Show empty, loading, validation, success, and failure states for the list and restock interaction.

## Explicitly not defined by this reference

Product creation fields, edit form, delete/archive behavior, image upload rules, product approval states, bulk import/export, category CRUD, low-stock threshold, restock quantities/reasons, and the menu options are not visible or specified. Do not infer these from the screenshot; document them when the user sends the corresponding design or requirements.

## Current implementation

The design has been connected through seller product routes, live filters/counts, details/edit/restock drawers and an inventory movement ledger. The implemented row menu is View / stock history, Edit product, and Restock; Add Product submits listings for admin review. The fixed low-stock threshold is 10. See [implementation spec](spec.md) for the final rules and [Seller domain](../../../domains/Seller.md) for remaining work. Sections above describe the original screenshot and its unspecified fields, not the current implementation status.
