# Logistics Navigation

**Status:** Sidebar, center operations, Rider Management, filters, notifications and reports implemented in web code; Messages and physical SH scan flow remain pending
**Updated:** 4 October 2026

The protected Logistics pages share a responsive Vendo sidebar. It lists Dashboard, Rider Management, Incoming Parcels, Parcel Sorting, Delivery Assignments, Delivery Monitoring, Reports, Messages, Account Management, and Logout, grouped under People, Parcels, Insights, and Settings. The structure was informed by the public [Aisley logistics navigation](https://github.com/ZieksQ/aisley/blob/main/src/logistics/src/components/LogisticsSidebarNav.tsx), while Vendo keeps its Laravel/Blade routes and styling.

Rider Management now has a dedicated center-scoped `logistics.riders.index` page with active, pending, rejected and inactive tabs; the dashboard also keeps its pending-applications summary. A center can approve/reject applicants and activate/deactivate its approved riders. Active delivery workload is counted from all assigned and out-for-delivery orders for this center. Incoming Parcels, Parcel Sorting, and Delivery Assignments filter the existing center-scoped dispatch operations by stage. Delivery Assignments keeps scanned `out_for_delivery` parcels visible to their destination center. Delivery Monitoring lists active orders routed through the authenticated center with the latest known status, assigned operator, and update time, including a configured route when present. Reports default to orders updated in the previous 30 days, offer date ranges up to one year, and group current status, delivery area and delivery rider. These are order snapshots, not independent scan analytics or proof of SH locality arrival. Messages remains an explicit placeholder. The original `/logistics/dispatch` route remains available for existing links and center notifications. All routes use the approved active logistics-center middleware.

## Sidebar items and routes

| Section | Item | Route |
| --- | --- | --- |
| (top) | Dashboard | `logistics.dashboard` |
| People | Rider Management | `logistics.riders.index` |
| Parcels | Incoming Parcels | `logistics.incoming-parcels` |
| Parcels | Parcel Sorting | `logistics.parcel-sorting` |
| Parcels | Delivery Assignments | `logistics.delivery-assignments` |
| Parcels | Delivery Monitoring | `logistics.delivery-monitoring` |
| Insights | Reports | `logistics.reports` |
| Insights | Messages | `logistics.messages` (placeholder) |
| Settings | Account Management | `logistics.account.index` |
| (bottom) | Logout | `logistics.logout` (POST) |

## Shell behavior (redesigned 3 October 2026)

- The shell keeps Vendo's plum sidebar with a gold active marker. On desktop it is expanded by default and collapses to an icon rail with the topbar menu button; the choice is saved in `localStorage` under `vendo.logistics.nav`. A collapsed sidebar expands on hover. Below 960px it becomes a slide-out drawer with a backdrop that closes on backdrop click, Escape, or link tap. Delivery Assignments and Delivery Monitoring are two flat links under Parcels; there is no expanding Delivery group.
- The topbar shows a breadcrumb, today's date, and an account menu (Account Management, Logout). The bell links to a center-owner notification inbox and polls its unread count every 30 seconds.
- Account Management now lets an approved, active Logistics Center upload or remove its profile photo; see [account management](../account-management/spec.md).
- Flash messages (`success`, rider `confirmation`, and validation errors) render once as toasts from the layout, so individual pages no longer print them.
- Typography and spacing follow an e-commerce scale: Poppins, 14px body, 24px page titles, and an 8px spacing grid. Transitions cover page and card entrance, sidebar width, dialogs and menus, toasts, count-up numbers, and the parcel tracker, and all respect `prefers-reduced-motion`.
- Dispatch and monitoring now apply search, status, area, date and stage filters in center-scoped database queries; stage counts cover matching records beyond the current page.
- The dashboard passes center-scoped workflow counts for pickup, sorting, delivery assignment and active parcels.
- Styles live in `resources/css/logistics/workspace.css` (`lg-` prefix) and behavior in `resources/js/logistics/workspace.js`; icons are inline SVG from `components/logistics/icon.blade.php`.

## Operations added 4 October 2026

- Incoming parcels require center verification before pickup rider assignment. A declined request returns to Seller preparation with a reason; the Seller can mark it ready again. The new pickup state and reason columns come from migration `2026_10_04_000002_add_pickup_request_review_to_orders.php`.
- Delivery Assignments can assign several ready parcels from one `shipping_city_code` area to one active, approved local Rider. The transaction locks all selected orders and rejects an out-of-scope or changed parcel. Truck operators are reserved for linehaul assignment, not final local delivery.
- New pickup routing, hub arrivals and Rider applications create notifications for the owning Logistics account. The inbox and unread-count endpoint are scoped by authenticated user ID. Existing historical events are not backfilled.
- Report PDF and Excel-compatible CSV are generated server-side from the selected center-scoped date range. CSV is not an `.xlsx` file; no spreadsheet package has been introduced.
- Messages remains a placeholder. Physical SH arrivals, sorting scans, custody, truck manifests, route progression, and SH-to-local-rider handoff still need the separate scanner/mobile contracts. A planned virtual checkpoint does not prove physical custody.

Owner migration, asset compilation and end-to-end verification remain pending.

`resources/js/logistics/workspace.js` is already present in `vite.config.js`; no Vite entry needs to be added for this backlog. This web work is for the Logistics Center portal. Rider/Truck interfaces and the separate SH scanner app remain mobile-only.
