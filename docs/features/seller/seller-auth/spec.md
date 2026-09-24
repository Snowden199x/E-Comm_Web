# Seller Registration and Access

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Seller submits identity, address, business permit, categories, and password after email OTP verification. Account remains pending until admin approval; active approved seller middleware guards dashboard/orders.

## Gaps and acceptance direction

Align cache OTP/session behavior with buyer registration, store uploads transactionally, and test role/status edges.

## Source evidence

`app/Http/Controllers/Seller/RegisteredUserController.php`, `app/Http/Middleware/EnsureActiveSeller.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
