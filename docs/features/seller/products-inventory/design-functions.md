# Products & Inventory — Figma Design Functions

**Reference:** Seller `Products & Inventory` Figma screenshot supplied by the user.  
**Design status:** Hardcoded visual reference; this document records the visible functions only.  
**Implementation status:** Connected to persisted seller products and stock movements. See [implementation spec](spec.md) for the implemented action rules. Screenshot values remain reference-only.

## Page purpose

Let a seller review products, categories, and stock levels from one page. The subtitle in the design describes managing products, stock levels, and categories. The screenshot does not define the exact create/edit/delete flow, so those actions remain unspecified until a fuller design or requirement is provided.
# Products & Inventory — Design Functions

**Reference:** Seller `Products & Inventory` mockup, status-color sheet and category-palette sheet supplied by the owner (3 Oct 2026), plus the written Add Product requirements. Replaces the earlier screenshot reference.  
**Implementation status:** List, modal and Add Product UI built; see [implementation spec](spec.md). Sample values in the mockup (48/42/6/0, 521 entries, product names) are reference-only.

## Page layout

Header (title "Products & Inventory", subtitle "Manage your products, stock levels and categories.", Add Product button) → four stat cards → filter row → table card (left) with Low Stock Alert card (right). The mockup has no Categories card.

## Visible functions

- **Stat cards:** Total Products, In Stock, Low Stock, Out of Stock, each with its own icon; clicking one filters the list.
- **Filters:** search (name/SKU), Categories dropdown, All status dropdown.
- **Table:** Product (image, name, `SKU: …`), Category pill, Price, Stock, Status pill, row menu (⋮). Pending-review/needs-changes/rejected/draft appears as a small label under the stock pill. Clicking a row opens the details modal.
- **Pagination:** "Showing N out of total entries" with boxed page numbers.
- **Low Stock Alert:** image, name, "N pcs", Restock action (opens the modal on the Restock tab), View all (filters low stock).
- **Details modal:** gallery, name, SKU, category + subcategory, stock status, listing status, price; tabs Details / Restock / Stock history; for variant products a Variations & stock table with per-variant stock and a total.

## Color specifications

Stock status (text/border → background): Out of Stock `#8D0000` → `#FFD3D3`; Low Stock `#2563EB` → `#DBEAFE`; In Stock `#15803D` → `#DCFCE7`.  
Category (border/text → background): Pet Supplies `#6B4F2A`/`#F1E6D2`; Electronics & Gadgets `#315A8A`/`#DCE8F5`; Women's Apparel `#A34A6F`/`#F4DDE7`; Men's Apparel `#3F5D75`/`#DDE7EF`; Kids and Baby `#C56A35`/`#F7E1D1`; Home and Garden `#4E7650`/`#DDEBDD`; Sports and Outdoors `#237A6A`/`#D8ECE7`; Health and Beauty `#7A5685`/`#E9DDEC`; Makeup & Cosmetics `#B14F68`/`#F3DCE2`; Books and Media `#705A3A`/`#EEE5D5`; Food and Gourmet `#A65C2B`/`#F4E0CF`; Automotive & Motorcycle `#7A3E3E`/`#F0DADA`; Furniture and Office Equipment `#665A50`/`#E7E1DC`; Jewelry and Watches `#8A6A25`/`#F3E8C8`; Office and School Supplies `#486B7A`/`#DDE9ED`. Implemented in `Category::colors`.

## Add Product

Defined in [implementation spec](spec.md#add--edit-product-form-ui-contract): five sections, category-driven details, additional specifications, variations with per-variant stock, shipping with required package size, review summary, Save as Draft / Submit for Review.

## Visual rules

Base text 14 px; inputs 42 px high; card padding 24–28 px; Poppins; Vendo purple; transitions 0.2–0.5 s (modal, tabs, sections, tiles, variant rows, toast) disabled for `prefers-reduced-motion`.

## Not defined by this reference

Category CRUD, bulk import/export, delete/archive, configurable low-stock threshold, mobile-specific layouts beyond stacking.
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
