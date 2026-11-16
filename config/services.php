<?php

/*
| Google sign-in allows more than one client ID per platform: every Android
| package (Buyer, Rider) registers its own OAuth client, so these env values
| take comma-separated lists.
*/
$clientIds = static function (?string $value): array {
    return array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $value)))));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'geoapify' => [
        // Browser key for Geoapify map tiles and geocoding. Restrict it to this site's referrers.
        'key' => env('GEOAPIFY_API_KEY'),
    ],

    'google' => [
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        // The mobile apps sign in against the web client above (it is what the
        // Android serverClientId takes), so an Android token carries that web
        // ID as `aud` and the package's own Android client as `azp`; the
        // verifier requires both to be accepted. Each Android package
        // (com.example.vendo_buyer, vendoph.rider) has its own client in the
        // same Google Cloud project, hence comma-separated lists.
        'android_client_ids' => $clientIds(env('GOOGLE_ANDROID_CLIENT_ID')),
        'ios_client_ids' => $clientIds(env('GOOGLE_IOS_CLIENT_ID')),
        // CA bundle for the TLS calls that verify Google ID tokens. Windows PHP
        // builds often ship without curl.cainfo, which makes every Google key
        // fetch fail with "cURL error 60". Leave empty to use the system store.
        'ca_bundle' => env('GOOGLE_CA_BUNDLE'),
        // GOOGLE_ALLOWED_CLIENT_IDS (comma separated) still overrides all of it.
        'allowed_client_ids' => $clientIds(env('GOOGLE_ALLOWED_CLIENT_IDS')) ?: $clientIds(implode(',', array_filter([
            env('GOOGLE_WEB_CLIENT_ID'),
            env('GOOGLE_ANDROID_CLIENT_ID'),
            env('GOOGLE_IOS_CLIENT_ID'),
        ]))),
    ],

    'vendo' => [
        // Where the buyer landing page's "Get the app" button and its printed QR
        // send a shopper. The URL is pinned to the Buyer release tag: GitHub
        // resolves releases/latest per repository, not per app, so publishing
        // either app moves "latest" and the other app's asset disappears from it.
        // Point it at Google Play once the Buyer app ships.
        'buyer_app_url' => env(
            'BUYER_APP_DOWNLOAD_URL',
            'https://github.com/Snowden199x/E-Comm_Mobile/releases/download/buyer-v1.0.0/vendo-buyer.apk',
        ),
        // Where the buyer-facing landing sends someone who wants the Rider app.
        // Pinned to the Rider tag for the same reason as the Buyer URL above.
        // Bump the tag (or set RIDER_APP_DOWNLOAD_URL) when a new Rider build ships.
        'rider_app_url' => env(
            'RIDER_APP_DOWNLOAD_URL',
            'https://github.com/Snowden199x/E-Comm_Mobile/releases/download/rider-v1.0.0/vendo-rider.apk',
        ),
    ],

    'firebase' => [
        // Service account JSON from the Firebase console (Project settings › Service accounts).
        // Without it, push is disabled and the Rider app keeps its polling fallback.
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
