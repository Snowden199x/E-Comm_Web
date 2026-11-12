# Seller Account Management

**Status:** Implemented in code; manual verification pending.

## Current records

`users` holds the seller's name, email, phone number, password, status and `profile_picture`. `seller_details` holds legal identity, address, business name, permit, shop description and banner path. Account Management lets the seller edit contact and shop presentation fields. Identity documents and permits are private review material, not shop content. The pickup address and optional Geoapify pin can be changed from the account page; the phone input follows the 11-digit `09XXXXXXXXX` format.

## Page and actions

Show a shop header with the saved banner, profile image, business name and account status. Use a neutral placeholder when an image is absent. The seller can preview how the public-facing header will look before saving. Below it, group contact information, business information, security and approval status. Explain which details are public and which are used only for verification.

An active seller can change their contact number, profile image, shop banner and optional shop description. Each section saves independently. Saved images appear in the account page and topbar without a new login. Password changes require the current password and a confirmed new password.
Replacing or removing the seller profile photo creates a platform notification for Admin, linked to this account in User Management. The Admin list and profile modal show the current photo. Removing a missing photo does not create a change notification.
Unsubmitted editable account fields survive a same-tab reload for up to two hours. Passwords and image files are excluded. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

Email, legal name, registered business name, and permit remain read-only identity records. Seller registration and Account Management provide a Geoapify address picker; the Seller can update the pickup address and map pin used for future routing. The browser shows a formatted address, not coordinates, and the server validates province, municipality and barangay against the PSGC catalog. Approved selling categories are read-only, but Sellers can submit a separate Admin-reviewed request for additional top-level categories with a private supporting permit; see [Category Requests](../category-requests/spec.md).

The public shop header may expose the shop name, description, avatar, banner and broad location such as city/province. Never expose the valid ID, permit file, birthday, exact street address, private phone number or account email by default. Show the account's pending, active, suspended or rejected state using the stored status; do not give the seller a control to set it.

## Media and storage

The avatar uses `users.profile_picture`; shop content uses `seller_details.shop_banner_path` and `seller_details.shop_description`. The account page previews selected images before upload. The server accepts JPEG, PNG and WebP images, with a 2 MB avatar and 4 MB banner limit. It stores paths in the database and removes replaced shop images after the new upload succeeds. Profile-photo updates and Admin notifications are committed in one database transaction; the previous profile photo is removed after commit. Verification documents stay out of this media workflow.

## Backend contract

`GET /seller/account`, `PATCH /seller/account`, `POST /seller/account/avatar`, `DELETE /seller/account/avatar`, `POST /seller/account/banner`, `DELETE /seller/account/banner` and `PATCH /seller/account/password` run under `auth` and `EnsureActiveSeller`. Every lookup uses the authenticated seller. Profile updates validate the submitted locality against the PSGC catalog and save the address and optional Geoapify pin to that Seller's own `seller_details` row. Editable fields are whitelisted, and the current password is required for password changes.

Category requests use a separate review table, reviewer and timestamps. Approved categories are attached only after the Admin decision, and the Seller sees each request's status. Other account-field change requests are not yet implemented.

**Related:** [Seller domain](../../../domains/Seller.md) · [Seller Messages](../chat-messaging/spec.md) · [Admin account review](../../admin/manage-account-registration/spec.md)

## 9 November 2026 update

Routes, guards and field names are unchanged. The page is rebuilt as tabs (profile, password, appearance and the rest of the account), with photo and banner preview, character counters, live password rules and banner-based messages. `AccountController` now returns fix-oriented validation messages for the phone format, shop description length, address, map pin, avatar and banner type/size, password rules and a wrong current password; success copy is shorter ("Changes saved", "Photo saved", "Banner saved"). The address map on this page loads a scoped Tailwind build (`resources/css/seller/map-tailwind.css`, config `tailwind.seller-map.config.js`, preflight off and reset scoped to `.ac-map`) plus `resources/js/seller/map-alpine.js`, because the Seller layout does not start Alpine. Both files are Vite build inputs in `vite.config.js`, so the production manifest contains them; before the fix the page threw a Vite manifest error under a production build while `npm run dev` still worked. See the [10 November progress log](../../../logs/PROGRESS-2026-11-10.md).

## 11 November 2026 account deletion request

The *Security* tab now has a **Delete account** card that opens a request dialog (required reason, optional comment up to 500 characters, required for *Other*). It posts to `seller.account.deletion-request` and closes on cancel, ×, Escape or an outside click. The shop stays open while the request waits for Admin review, and the card shows its sent date. See [account deletion request](../../shared/account-deletion-request/spec.md).
