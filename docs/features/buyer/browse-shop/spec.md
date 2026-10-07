# Browse Shop

**Status:** Implemented catalog pages; product list and detail restyled 6 October 2026; owner verification pending  
**Reviewed:** 6 October 2026

## Current behavior

Authenticated buyers can view categories and product listings/detail pages. The Buyer header's category strip and subcategory menu link to the product listing filtered by `category_id`, and the Dashboard shows a scrollable Categories row. See [Buyer Dashboard](../dashboard/spec.md) for the header, menu, and shared product card.

The product detail gallery includes Seller photos and, when present, a selectable video item with native browser controls. The product details section shows saved category attributes with category-specific labels and any additional Seller specifications; blank optional values are omitted. Beside those details, the page recommends up to four in-stock, approved products from the same category group, excluding the current product. The panel shows an empty state when no related products are available. Buyer, Seller, Logistics Center, and Admin layouts plus the public and standalone account pages use the shared transparent `Web_Logo.png` site icon.

The product list (`buyer.products.index`) shows a result heading with the count, a Clear filters button, a link to search shops for the same text (when shop search exists), the shared product card grid, and designed empty states for no search match and for an empty category. The product detail page was rebuilt from the owner's mockup; see [Product detail](../product-detail/spec.md). The shop page and shop search are in [Seller shop](../seller-shop/spec.md).

## Gaps and acceptance direction

Robust filters/sorting and product approval/stock visibility rules need confirmation. The `category_id` filter matches only products assigned directly to that category, so choosing a parent category omits products assigned to its subcategories; it should include the category's children.

## Source evidence

`app/Http/Controllers/Buyer/CategoryController.php`, `ProductController.php`, `resources/views/buyer/products/`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).