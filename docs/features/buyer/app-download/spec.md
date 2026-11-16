# Buyer App Download (Shopper Landing)

**Status:** Download redirect implemented; the Buyer APK is distributed through GitHub Releases. Google Play not set up.
**Reviewed:** 16 November 2026

## Purpose

The public shopper landing page (`/`, `resources/views/home.blade.php` → `buyer/partials/hero.blade.php`) advertises the Vendo Buyer Android app inside the hero, above the fold. The button and the printed QR must both reach the current buyer APK — and must not reach the Rider app, which is a separate package (`vendoph.rider`).

## Current behavior

| Surface | Detail |
| --- | --- |
| Landing card | `resources/views/buyer/partials/hero.blade.php`, `.hero-app` block under the hero CTAs |
| Button | Links to `route('buyer-app')` — "Get the app" |
| QR image | `public/assets/buyer/buyer-app-qr.png`, encodes `https://vendo-ph.app/get-the-app` |
| Route | `GET /get-the-app` (`buyer-app`, public, root — **not** inside the `/logistics` prefix) |
| Result | `302` to `config('services.vendo.buyer_app_url')` |
| Default target | `https://github.com/Snowden199x/E-Comm_Mobile/releases/download/buyer-v1.0.0/vendo-buyer.apk` |
| Override | `BUYER_APP_DOWNLOAD_URL` in `.env` |

The short path `/get-the-app` exists twice by design: the buyer route at the root and the rider route at `/logistics/get-the-app` (`logistics.rider-app`). They are separate names and separate config keys, so changing one app's distribution target never moves the other.

As with the Rider app, the target is pinned to the Buyer release tag. `releases/latest/download/<asset>` is not usable: GitHub resolves "latest" per repository, and both apps live in `Snowden199x/E-Comm_Mobile`, so the tag changes whenever either app is published and the other app's asset is missing from it. Publishing `buyer-v1.0.0` broke the Rider download exactly that way. Bump the tag in `BUYER_APP_DOWNLOAD_URL` (or the config default) with each Buyer release.

## Moving to Google Play later

1. Create the Play Console app with the existing package id `vendoph.buyer` and upload the same keystore as the upload key.
2. Change `BUYER_APP_DOWNLOAD_URL` (or the `config/services.php` default) to the Play listing URL. The hero button and the printed QR keep working; only the redirect target changes.
3. Regenerate `buyer-app-qr.png` only if the short URL `/get-the-app` ever changes.

Do not change the package id: it is the app's permanent identity on phones and in Play, and the Android OAuth client in Google Cloud is bound to it together with the signing SHA-1.

## Gaps

- No release page of our own: a shopper who lands on GitHub sees release notes instead of install steps.
- The APK must be attached to a published GitHub release under the exact asset name `vendo-buyer.apk`, and that release tag must be the one in `BUYER_APP_DOWNLOAD_URL`; a mismatch lands on a GitHub 404.
- No iOS distribution.
- Google Play not set up: buyers must allow "install from this source" once, and there is no auto-update.

## Source

`routes/web.php` (`buyer-app`), `config/services.php` (`vendo.buyer_app_url`), `resources/views/buyer/partials/hero.blade.php`, `public/assets/buyer/buyer-app-qr.png`. Mobile build and signing details: `E-Comm_Mobile/docs/features/buyer-app.md`.
