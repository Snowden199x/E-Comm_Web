# Seller Dashboard

**Status:** Implemented with live scoped data  
**Reviewed:** 5 October 2026 (UI refresh; data unchanged)

## Current behavior

Dashboard computes seller order/sales counts, chart data, delivered top items, inventory alerts, notices, and announcements.

## Layout and UI (5 October 2026 refresh)

Same layout as before, restyled for readability. Frontend only; `DashboardController` data is unchanged.

- **Welcome row:** time-of-day greeting, store name, today's date, **View orders** and **Add product** buttons.
- **Stats row (always one line):** Total Orders, Total Sales, Pending Orders, To Ship, Average Rating. Cards link to Orders, Reports, Orders › New, Orders › To Pack and Feedback. Numbers count up on load; Total Sales shows the change against the previous week when there is a previous week. Below roughly 1180 px the row scrolls sideways instead of wrapping.
- **Sales Overview:** six-week totals (sales, non-cancelled orders, weekly average computed from the existing chart data), legend buttons that show/hide each series, peso tooltips, empty state when there is no data. An order cancelled by either Buyer or Seller is excluded from the chart's order count and the "Orders, excluding cancelled" total. Sales amount and Top Selling Products already count delivered/completed orders only. Cancelled orders remain in Recent Orders and the Orders workspace as history.
- **Recent Orders:** row list (order number and time, buyer with initials, total, status pill) linking to that order's drawer; empty state with a link to Products.
- **Top Selling Products:** rank, product image, relative sales bar, revenue and units sold; empty state.
- **Inventory Alerts:** Out of stock and Low stock groups with stock bars and links to each product; "Everything is stocked" state. Product images appear only when the controller eager-loads `images` (see backend needs); placeholders otherwise.
- **Notifications:** unread dot, title, two-line message, relative time. This list is re-rendered every 3 seconds by the seller layout's notification poll, so it intentionally has no entrance animation.
- Minimum text size raised from 7–9 px to 11.5–12 px. Styles use the `db-` prefix, appended to `seller-dashboard.css` and scoped under `.sd-body`; the older `sd-welcome`/`sd-stat`/`sd-card` dashboard rules are now unused and can be removed later.

## Gaps and acceptance direction

Rating requires reviews; products and stock updates are not yet managed through a seller CRUD workflow.

## Source evidence

`app/Http/Controllers/Seller/DashboardController.php`, `resources/views/seller/dashboard.blade.php`, `resources/views/seller/notifications/dashboard.blade.php`, `resources/css/seller/seller-dashboard.css`

## Related documentation

See [backend needs](../backend-needs.md), [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
