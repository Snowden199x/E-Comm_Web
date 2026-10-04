# Buyer Account

**Status:** Implemented profile basics  
**Reviewed:** 24 September 2026

## Current behavior

Buyer can edit profile/password and upload/remove profile picture and banner.
Unsubmitted profile fields survive an accidental reload in the same tab for up to two hours. Passwords and image files are excluded. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Add stronger file validation, private-storage rules, and address history if needed.

## Source evidence

`app/Http/Controllers/Buyer/AccountController.php`, `resources/views/buyer/account.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
