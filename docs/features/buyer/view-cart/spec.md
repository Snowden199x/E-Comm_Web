# Cart

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Buyers can view cart items, add/update quantity, and remove items; data is scoped to the authenticated user.

## Gaps and acceptance direction

Verify out-of-stock/product-status handling and stale-price behavior at checkout.

## Source evidence

`app/Http/Controllers/Buyer/CartController.php`, `app/Models/Ecommerce/CartItem.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
