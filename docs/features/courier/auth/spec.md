# Courier Authentication

**Status:** Partial credentials; courier destination incomplete  
**Reviewed:** 24 September 2026

## Current behavior

Courier is an allowed role in logistics login, and CourierDetail profile exists.

## Gaps and acceptance direction

No courier dashboard route is defined, and no courier-specific middleware/API exists.

## Source evidence

`app/Http/Controllers/Logistics/Auth/AuthenticatedSessionController.php`, `routes/web.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
