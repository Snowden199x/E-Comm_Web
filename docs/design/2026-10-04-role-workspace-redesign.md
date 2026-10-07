# Vendo Role Workspace Redesign

**Date:** 4 October 2026  
**Scope:** Buyer storefront, Seller product workspace, Logistics Center portal, and Admin registration/user-management screens  
**Reference:** Team pull request #55, merged as `3726244`; role-specific implementation notes are linked below.

## Purpose

This document records the visual and interaction direction introduced in the 3–4 October UI update. It describes the current Blade/Vite implementation and separates it from backend work that is still pending. The screens are part of the existing Laravel application; this change does not introduce a new frontend framework or change the role/authentication model.

## Shared design direction

- Keep Vendo's plum/purple identity, with restrained gold accents and warm neutral page surfaces.
- Use Poppins, compact readable marketplace typography, clear page headings, white content surfaces, and consistent status colors.
- Use short transitions for menus, dialogs, row refreshes, and content entry. Honor `prefers-reduced-motion` where implemented.
- Keep existing route names, form field names, authorization rules, and controller data contracts unless a feature spec explicitly records a change.
- Give missing images, empty lists, unavailable previews, and loading requests visible fallbacks rather than implying data exists.
- Keep role-specific CSS and JavaScript in the existing Vite asset folders. Blade remains responsible for markup and server-rendered data.

The current work uses role-scoped CSS variables rather than one shared token file:

| Surface | Main colors and scale |
| --- | --- |
| Buyer | Ink `#2b1730`, plum `#402143`, accent `#805487`, gold `#e8c874`, page `#f6f2f7`; 14px body with compact storefront text. |
| Logistics | Brand `#5a1d63`, dark brand `#43104a`, gold `#c8962e`, warm page `#fdfbf7`; 14px body, 24px page titles, 8px spacing grid. |
| Seller Products | Plum `#512258`, accent `#805487`, tint `#f4eef5`; stock states use green, blue, and red with pale backgrounds. |
| Admin Registration | Existing Tailwind palette plus `rg-` refresh/entry motion styles in `resources/css/admin/registrations.css`. |

These are the values in the current assets, not a consolidated design-token package. Shared tokens can be considered if the team later aligns the role palettes.

## Buyer storefront

### Shell and navigation

The Buyer workspace replaces the previous sidebar with a purple marketplace header containing the Vendo logo, product search, Messages, Cart with item count, Notifications, and an account menu for My Orders, Account Management, and Logout. A category strip below the header opens a category/subcategory menu. It uses available category imagery and falls back to a letter tile when an image is missing.

### Dashboard and cart

The dashboard presents an announcement banner, a scrollable category row, and a Recommended for You product grid. Shared product cards support Add to Cart; color/size selections open a quick-select sheet before adding. The cart groups lines by seller and provides line selection, select-all, quantity controls, removal, a sticky summary, and COD checkout. An optional You May Also Like section appears only when its controller data is supplied.

### Data integration and limits

The dashboard controller now supplies audience-filtered/due Buyer announcements, 18 products from approved active sellers, eager-loaded shop names, and rating/sold aggregates. Parent-category filtering includes child products. Cart recommendations and variant IDs flow through cart, checkout and order items. Sellers can set an optional original price, displayed crossed out when it exceeds the selling price. Shipping remains zero because there is no quote service. See the [Buyer Dashboard spec](../features/buyer/dashboard/spec.md), [Cart spec](../features/buyer/view-cart/spec.md), and [Buyer progress entry](../logs/PROGRESS-2026-10-04.buyer-entry.md).

## Seller Products & Inventory

The products page uses four inventory summary cards, search/category/status filters, a product table with category and stock labels, a Low Stock Alert panel, pagination, and a product details modal. The modal contains details, restock, and stock-history tabs. Add/Edit Product uses a five-section form for basic information, category details, variations and stock, shipping, and review/submit. The UI supports responsive layouts and reduced-motion behavior.

The server now persists drafts, category attributes, specifications, video, variation types and variants, per-variant stock, package dimensions, fragile flag, and annual `PRD-YYYY-NNNN` codes. Variant-aware Buyer checkout, existing-image main selection, and expanded Admin Product Review details are wired in code. Incomplete variation drafts and server-side video duration validation remain open. The fixed low-stock threshold remains 10. See the [Seller Products & Inventory spec](../features/seller/products-inventory/spec.md), [design functions](../features/seller/products-inventory/design-functions.md), and [Seller progress entry](../logs/PROGRESS-2026-10-03.seller-entry.md).

## Logistics Center portal

The shell uses a plum sidebar, gold active marker, top bar, account menu, and shared toast region. Desktop navigation can collapse to an icon rail; narrow screens use a drawer. The sidebar sections cover Dashboard, Rider Management, Incoming Parcels, Parcel Sorting, Delivery Assignments, Delivery Monitoring, Reports, Messages, Account Management, and Logout.

