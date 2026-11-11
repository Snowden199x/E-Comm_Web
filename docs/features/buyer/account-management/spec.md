# Buyer Account

**Status:** Profile, password, and account preferences implemented; owner verification and migration pending
**Reviewed:** 7 October 2026

## Current behavior

Buyer can edit profile/password and upload/remove profile picture and banner. The refreshed single account page groups Profile and Password/security under Account, and Appearance/motion, Notifications, Saved items, and Privacy/policies under Settings. Query-string tabs support direct links to sections. The existing profile/password form routes and fields are unchanged.
Replacing or removing a Buyer profile photo creates a platform notification for Admin linked to the Buyer in User Management. The Admin list and profile modal show the current photo. Removing a missing photo does not create a change notification. Buyer banners do not trigger profile-photo notifications.
Unsubmitted profile fields survive an accidental reload in the same tab for up to two hours. Passwords and image files are excluded. See [form reload recovery](../../shared/form-draft-recovery/spec.md).

Text size, reduced motion, and notification sound are saved to `buyer_settings` by Buyer-scoped endpoints. The Buyer layout reads the account settings before rendering and applies them before first paint. Existing browser-local preferences are imported when no server settings row exists. The Saved items section uses the account-backed list described in [Wishlist](../wishlist/spec.md).

## Gaps and acceptance direction

Notification choices by type and email preference are still unavailable. Address history and a multi-address book are not provided by this page. Settings migration and cross-device behavior need owner verification.

## Source evidence

`app/Http/Controllers/Buyer/AccountController.php`, `app/Http/Controllers/Buyer/PersonalizationController.php`, `app/Services/BuyerPersonalizationService.php`, `resources/views/buyer/account.blade.php`, `resources/views/components/buyer/layout.blade.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).

## 8 November 2026 UI/UX pass

The account page was reworked without changing routes or field names. Tabs are Profile, Password, Appearance, Delivery, Notifications, Favorites and Privacy and terms. Success and failure messages open as banners; each field error says the fix. Photo and banner uploads are checked in the browser (image only, 2 MB photo, 4 MB banner) and show a spinner; their menus are no longer clipped. The password form shows a strength bar and live checks, and its button stays disabled until the form can pass. The delivery select is now radio cards. The Favorites tab summarizes the list and links to the Favorites page. See the [progress log](../../../logs/PROGRESS-2026-11-08.md).

Photo and banner uploads open a crop dialog (drag to reposition, zoom 1x to 4x). The photo saves as a 512x512 circle-framed JPEG and the banner as 1600x400 (4:1), using the same upload routes. Each password field has its own show/hide eye.

## 11 November 2026 account deletion request

Settings → *Privacy and terms* now has a **Delete account** card. It opens a dialog with a required reason and an optional comment (max 500, required for *Other*) and posts to `buyer.account.deletion-request`. The request waits for Admin review; the account stays usable and the card shows the sent date until it is decided. See [account deletion request](../../shared/account-deletion-request/spec.md).
