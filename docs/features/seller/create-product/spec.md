# Create and Manage Products

**Status:** Implemented creation, details and edit; admin approval retained.  
**Updated:** 24 September 2026

Seller can add a product with name, description, price, category, opening stock, optional brand/colors/sizes and product image. The server assigns seller ownership and a unique SKU. Category choices are limited to registered selling categories/children. Images accept JPG/PNG/WebP up to 2 MB. New and changed listings enter `for_review`, which feeds the existing admin compliance review screen. Buyer checkout rejects unapproved listings.

Editing listing details uses a revision token to avoid lost updates and does not overwrite stock. Restock is a separate audited action. Details show review state, rejection information, stock and movement history. Product deletion and bulk import/export are not part of this page.

Sources: `app/Http/Controllers/Seller/ProductController.php`, `app/Models/Ecommerce/Product.php`, and `resources/views/seller/products/`.

[Products & Inventory](../products-inventory/spec.md) · [Design functions](../products-inventory/design-functions.md)
