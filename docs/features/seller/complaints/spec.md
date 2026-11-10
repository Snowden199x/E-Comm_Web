# Seller Complaints List

**Status:** Implemented listing; owner verification pending.
**Reviewed:** 12 October 2026

The Seller Complaints page lists only cases where the signed-in Seller is the respondent or complainant. Server-side scope, status, type, and search filters apply before pagination. The counts use the same Seller scope. Case details show related order, parties, activities, and evidence filenames except that anonymous Buyer report files use generic labels. Private evidence bytes are not exposed by this list. Filing and case messaging remain the existing role-scoped flows; this route adds a Seller overview, not a new dispute workflow.

**Source:** `app/Http/Controllers/Seller/ComplaintController.php`, `resources/views/seller/complaints/index.blade.php`, and `routes/web.php`.

## 9 November 2026 update

Each card now loads order items with product images, and only this Seller's own Vendo thread with its unread count. A new route `GET /seller/complaints/{complaint}/evidence/{evidence}` (`seller.complaints.evidence`) streams one evidence file through `local` then `public` storage. The case is looked up first with `respondent_id` or `complainant_id` equal to the signed-in Seller, and the file must belong to that case; other case IDs return 404. The response sets `X-Content-Type-Options: nosniff`, and an anonymous Buyer report is renamed to `evidence-{id}.{ext}` so the Seller never sees the original filename. The page redesign also adds empty states and fix-oriented messages for the scope, status, type and search filters. See the [10 November progress log](../../../logs/PROGRESS-2026-11-10.md).