Dispatch pages present stage tabs, parcel cards, progress indicators, area/search controls, and rider load information. Monitoring uses a parcel table and route chips. Rider Management has a dedicated center-scoped page with approval and activation controls. Dispatch and monitoring filters run on the server; dashboard counts, pickup verification/decline, same-area bulk delivery assignment, a scoped Logistics notification feed, and report date ranges/breakdowns/PDF/CSV exports are wired in code. Messages is a placeholder.

The Logistics portal remains responsible for center operations. It does not represent a Rider or Truck app, and a planned SH route is not evidence of an arrival or custody transfer. Physical SH scans, truck manifests and route progression, local pickup from an SH, and independent delivery proof remain pending. See the [Logistics Navigation spec](../features/logistics/navigation/spec.md), [Logistics progress entry](../logs/PROGRESS-2026-10-03.logistics-entry.md), and [order flow decisions](../order-logistics-flow-decisions.md).

### Asset integration correction

The pulled shell referenced `resources/js/logistics/workspace.js` in both Vite and the Blade layout, while the incoming file was named `worskspace.js`. The filename has been corrected to `workspace.js` so it matches both references. Owner-side browser verification and Vite compilation remain pending.

## Admin Registration and User Management

The Admin shell remains unchanged. Registrations and User Management use pending-count cards, searchable/filterable tables, windowed pagination, in-place refresh, clearer empty states, and modal dialogs. Registration details use profile information cards, tabbed personal/address/business sections, protected ID preview/lightbox, and a sticky approve/reject bar. User Management keeps the user profile in a modal and groups profile data and status actions there. Action dialogs show busy states to prevent duplicate submissions.

The interfaces continue to use the existing server operations. Approval sends its existing email; rejection records a reason but does not send an email. Riders remain approved by their linked Logistics Center and are not included in Admin Registration review. The UI/UX backend handoff includes aligning User Management counts with listed statuses; handling approval-email failures safely; deciding whether rejection sends email; defining rider review ownership before adding mockup vehicle tabs; configurable/permanent suspension; a lighter table-plus-dialogs response; server validation for "Other" details; and a general admin audit trail. Product Review also needs to display the new Seller listing fields and exclude drafts. The four pending-count SVG icons and `registrations.css` are present and registered in Vite. See the [Registration Review spec](../features/admin/manage-account-registration/spec.md), [User Management spec](../features/admin/manage-user-accounts/spec.md), [Seller Compliance spec](../features/admin/monitor-seller-compliance/spec.md), and [Admin progress entry](../logs/PROGRESS-2026-10-04.admin-entry.md).

## Accessibility and responsive behavior

- Keep visible keyboard focus, accessible labels for icon-only controls, and keyboard-operable dialogs/menus.
- Respect reduced-motion preferences on animated surfaces.
- Ensure Buyer navigation and cart summaries reflow on narrow screens, Logistics uses its drawer below the documented breakpoint, and product/admin dialogs remain within the viewport.
- Keep loading, empty, validation, and unavailable-document states understandable without relying on color alone.

These are design acceptance points; no browser walkthrough or screen-reader review is implied by this document.

## Verification and remaining work

### Backend integration later on 4 October

The UI/UX handoff is now partly implemented in this repository. Buyer dashboard data and recommendations, Seller product fields/variants/stock, Admin review/counts/suspension/audit records, and Logistics pickup review/filters/roster/notifications/reports have backend routes and persistence. Three new migrations are pending owner execution. The implementation state and remaining gaps are tracked in the linked role specs and `docs/domain-feature-status.md`; earlier “backend needs” paragraphs above describe the team handoff at the time it was received. Browser, Vite and database verification remain with the owner.

The team recorded static review of changed markup/scripts, but no Vite build, automated tests, or owner browser acceptance is recorded for this pull. The filename mismatch above was corrected during documentation review. The owner should verify the role layouts at desktop and narrow widths and run the normal Vite development or production asset process.

Backend dependencies and unimplemented flows remain listed in the linked feature specifications. Do not treat a visual control, count card, route chip, or planned route as proof of a persisted or completed operation.

## Main implementation files

- Buyer: `resources/views/components/buyer/layout.blade.php`, `resources/views/buyer/dashboard.blade.php`, `resources/views/buyer/cart.blade.php`, `resources/css/buyer/layout.css`, and `resources/js/buyer/sidebar.js`.
- Seller Products: `resources/views/seller/products/`, `resources/css/seller/products.css`, `resources/js/seller/products.js`, and `config/product-attributes.php`.
- Logistics: `resources/views/components/logistics/`, `resources/views/logistics/`, `resources/css/logistics/workspace.css`, and `resources/js/logistics/workspace.js`.
- Admin: `resources/views/admin/registrations/`, `resources/views/admin/user-management/`, and `resources/css/admin/registrations.css`.
- Vite inputs: `vite.config.js`.
