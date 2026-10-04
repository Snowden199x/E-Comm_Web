# Seller Inventory

**Status:** Implemented stock display, restock and movement history.  
**Updated:** 4 October 2026

The Products & Inventory page reads seller-owned products, supports search/category/stock-status filters and pagination, and shows live inventory cards, category counts, and low-stock alerts. Restock adds a validated positive quantity and reason; a request key prevents duplicate additions. Stock changes from opening stock, restock, checkout and seller cancellation are recorded with actor, order where relevant, before/after quantities and timestamps.

Low stock is 1–10 units; zero is out of stock and more than 10 is in stock. Product edits do not allow directly replacing stock. Existing products start their movement history from the time tracking is enabled. Direct SQL/manual stock edits remain outside this ledger.

Variant products now require a seller-owned `variant_id` for restock. Checkout and cancellation adjust that variant and the product aggregate in one transaction; movements record the variant ID. The aggregate equals the sum of variant stocks when the application performs these operations. Direct SQL changes can still break that invariant. The variant schema requires the new 4 October migration and owner verification.

Sources: `Seller/ProductController`, `InventoryService`, `InventoryMovement`, `Product`, `SellerOrderWorkflow`, and `Buyer/CheckoutController`.

[Full implementation](../products-inventory/spec.md) · [Design functions](../products-inventory/design-functions.md)
