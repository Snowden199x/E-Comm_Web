<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class GoogleIdTokenVerifier
{
    /** @return array<string, mixed> */
    public function verify(string $idToken): array
    {
        $allowedClientIds = config('services.google.allowed_client_ids', []);
        if ($allowedClientIds === []) {
            throw new ServiceUnavailableHttpException(null, 'Google sign-in is not configured.');
        }

        try {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) throw new \UnexpectedValueException('Malformed ID token.');
            $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true, flags: JSON_THROW_ON_ERROR);
            $keyId = is_array($header) ? ($header['kid'] ?? null) : null;
            if (! is_string($keyId) || ($header['alg'] ?? null) !== 'RS256') throw new \UnexpectedValueException('Unsupported ID token header.');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Google could not verify this sign-in. Please try again.',
            ]);
        }

        try {
            $keys = $this->keys();
            if (! isset($keys[$keyId])) {
                Cache::forget('google_oidc_jwks');
                $keys = $this->keys();
            }
        } catch (\Throwable $exception) {
            report($exception);
            throw new ServiceUnavailableHttpException(null, 'Google sign-in verification is temporarily unavailable.');
        }

        try {
            $claims = (array) JWT::decode($idToken, $keys);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Google could not verify this sign-in. Please try again.',
            ]);
        }

        $audiences = is_array($claims['aud'] ?? null) ? $claims['aud'] : [$claims['aud'] ?? null];
        $audiences = array_filter($audiences, 'is_string');
        $authorized = array_intersect($allowedClientIds, $audiences) !== [];
        if (isset($claims['azp'])) {
            $authorized = $authorized && in_array($claims['azp'], $allowedClientIds, true);
        }
        if (count($audiences) > 1) {
            $authorized = $authorized && isset($claims['azp']);
        }

        $issuerValid = in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true);
        $emailVerified = in_array($claims['email_verified'] ?? null, [true, 'true', 1, '1'], true);
        $email = strtolower(trim((string) ($claims['email'] ?? '')));

        if (! $authorized || ! $issuerValid || (int) ($claims['exp'] ?? 0) <= now()->timestamp
            || ! $emailVerified || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw ValidationException::withMessages([
                'credential' => 'Google could not verify this sign-in. Please try again.',
            ]);
        }

        $claims['email'] = $email;

        return $claims;
    }

    /** @return array<string, \Firebase\JWT\Key> */
    private function keys(): array
    {
        $jwks = Cache::remember('google_oidc_jwks', now()->addHours(6), function (): array {
            $discovery = Http::acceptJson()->timeout(10)
                ->get('https://accounts.google.com/.well-known/openid-configuration')
                ->throw()->json();
            $jwksUri = is_array($discovery) ? ($discovery['jwks_uri'] ?? null) : null;
            if (! is_string($jwksUri) || ! str_starts_with($jwksUri, 'https://www.googleapis.com/')) {
                throw new \UnexpectedValueException('Google key endpoint unavailable.');
            }

            $jwks = Http::acceptJson()->timeout(10)->get($jwksUri)->throw()->json();
            if (! is_array($jwks) || ! is_array($jwks['keys'] ?? null)) {
                throw new \UnexpectedValueException('Google signing keys unavailable.');
            }

            return $jwks;
        });

        return JWK::parseKeySet($jwks, 'RS256');
    }
}
