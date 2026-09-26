# Admin Domain

## Purpose

Platform staff review registrations and catalog compliance, manage accounts and configuration, handle complaints, inspect reports, and communicate platform announcements. Admin login uses its own guard and admin-specific account controls.

## Implemented areas

- Dashboard aggregates orders/sales/users, six-week charts, pending registrations, recent notices, registrations, and complaints.
- Registration review filters pending buyers, sellers, and logistics centers; detail, approval email, and rejection reason/notes are supported.
- User management filters buyer, seller, and logistics-center accounts and supports suspend, deactivate, and reactivate. New logistics-center approval remains in Registrations.
- Seller compliance covers product review, warning/violation lists, seller suspension workflows, and product approve/reject/warn actions.
- Complaints, category commission settings, reports/PDF download, platform policies, announcements, notification listing, chat, and admin account management have controllers/views.

## Boundaries and gaps

Admin dashboard routes and operational routes use the `admin` guard; many operational routes also apply active-admin and force-password middleware. Verify exceptions individually. Current reports and dashboard use order aggregates whose business definitions should be confirmed. Admin login sessions exist, but this is not a complete immutable audit log for every admin action. Push delivery is not established by the notification table alone.

See [feature status](../domain-feature-status.md) and `features/admin/` for detailed specs.
