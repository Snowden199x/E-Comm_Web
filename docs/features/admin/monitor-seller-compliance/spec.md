# Seller Compliance

**Status:** Implemented core review actions (UI redesigned 6 October 2026; seller popup added 7 October 2026; owner verification pending)  
**Reviewed:** 7 October 2026

## Current behavior

Admin can browse products for review, approve, reject, or warn, inspect warnings and violations, view suspended sellers, and lift a suspension. Five tabs share one layout: **Overview**, **Products for Review**, **Warnings**, **Violations**, **Suspended Sellers**.

**Layout.** Page header, tab bar, four summary cards, search and filters, one table card, and a footer with "Showing 1–8 of N entries" and boxed pagination (eight rows per page). Summary cards link to the matching tab. Products for Review shows For Review, Warnings Issued, Violations (Rejected), and Suspended Sellers; the other tabs show Compliant, With Warnings, With Violations, and Suspended sellers.

**In-place refresh.** Search (debounced), the category filter (Products), the date filter (Warnings, Violations), and paging fetch the existing partial routes (`sellers-table`, `products-table`, `warnings-table`, `violations-table`, `suspended-sellers-table`) and swap only the table region. A loading bar shows while a request runs, stale requests are cancelled, open dialogs close on each swap, and the URL stays in sync. A failed request shows an inline "Try again" message. Tab changes slide the active highlight and cross-fade the page where the browser supports view transitions; motion is off for reduced-motion users.

**Empty states.** Each tab has its own message when there is nothing to show (for example "You're all caught up" for Products for Review) and a separate "No results" message with "Clear filters" when a search or filter is active.

**Overview.** Approved sellers with avatar initial, category badges, a compliance score bar (80+ green, 50–79 amber, below 50 red), warning and violation counts, a Compliant or Suspended pill, and a link to the seller in User Management. Search matches seller name or email.

**Seller popup (Overview).** Selecting a row (or pressing Enter or Space on it) opens a popup for that seller: avatar, name, email, Compliant or Suspended pill, category badges, and a summary of products, compliance score, warnings, and violations. A **Products** tab lists the seller's latest 48 products (photo, name, category, price, stock, status) with status filter chips; a **Warnings and violations** tab lists up to 10 of each with reason, details, date, and product. Each tab has an empty state ("No products yet", "Clean record"). The popup links to the seller in User Management; the arrow at the end of the row does the same without opening the popup. Open state is `sellerId` in `scTable`, so it closes on each table swap and on Esc.

**Products for Review.** Only `for_review` products are listed. Search matches product name or seller name; the category filter uses the seller-registered category. The tab shows a count of products waiting for review beside its label when above zero. Selecting a row opens the review dialog: photo gallery, price, stock, category, seller and shop, description, product information, category details and specifications, variations, video, and a sticky action bar.
- **Approve** sets `approved` and notifies the Seller.
- **Reject** requires a reason and details (up to 500 characters), sets `rejected`, notifies the Seller, and records a violation.
- **Issue warning** requires a reason and details, records a warning, sets `warned`, and notifies the Seller. Every third warning also records an automatic violation.
- All three endpoints require `for_review`, so a Seller draft cannot be reviewed through a direct POST. A result dialog follows each action.

**Suspension rules (server-side, unchanged).** After a violation is recorded, 3 violations suspend the Seller for 7 days, 6 for 30 days, and 9 or more permanently, by setting `account_status = suspended` while `status` stays `approved`.

**Warnings and Violations.** Date filter (all, today, week, month, specific day) and search by seller name or email. Rows show product, seller, reason chip, two-line details with the full text on hover, and date.

**Suspended Sellers.** Reason, start date, and a live countdown for timed suspensions or a Permanent chip. **Activate** opens a confirmation dialog and posts to `admin.user-management.activate`; the page then shows a "Suspension lifted" result.

Seller submission and resubmission to `for_review` create a platform notification that opens this queue filtered to the product name; saving a draft does not notify Admin.

Reject and warn drafts survive a same-tab reload for up to two hours (`data-draft-key`). See [form reload recovery](../../shared/form-draft-recovery/spec.md).

## Gaps and acceptance direction

- Confirm remaining action authorization, appeal, and remediation requirements; ensure rejected products cannot be sold.
- The card counts include sellers that are not approved while the tables list approved sellers only, and "Compliant" counts sellers without violations even if they are suspended. See [Admin backend needs](../../../design/2026-10-06-admin-backend-needs.md).
- The seller popup reads each listed seller's products, warnings, and violations inside `sellers-table.blade.php` (about five queries per row, eight rows per page), and the tab count is one query in `tabs.blade.php`. Replace them with an eager-loaded or lazily fetched response. See [Admin UI refresh backend needs](../../../design/2026-10-07-admin-ui-refresh-backend-needs.md).
- The compliance score runs one query per row. Warnings, Violations, and Suspended Sellers have no live-update banner (Overview and Products for Review do).
- The escalation thresholds appear only in `escalateSuspension()`; the UI describes the "every third warning" rule but does not list the 3/6/9 thresholds.
- Verified by static review only (JavaScript syntax and Blade directive balance). No automated tests, build, or browser walkthrough were run. Owner verification is pending.

## Source evidence

`app/Http/Controllers/Admin/SellerComplianceController.php`, `app/Models/Compliance/`, `resources/views/admin/seller-compliance/`, `resources/js/admin/seller-compliance.js`, `resources/css/admin/seller-compliance.css`, `resources/views/components/admin/icon.blade.php`

## Related documentation

See [User Management](../manage-user-accounts/spec.md), [domain status](../../../domain-feature-status.md), the [Admin domain page](../../../domains/Admin.md), and [feature implementation guide](../../../feature-implementation-guide.md).