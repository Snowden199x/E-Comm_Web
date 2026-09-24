# Registration Email Verification

**Status:** Implemented with session-backed continuity  
**Reviewed:** 24 September 2026

## Current behavior

Buyer OTP is stored in a verification record and a proof token/email/expiry in the registration session; refresh can repopulate the verified email until expiry.

## Gaps and acceptance direction

Test expiry, cross-session behavior, resend throttling, and cleanup. Buyer OTP state is database-backed unlike seller/logistics cache OTP.

## Source evidence

`app/Http/Controllers/Buyer/OtpController.php`, `app/Http/Controllers/Buyer/RegisteredBuyerController.php`, `resources/views/buyer/auth/registration-scripts.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
