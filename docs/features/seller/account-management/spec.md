# Seller Account Management

**Status:** Implemented in code; manual verification pending.

## Current records

`users` holds the seller's name, email, phone number, password, status and `profile_picture`. `seller_details` holds legal identity, address, business name, permit, shop description and banner path. Account Management lets the seller edit contact and shop presentation fields. Identity documents and permits are private review material, not shop content.

## Page and actions

Show a shop header with the saved banner, profile image, business name and account status. Use a neutral placeholder when an image is absent. The seller can preview how the public-facing header will look before saving. Below it, group contact information, business information, security and approval status. Explain which details are public and which are used only for verification.

An active seller can change their contact number, profile image, shop banner and optional shop description. Each section saves independently. Saved images appear in the account page and topbar without a new login. Password changes require the current password and a confirmed new password.

Email, legal name, registered business name, permit, address and selected categories affect identity or approval. The page shows their current values without an edit control and directs corrections to Vendo Support. An admin-reviewed change request remains future work.

The public shop header may expose the shop name, description, avatar, banner and broad location such as city/province. Never expose the valid ID, permit file, birthday, exact street address, private phone number or account email by default. Show the account's pending, active, suspended or rejected state using the stored status; do not give the seller a control to set it.

## Media and storage

The avatar uses `users.profile_picture`; shop content uses `seller_details.shop_banner_path` and `seller_details.shop_description`. The account page previews selected images before upload. The server accepts JPEG, PNG and WebP images, with a 2 MB avatar and 4 MB banner limit. It stores paths in the database and removes replaced shop images after the new upload succeeds. Verification documents stay out of this media workflow.

## Backend contract

`GET /seller/account`, `PATCH /seller/account`, `POST /seller/account/avatar`, `DELETE /seller/account/avatar`, `POST /seller/account/banner`, `DELETE /seller/account/banner` and `PATCH /seller/account/password` run under `auth` and `EnsureActiveSeller`. Every lookup uses the authenticated seller. Editable fields are whitelisted, and the current password is required for password changes.

If a reviewed-change workflow is introduced, store the proposed values separately with requester, review status, reviewer and timestamps. Apply approved values only after admin action and keep an audit trail. The seller should see the pending request and its decision.

**Related:** [Seller domain](../../../domains/Seller.md) · [Seller Messages](../chat-messaging/spec.md) · [Admin account review](../../admin/manage-account-registration/spec.md)
