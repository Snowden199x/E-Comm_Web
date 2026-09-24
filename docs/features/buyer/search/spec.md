# Product Search

**Status:** Partial  
**Reviewed:** 24 September 2026

## Current behavior

Product listing supports request-driven query behavior where wired in the controller/view.

## Gaps and acceptance direction

There is no dedicated search service/index, and the current filters do not cover brand, price, stock, or relevance ranking.

## Source evidence

`app/Http/Controllers/Buyer/ProductController.php`, `resources/views/buyer/products/index.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
