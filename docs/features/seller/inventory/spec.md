# Seller Inventory

**Status:** Implemented stock display, restock and movement history.  
**Updated:** 24 September 2026

The Products & Inventory page reads seller-owned products, supports search/category/stock-status filters and pagination, and shows live inventory cards, category counts, and low-stock alerts. Restock adds a validated positive quantity and reason; a request key prevents duplicate additions. Stock changes from opening stock, restock, checkout and seller cancellation are recorded with actor, order where relevant, before/after quantities and timestamps.

Low stock is 1–10 units; zero is out of stock and more than 10 is in stock. Product edits do not allow directly replacing stock. Existing products start their movement history from the time tracking is enabled. Direct SQL/manual stock edits remain outside this ledger.

Sources: `Seller/ProductController`, `InventoryService`, `InventoryMovement`, `Product`, `SellerOrderWorkflow`, and `Buyer/CheckoutController`.

[Full implementation](../products-inventory/spec.md) · [Design functions](../products-inventory/design-functions.md)
