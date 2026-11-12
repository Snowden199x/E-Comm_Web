<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one Firebase Cloud Messaging (HTTP v1) notification to one device token.
 *
 * push requires a Firebase project: `FIREBASE_CREDENTIALS` points at the service
 * account JSON and `FIREBASE_PROJECT_ID` (or the JSON's project_id) names it. When
 * those are missing, sendToToken() returns "disabled" and the Rider app keeps its
 * 25-second polling fallback. No Composer package is used: the OAuth assertion is
 * signed with OpenSSL and exchanged for a cached access token.
 */
class FirebasePushService
{
    public const SENT = 'sent';
    public const INVALID = 'invalid';
    public const FAILED = 'failed';
    public const DISABLED = 'disabled';

    public function enabled(): bool
    {
        return $this->credentials() !== null && $this->projectId() !== null;
    }

    public function projectId(): ?string
    {
        $configured = trim((string) config('services.firebase.project_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        return $this->credentials()['project_id'] ?? null;
    }

    /** @return array{client_email:string, private_key:string, project_id?:string}|null */
    private function credentials(): ?array
    {
        $path = trim((string) config('services.firebase.credentials', ''));
        // A relative value is resolved from the project root, so the same .env
        // works locally and on the server.
        if ($path !== '' && ! str_starts_with($path, '/')) {
            $path = base_path($path);
        }
        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            return null;
        }

        return $json;
    }

    /** @param  array<string,string>  $data */
    public function sendToToken(string $token, string $title, string $body, array $data = []): string
    {
        $credentials = $this->credentials();
        $project = $this->projectId();
        if ($credentials === null || $project === null) {
            return self::DISABLED;
        }

        $accessToken = $this->accessToken($credentials);
        if ($accessToken === null) {
            Log::warning('Firebase push skipped: no access token could be obtained.');

            return self::FAILED;
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => ['sound' => 'default'],
                    ],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);

        if ($response->successful()) {
            return self::SENT;
        }

        // A token that no longer exists must be dropped, not retried forever.
        $code = (string) ($response->json('error.details.0.errorCode')
            ?? $response->json('error.status')
            ?? '');
        if (in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'], true)) {
            return self::INVALID;
        }

        Log::warning('Firebase push failed.', ['status' => $response->status(), 'code' => $code]);

        return self::FAILED;
    }

    /** @param  array{client_email:string, private_key:string}  $credentials */
    private function accessToken(array $credentials): ?string
    {
        $cached = Cache::get('firebase:access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $encode = fn (array $value) => rtrim(strtr(base64_encode((string) json_encode($value)), '+/', '-_'), '=');
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $key = openssl_pkey_get_private($credentials['private_key']);
        if ($key === false || ! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $assertion = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        $token = $response->successful() ? (string) $response->json('access_token') : '';
        if ($token === '') {
            return null;
        }

        Cache::put('firebase:access_token', $token, 3300);

        return $token;
    }
}
