# Product Search

**Status:** Partial  
**Reviewed:** 6 October 2026

## Current behavior

Product listing supports request-driven query behavior where wired in the controller/view. The Buyer header search box on every Buyer page submits `search` to `buyer.products.index` and keeps the entered text on the results page. A search with no match shows an empty state with Browse all products and, when shop search exists, Search shops instead.

**Shop search (view ready, server Planned):** the header search shows a Products/Shops selector only when the route `buyer.sellers.index` exists. Shops are searched by shop name or account name and open `buyer/sellers/index.blade.php`. The route and controller method are not implemented; see [Seller shop](../seller-shop/spec.md) and [backend needs](../../../backend-needs.md), item 2.

## Gaps and acceptance direction

There is no dedicated search service/index, and the current filters do not cover brand, price, stock, or relevance ranking.

## Source evidence

`app/Http/Controllers/Buyer/ProductController.php`, `resources/views/buyer/products/index.blade.php`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).