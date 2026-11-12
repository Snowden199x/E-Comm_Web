# Rider App Download (Logistics Landing)

**Status:** Download redirect implemented; the APK is distributed through GitHub Releases. Google Play not set up.
**Reviewed:** 10 November 2026

## Purpose

The Logistics landing page is for registering a logistics **business**. Individual riders apply in the Vendo Rider Android app, so the page carries a separate callout with a button and a QR code. Both must lead the rider to the current APK.

## Current behavior

| Surface | Detail |
| --- | --- |
| Landing callout | `resources/views/logistics/landing.blade.php`, section "Looking to ride, not run a business?" |
| Button | Links to `route('logistics.rider-app')` — "Get the Vendo Rider App" |
| QR image | `public/assets/logistics/rider-app-qr.png`, encodes `https://vendo-ph.app/logistics/get-the-app` |
| Route | `GET /logistics/get-the-app` (`logistics.rider-app`, public, inside the `/logistics` prefix group) |
| Result | `302` to `config('services.vendo.rider_app_url')` |
| Default target | `https://github.com/Snowden199x/E-Comm_Mobile/releases/latest/download/vendo-rider.apk` |
| Override | `RIDER_APP_DOWNLOAD_URL` in `.env` |

The GitHub URL form is deliberate: `releases/latest/download/<asset>` always serves the newest **published** release, so a new build needs no site change. The asset name must therefore stay `vendo-rider.apk`.

## Why a direct APK, not an app store

- Play Console is not set up. A personal developer account created after 13 November 2023 must run a closed test with at least 12 testers opted in for 14 consecutive days before production access; organization accounts with a D-U-N-S number are exempt.
- The APK is 38–107 MB depending on ABI. Vercel's free Hobby plan is non-commercial only and caps static uploads at 100 MB, Render's free plan includes about 5 GB of outbound bandwidth per month, and Cloudflare Pages caps a single file at 25 MiB. GitHub Releases allows 2 GiB per asset with no bandwidth limit, and `Snowden199x/E-Comm_Mobile` is public, so anonymous downloads work.
- Trade-off accepted: riders must allow "install from this source" once, and there is no auto-update until the app is in Play.

## Moving to Google Play later

1. Create the Play Console app with the existing package id `vendoph.rider` and upload the same keystore as the upload key (Play App Signing optional).
2. Complete the store listing, data safety form (location, camera, photos) and privacy policy URL.
3. Change `RIDER_APP_DOWNLOAD_URL` (or the `config/services.php` default) to the Play listing URL. The landing button and the printed QR keep working; only the redirect target changes.

Do not change the package id: it is the app's permanent identity on phones and in Play.

## Gaps

- No release page of our own: a rider who lands on GitHub sees the release notes instead of install steps. A small `/logistics/get-the-app` download page (version, size, SHA-256, install steps) would be the next improvement, with the redirect kept as the fallback.
- No iOS distribution.
- The QR still needs regeneration only if the short URL (`/logistics/get-the-app`) ever changes; it does not need to change per release.

## Source

`routes/web.php` (`logistics.rider-app`), `config/services.php` (`vendo.rider_app_url`), `resources/views/logistics/landing.blade.php`, `public/assets/logistics/rider-app-qr.png`. Mobile build and signing details: `E-Comm_Mobile/docs/features/rider-app-release.md`.
