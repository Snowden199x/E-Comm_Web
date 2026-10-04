# Browse Shop

**Status:** Implemented basic catalog pages  
**Reviewed:** 4 October 2026

## Current behavior

Authenticated buyers can view categories and product listings/detail pages. The Buyer header's category strip and subcategory menu link to the product listing filtered by `category_id`, and the Dashboard shows a scrollable Categories row. See [Buyer Dashboard](../dashboard/spec.md) for the header, menu, and shared product card.

The product detail gallery includes Seller photos and, when present, a selectable video item with native browser controls. The product details section shows saved category attributes with category-specific labels and any additional Seller specifications; blank optional values are omitted. Beside those details, the page recommends up to four in-stock, approved products from the same category group, excluding the current product. The panel shows an empty state when no related products are available. Buyer, Seller, Logistics Center, and Admin layouts plus the public and standalone account pages use the shared transparent `Web_Logo.png` site icon.

## Gaps and acceptance direction

Seller shop pages, robust filters/sorting, and product approval/stock visibility rules need confirmation. The `category_id` filter matches only products assigned directly to that category, so choosing a parent category omits products assigned to its subcategories; it should include the category's children. The product listing and detail pages have not yet been restyled to match the new Buyer header.

## Source evidence

`app/Http/Controllers/Buyer/CategoryController.php`, `ProductController.php`, `resources/views/buyer/products/`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
