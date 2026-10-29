# Buyer Support Tickets

**Status:** Complaints list page implemented; full support-ticket lifecycle still absent
**Reviewed:** 29 October 2026

## Current behavior

Complaint/dispute records exist. A Buyer can file one from a shop chat (account report against a Seller) or through the complaint/report entry points; the record is stored in `complaints` with `complainant_id` set to the Buyer.

`buyer.complaints.index` now lists only complaints where the signed-in Buyer is the complainant. Each row shows the type, status (Open, In review, Resolved), description, filed time, the Seller it is about, attachment count, linked order when present, and the Admin decision once recorded. Open/review/resolved totals appear above the list. Rows link to the Buyer-scoped case messages (`buyer.case-messages.show`) so the Buyer can read and reply without seeing another user's case. The page is linked from the account menu.

## Gaps and acceptance direction

There is still no general support-ticket workflow separate from complaints: no categories for platform issues unrelated to an order, no SLA, no attachments from the Buyer after filing, and no reopen/closure by the Buyer. Decide if complaints are the support channel or whether a separate ticket lifecycle is needed. The existing list is read plus case messages only; it does not create complaints from scratch.

## Source evidence

`app/Models/Complaints/`, `app/Http/Controllers/Buyer/ComplaintController.php`, `resources/views/buyer/complaints/index.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
