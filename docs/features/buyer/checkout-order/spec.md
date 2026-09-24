# Checkout and Order Creation

**Status:** Implemented core COD-style order creation  
**Reviewed:** 24 September 2026

## Current behavior

Validates shipping address/payment mode, checks locked product stock, groups cart items by seller, creates order and item snapshots, decrements stock, notifies sellers, and clears the cart inside a transaction.

## Gaps and acceptance direction

Payment gateway, shipping quotation, taxes/fees, idempotency key, and checkout retry semantics are not present in this scope.

## Source evidence

`app/Http/Controllers/Buyer/CheckoutController.php`, `app/Models/Ecommerce/Order.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
