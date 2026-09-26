# User Management

**Status:** Implemented for buyer, seller and logistics-center accounts
**Reviewed:** 26 September 2026

The Admin User Management list and counts include approved, suspended, deactivated, and rejected buyer, seller, and logistics-center accounts. Search, date and role filters can find logistics centers. The profile dialog shows their business and address data. Admin can suspend, deactivate, or reactivate these roles with server-side role/status checks. New logistics-center approval remains in Admin Registrations. Filter navigation reloads the page so the selected user's profile/action dialogs match the visible results.

Rider and admin accounts are outside this screen. Account audit history and private verification-document downloads remain open.

Source: `app/Http/Controllers/Admin/UserManagementController.php`, `resources/views/admin/user-management/`.
