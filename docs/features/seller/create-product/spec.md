# Create and Manage Products

**Status:** Implemented creation, details and edit; admin approval retained.  
**Updated:** 24 September 2026

Seller can add a product with name, description, price, category, opening stock, optional brand, and product photos. Product images accept JPG/PNG/WebP up to 2 MB each, with at most six photos and 10 MB combined per save; the optional video is limited to 10 MB. The server assigns seller ownership and a unique SKU. Category choices are limited to registered selling categories/children. New and changed listings enter `for_review`, which feeds the existing admin compliance review screen. Buyer checkout rejects unapproved listings. See [Products & Inventory](../products-inventory/spec.md) for the current form behavior.

Editing listing details uses a revision token to avoid lost updates and does not overwrite stock. Sellers can replace the main photo, add gallery photos, or remove existing photos while keeping one to six photos total. Gallery ordering makes the first photo the main one in seller, admin and buyer views. Restock is a separate audited action. Details show review state, rejection information, material, weight, origin, photo gallery, stock and movement history. Product deletion and bulk import/export are not part of this page.

Sources: `app/Http/Controllers/Seller/ProductController.php`, `app/Models/Ecommerce/Product.php`, and `resources/views/seller/products/`.

[Products & Inventory](../products-inventory/spec.md)
