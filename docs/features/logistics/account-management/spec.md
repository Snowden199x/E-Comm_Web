# Logistics Account Management

**Status:** Registered details and profile-photo controls implemented; owner verification pending.

The approved, active Logistics Center account page shows its registered name, business name, address, contact number, status, and platform policies. The account holder may upload a JPG, PNG, or WebP profile photo up to 2 MB, or remove the current photo. Registered business identity and address remain read-only.

The routes `logistics.account.avatar` and `logistics.account.avatar.remove` are under the existing authenticated, approved, active Logistics Center middleware. The server also checks the user's role when changing the photo. A successful replacement or removal updates `users.profile_picture` and creates a platform notification for Admin in one database transaction. Admin User Management shows the current image and its notification links to the account search. A no-op removal creates no notification. Previously stored profile photos under `profile-pictures/` are removed after a successful change; registration documents are unaffected.

**Code:** `app/Http/Controllers/Logistics/AccountController.php`, `app/Services/ProfilePhotoService.php`, `resources/views/logistics/account.blade.php`, `resources/css/logistics/workspace.css`.
