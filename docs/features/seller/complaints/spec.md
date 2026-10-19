# Seller Complaints List

**Status:** Implemented listing; owner verification pending.
**Reviewed:** 12 October 2026

The Seller Complaints page lists only cases where the signed-in Seller is the respondent or complainant. Server-side scope, status, type, and search filters apply before pagination. The counts use the same Seller scope. Case details show related order, parties, activities, and evidence filenames except that anonymous Buyer report files use generic labels. Private evidence bytes are not exposed by this list. Filing and case messaging remain the existing role-scoped flows; this route adds a Seller overview, not a new dispute workflow.

**Source:** `app/Http/Controllers/Seller/ComplaintController.php`, `resources/views/seller/complaints/index.blade.php`, and `routes/web.php`.
