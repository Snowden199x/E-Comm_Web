# Registration Review

**Status:** Implemented  
**Reviewed:** 28 September 2026

## Current behavior

Admin filters pending buyer, seller, and logistics-center applications; can inspect profiles, approve with an email, or reject with a reason and notes.
ID and business-permit previews now use a protected Admin route that selects the applicant's stored document field and reads the private disk. A secondary Buyer ID, when submitted, has its own review link. Public file URLs are no longer used for these documents.

## Gaps and acceptance direction

Add action audit events, verify details before mail/send failure handling, and test each account role.

## Source evidence

`app/Http/Controllers/Admin/RegistrationController.php`, `app/Http/Controllers/VerificationDocumentController.php`, `resources/views/admin/registrations/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
