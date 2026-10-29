# Logistics Account Management

**Status:** Legacy profile-photo controls and new hierarchy account workflows implemented in code; owner migration and verification pending.

The approved, active Logistics Center account page shows its registered name, business name, address, contact number, status, and platform policies. The account holder may upload a JPG, PNG, or WebP profile photo up to 2 MB, or remove the current photo. Registered business identity and address remain read-only.

The routes `logistics.account.avatar` and `logistics.account.avatar.remove` are under the existing authenticated, approved, active Logistics Center middleware. The server also checks the user's role when changing the photo. A successful replacement or removal updates `users.profile_picture` and creates a platform notification for Admin in one database transaction. Admin User Management shows the current image and its notification links to the account search. A no-op removal creates no notification. Previously stored profile photos under `profile-pictures/` are removed after a successful change; registration documents are unaffected.

**Code:** `app/Http/Controllers/Logistics/AccountController.php`, `app/Services/ProfilePhotoService.php`, `resources/views/logistics/account.blade.php`, `resources/css/logistics/workspace.css`.

## Company / Province Hub / Station Hub portal (23 October 2026)

Province Hub and Station Hub manager provisioning and edits require the same 11-digit Philippine `09XXXXXXXXX` phone format as public account registration.

The authenticated `/logistics/portal/{company|province|station}/account` page reads the actor's own company or facility membership. A Company user may edit company name, personal display name, phone, office address and Geoapify pin. Province, municipality and barangay are canonicalized against PSGC. Province and Station managers may edit their own display name and phone; facility operating addresses remain parent-managed. All three roles may change password by entering the current password and confirming a new password with at least eight characters, letters and numbers. Server authorization resolves identity from the session, and profile writes are transactional. The original Logistics Center account page above remains for legacy navigation.

The portal also has user-scoped notifications and Company-to-facility conversations. These require the `2026_10_23_180000_create_logistics_messages.php` migration and have not been manually verified yet.

Company users can request an email change after entering their current password. A one-use, 30-minute signed link is sent to the new address; the user must be signed in to the same account to confirm it. The old email stays active until confirmation. Email-change requests use `logistics_email_changes` from migration `2026_10_23_210000_create_logistics_account_change_requests.php`.

Company users can upload replacement ID or business-permit files (PDF/JPG/PNG, up to 5 MB). Files are stored on the private local disk. Admin reviews them in the Logistics Documents queue; approval changes the document path used by the Company profile, rejection stores a note, and either decision notifies the Company user. One pending replacement per document type is enforced while holding the Logistics Center row lock. Province/Station managers do not have replacement-document review because the current registration model does not collect separate facility-manager verification documents.

Company registration uses Geoapify to fill its written head-office address and stores an optional map pin internally. The Company profile can update this address and pin; Geoapify coordinates are not shown as editable fields. Province and Station managers can update their own display name, phone and password, while their facility addresses remain parent-managed. Email change currently applies to all three hierarchy roles; replacement-document requests apply to the Company account only.
