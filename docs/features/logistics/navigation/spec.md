# Logistics Navigation

**Status:** Sidebar and core center-scoped sections implemented; UI redesigned 3 October 2026; Messages remains a placeholder
**Updated:** 3 October 2026

The protected Logistics pages share a responsive Vendo sidebar. It lists Dashboard, Rider Management, Incoming Parcels, Parcel Sorting, Delivery Assignments, Delivery Monitoring, Reports, Messages, Account Management, and Logout, grouped under People, Parcels, Insights, and Settings. The structure was informed by the public [Aisley logistics navigation](https://github.com/ZieksQ/aisley/blob/main/src/logistics/src/components/LogisticsSidebarNav.tsx), while Vendo keeps its Laravel/Blade routes and styling.

Rider Management is the center-scoped applications and approve/reject screen. Until a dedicated riders route exists, it lives on the dashboard page and the sidebar link scrolls to its Rider applications section (`logistics.dashboard#rider-applications`); if a `logistics.riders.index` route is added later, the link switches to it automatically. Incoming Parcels, Parcel Sorting, and Delivery Assignments filter the existing center-scoped dispatch operations by stage. Delivery Assignments keeps scanned `out_for_delivery` parcels visible to their destination center. Delivery Monitoring lists active orders routed through the authenticated center with the latest known status, assigned operator, and update time, including a configured route when present. Reports summarize orders updated in the previous 30 days, grouped by current status. These are order snapshots, not independent scan analytics or proof of SH locality arrival. Messages remains an explicit placeholder. The original `/logistics/dispatch` route remains available for existing links and center notifications. All routes use the approved active logistics-center middleware.

## Sidebar items and routes

| Section | Item | Route |
| --- | --- | --- |
| (top) | Dashboard | `logistics.dashboard` |
| People | Rider Management | `logistics.dashboard#rider-applications` (switches to `logistics.riders.index` if that route exists) |
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
- The topbar shows a breadcrumb, today's date, and an account menu (Account Management, Logout). It has no notification bell because no Logistics notification route exists yet.
- Flash messages (`success`, rider `confirmation`, and validation errors) render once as toasts from the layout, so individual pages no longer print them.
- Typography and spacing follow an e-commerce scale: Poppins, 14px body, 24px page titles, and an 8px spacing grid. Transitions cover page and card entrance, sidebar width, dialogs and menus, toasts, count-up numbers, and the parcel tracker, and all respect `prefers-reduced-motion`.
- Search, area, and stage filters on the parcel pages work only on the parcels loaded on the current page. Server-side filtering is listed in `docs/features/logistics/redesign-backend-needs.md`.
- The dashboard parcel workflow cards show counts only when the controller passes an optional `$overview` array; otherwise they are plain shortcuts.
- Styles live in `resources/css/logistics/workspace.css` (`lg-` prefix) and behavior in `resources/js/logistics/workspace.js`; icons are inline SVG from `components/logistics/icon.blade.php`.