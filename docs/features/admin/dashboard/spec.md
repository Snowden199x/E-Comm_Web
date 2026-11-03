# Admin Dashboard

**Status:** Implemented, reporting semantics need review (UI redesigned 7 October and again 31 October 2026; owner verification pending)  
**Reviewed:** 7 October 2026

## Current behavior

Shows platform order/sales/user counts, a six-week summary, pending Buyer/Seller/Logistics counts, recent registrations/complaints/notifications, and the latest published all-user announcement. Total Users includes Buyer, Seller and Logistics accounts. The route now applies the same active Admin and forced-password-change middleware as other Admin pages.

**Layout (7 October 2026).** Time-of-day greeting with quick actions (registrations, products for review, announcements). A **Needs your attention** card lists four counts that each link to their queue: pending registrations, products with status `for_review`, open complaints, and suspended sellers (`account_status = suspended`, `status = approved`); a zero reads "All clear". Four stat cards (orders, sales, users, sellers) show a week-over-week change taken from the six weekly buckets already supplied for the chart. The latest bucket is the current partial week, so the change compares this week so far with last week. A Sales | Orders toggle switches the bar chart; the sales summary shows completed and returned/refunded rates against total orders. Recent registrations (with the existing approve, reject, and details dialogs), recent complaints and disputes (each links to its case), notifications, pending registrations by type, and the announcement card each have an empty state. Styling is flat, with no gradients; motion is off for reduced-motion users.

## Gaps and acceptance direction

Confirm whether gross sales should include cancelled, returned, or unpaid orders; use shared reporting definitions. The week-over-week change and the completed/returned rates inherit whatever definition that review settles on.

- `DashboardController` now supplies the four "Needs your attention" counts; the view renders that data. Reporting definitions still need review. See the [7 October frontend pull review](../../../design/2026-10-07-pulled-frontend-review.md).
- Verified by static review only (JavaScript syntax and Blade directive balance). No automated tests, build, or browser walkthrough were run. Owner verification is pending.

## Source evidence

`app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `resources/js/admin/dashboard.js`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).

## 31 October 2026 update

Follows the owner's mockup (see the [Admin design system](../../../design/admin-design-system.md) for the palette and rules): Greeting with the date and three quick actions (**Review registrations**, **Review products**, **Create announcement**); four tinted stat cards; Sales overview and Sales summary (flat, with a chart loading state and a **Reload page** error if Chart.js fails); Recent registrations table with view, approve and reject icon buttons and a details dialog; Recent complaints table (ID, From, Against, Status); Notifications; Pending registrations by type; and the Announcement card. Approve and reject show an `x-admin.flash` banner, including the email-failed warning. Every panel has an empty state. The earlier "Needs your attention" tiles and week-over-week chips were removed. Not verified in a browser.
