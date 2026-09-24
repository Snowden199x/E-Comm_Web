# Accept or Decline Order

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Seller may accept a placed order or decline with a required reason. Decline restores checkout-reserved stock once under a transaction and notifies the buyer.

## Gaps and acceptance direction

Add idempotency/race tests and policy for buyer-visible decline/refund/payment consequences.

## Source evidence

`app/Http/Controllers/Seller/OrderController.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
