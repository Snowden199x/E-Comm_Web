# Complaints and Disputes

**Status:** Implemented core status flow (UI redesigned 7 October 2026; owner verification pending)  
**Reviewed:** 7 October 2026

## Current behavior

Complaints have complainant/respondent, optional order link, evidence, activity records, and an admin status update flow.
Cases are of two kinds: an order **complaint** (`kind = complaint`) and an **account report** (`kind = user_report`), which Admin approves (warns the reported account) or rejects with a note.

**List (`admin.complaints.index`, partial `admin.complaints.table`).** Four stat cards (Total Complaints, Open Cases, In Progress, Resolved) also filter the list by status. Search by name or email, a case-kind filter (all, order complaints, account reports), a date filter, and a type filter refresh only the table in place (debounced, stale requests cancelled, URL kept in sync). Columns: Complaint ID, Parties (name with role), Type, Status, Date Filed. Open cases older than two days show "Waiting N days". Selecting a row opens a **Complaint Preview** popup (ID, filed date, status, type, order ID, amount, description, parties, and See Full Details; open order complaints also offer Mark in progress). Previews are rendered inside the swapped table, so they always match the visible rows. Empty states: no cases yet, and no match for the filters (with Clear filters).

**Sample case.** "View a sample case" shows one in-memory example (`partials/sample-case.blade.php`); it is never stored. Remove it once real cases exist.

**Case page (`admin.complaints.show`).** Header with the case ID and status; Complaint Summary; Order Information with product detail dialogs; Timeline (newest first); Parties Involved; Supporting Evidence (first three files plus a "+N Other" tile); and case-linked Buyer/Seller message threads. Admin can send to a case participant, and that participant can reply from a private case page linked by notification. Each thread belongs to one case and one participant; the other participant cannot read it. Messaging closes when the case is resolved. Status buttons (Mark in progress, Mark resolved) apply to order complaints; account reports use the review-note form. A confirmation toast follows a status change or decision.

An unfinished Admin decision note survives a same-tab reload for up to two hours. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

Define resolution outcomes/refunds and role-facing conversation/notification workflow; enforce allowed transitions.

- Status and case-kind filters now apply in `ComplaintController::filteredComplaints()` before pagination. Stat cards remain global counts, so they do not change with the current search and filter selection.
- Courier case messaging remains outside the web UI because Courier is mobile-only. The future Rider API needs its own case-message authorization and notification contract before exposing that participant.
- Case messaging supports text only. Attachments, shared multi-party threads, read receipts in the case panel, and automatic case-status transitions from messages are future work.
- Verified by static review only (JavaScript syntax and Blade directive balance). No automated tests, build, or browser walkthrough were run. Owner verification is pending.

## Source evidence

`app/Http/Controllers/Admin/ComplaintController.php`, `app/Http/Controllers/CaseMessageController.php`, `app/Models/Complaints/`, `resources/views/admin/complaints/`, `resources/views/shared/case-messages.blade.php`, `resources/js/admin/complaints.js`, `resources/css/admin/complaints.css`

## Related documentation

See the [7 October frontend pull review](../../../design/2026-10-07-pulled-frontend-review.md), [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
