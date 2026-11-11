# Rider Push Notifications

**Status:** Implemented in code (server token registry + FCM HTTP v1 sender; Flutter token registration); owner Firebase setup and device verification pending
**Reviewed:** 11 November 2026

Rider notifications already exist in the in-app feed and the app polls them every 25 seconds. Push adds an OS-level alert when the app is closed, using **Firebase Cloud Messaging**. Polling stays in place as the fallback, so a build without Firebase still works.

## Server

- **`push_devices`** (`user_id`, unique `token`, `platform`, `last_seen_at`) keeps one row per app install; migration `2026_11_11_183000_create_push_devices_table.php`.
- **`POST /api/v1/rider/push-tokens`** (authenticated Rider) registers or refreshes a token. A token belongs to one account at a time, so signing in on the same phone moves it to the newest Rider. **`DELETE /api/v1/rider/push-tokens`** removes it; the app calls this before logout.
- When a **`Notification`** row is created for a `courier` user, `SendPushNotification` is queued after commit. The job reads the notification row and sends one FCM message per registered device with the title, body and `notification_id`/`type`/`link` data. `UNREGISTERED`/mismatched tokens are deleted instead of retried.
- **`FirebasePushService`** signs an OAuth assertion with the service-account private key (OpenSSL), caches the access token for 55 minutes, and posts to `https://fcm.googleapis.com/v1/projects/{project}/messages:send`. No Composer package is added.
- **Disabled mode:** with no credentials the service reports `disabled`, the notification hook skips the job, and only polling delivers the message.
- **Credential path:** `FIREBASE_CREDENTIALS` may be absolute or relative to the project root, so the same `.env` line works on a workstation and a server.

## App (vendo_rider)

- `firebase_core` and `firebase_messaging` were added to `pubspec.yaml`.
- `RiderPushService.start()` runs after the dashboard opens (post sign-in): asks permission, reads the FCM token, registers it with the API, listens for token refresh, refreshes the notification feed on a foreground push, and turns a tapped push into a feed refresh. Failures are swallowed, so the app never blocks on push.
- `main.dart` registers `riderPushBackgroundHandler`; the system tray draws background/terminated notifications from the `notification` payload.
- Android declares `POST_NOTIFICATIONS` (Android 13+). iOS needs the APNs key and push entitlement from the owner.

## Owner setup required

1. Firebase project **vendorider**, Android app package `vendoph.rider` (matches `applicationId`). The downloaded `google-services.json` lives at `vendo_rider/android/app/google-services.json` (gitignored — a fresh clone must supply its own), and the `com.google.gms.google-services` Gradle plugin is applied in `android/settings.gradle.kts` and `android/app/build.gradle.kts`, so a normal `flutter build apk` picks up the options without extra flags. (`--dart-define` options still override the file if ever needed.)
2. Download the service account JSON from the same project (Project settings › Service accounts). Store it at `storage/app/private/firebase/vendorider.json` — that folder is already gitignored by `storage/app/private/.gitignore` — and set `FIREBASE_CREDENTIALS=storage/app/private/firebase/vendorider.json` plus `FIREBASE_PROJECT_ID=vendorider` in `.env`. A relative value is resolved from the project root; an absolute path also works and is safer when `storage/` is not persistent. Because the key is never committed, a deployment that builds from git must transfer the file separately (secret file, scp/rsync, or an environment-specific mount), and hosts that reset `storage/` must re-upload it.
4. Run a queue worker (`php artisan queue:work`) unless `QUEUE_CONNECTION=sync`, so pushes do not slow the request that created the notification.
5. For iOS, upload the APNs auth key in Firebase and enable the Push Notifications capability.

## Limits

- Courier notifications only; Buyer, Seller, Admin and Transport Driver pushes are not sent yet.
- One FCM token per install; reinstall or a cleared app data produces a new token on next sign-in.
- No topics, scheduling, retry backoff beyond the queue's three tries, per-category opt-out, or delivery analytics.
- The payload carries the same title/body already stored in the notification row (which can include an order number); it is not end-to-end encrypted, so do not put documents, addresses or personal data in notification text.
- If the phone denies notification permission, only polling works.

## Source evidence

`app/Http/Controllers/Api/RiderPushTokenController.php`, `app/Models/Communication/PushDevice.php`, `app/Services/FirebasePushService.php`, `app/Jobs/SendPushNotification.php`, `app/Models/Communication/Notification.php`, `config/services.php`, `routes/api.php`; mobile: `vendo_rider/lib/core/push/rider_push_service.dart`, `vendo_rider/lib/core/api/rider_api.dart`, `vendo_rider/lib/main.dart`, `vendo_rider/lib/features/dashboard/screens/navigation_bar.dart`.

## Related documentation

See [rider scan API](../scan-api/spec.md), [Rider integration](../../../../E-Comm_Mobile/docs/features/rider-integration.md), and [domain status](../../../domain-feature-status.md).
