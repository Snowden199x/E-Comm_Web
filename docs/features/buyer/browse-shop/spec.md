# Browse Shop

**Status:** Implemented basic catalog pages  
**Reviewed:** 4 October 2026

## Current behavior

Authenticated buyers can view categories and product listings/detail pages. The Buyer header's category strip and subcategory menu link to the product listing filtered by `category_id`, and the Dashboard shows a scrollable Categories row. See [Buyer Dashboard](../dashboard/spec.md) for the header, menu, and shared product card.

## Gaps and acceptance direction

Seller shop pages, robust filters/sorting, and product approval/stock visibility rules need confirmation. The `category_id` filter matches only products assigned directly to that category, so choosing a parent category omits products assigned to its subcategories; it should include the category's children. The product listing and detail pages have not yet been restyled to match the new Buyer header.

## Source evidence

`app/Http/Controllers/Buyer/CategoryController.php`, `ProductController.php`, `resources/views/buyer/products/`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).