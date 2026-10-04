<?php

namespace App\Features\Auth\SingleSignOn;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/**
 * Minimal OpenID Connect relying party: discovery, authorization code flow
 * with PKCE, and ID token validation against the provider's JWKS.
 */
class OidcClient
{
    /**
     * @return array{url: string, state: string, nonce: string, verifier: string}
     */
    public function authorizationRequest(string $redirectUri): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('hris.sso.client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => config('hris.sso.scopes'),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->base64Url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'url' => $this->discovery()['authorization_endpoint'].'?'.$query,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
        ];
    }

    /**
     * Exchanges the code and returns the validated ID token claims.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function claims(string $code, string $redirectUri, string $verifier, string $nonce): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->withBasicAuth(rawurlencode((string) config('hris.sso.client_id')), rawurlencode((string) config('hris.sso.client_secret')))
            ->post($this->discovery()['token_endpoint'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $verifier,
            ]);

        $idToken = $response->json('id_token');

        if (! $response->successful() || ! is_string($idToken)) {
            throw new RuntimeException('The identity provider did not return an ID token.');
        }

        return $this->validate($idToken, $nonce);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function validate(string $idToken, string $nonce): array
    {
        JWT::$leeway = 60;

        try {
            try {
                $claims = (array) JWT::decode($idToken, $this->keys());
            } catch (UnexpectedValueException $e) {
                // Unknown key id: the provider may have rotated its keys, so refresh once.
                if (! str_contains($e->getMessage(), '"kid"')) {
                    throw $e;
                }

                $claims = (array) JWT::decode($idToken, $this->keys(refresh: true));
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid ID token: '.$e->getMessage(), previous: $e);
        }

        $audiences = (array) ($claims['aud'] ?? []);
        $clientId = (string) config('hris.sso.client_id');

        $checks = [
            'issuer' => ($claims['iss'] ?? null) === $this->discovery()['issuer'],
            'audience' => in_array($clientId, $audiences, true) && (count($audiences) === 1 || ($claims['azp'] ?? null) === $clientId),
            'nonce' => is_string($claims['nonce'] ?? null) && hash_equals($nonce, $claims['nonce']),
            'subject' => is_string($claims['sub'] ?? null) && $claims['sub'] !== '',
        ];

        foreach ($checks as $name => $ok) {
            if (! $ok) {
                throw new RuntimeException("Invalid ID token: {$name} mismatch.");
            }
        }

        return $claims;
    }

    /**
     * @return array{issuer: string, authorization_endpoint: string, token_endpoint: string, jwks_uri: string}
     */
    public function discovery(): array
    {
        $issuer = rtrim((string) config('hris.sso.issuer'), '/');

        /** @var array{issuer: string, authorization_endpoint: string, token_endpoint: string, jwks_uri: string} */
        return Cache::remember('oidc:discovery:'.md5($issuer), 3600, function () use ($issuer) {
            $document = Http::acceptJson()->timeout(10)->get($issuer.'/.well-known/openid-configuration')->throw()->json();

            foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $key) {
                if (! is_string($document[$key] ?? null)) {
                    throw new RuntimeException("OIDC discovery document is missing {$key}.");
                }
            }

            return $document;
        });
    }

    /**
     * @return array<string, Key>
     */
    private function keys(bool $refresh = false): array
    {
        $uri = $this->discovery()['jwks_uri'];
        $key = 'oidc:jwks:'.md5($uri);

        if ($refresh) {
            Cache::forget($key);
        }

        /** @var array{keys: list<array<string, mixed>>} $jwks */
        $jwks = Cache::remember($key, 3600, fn () => Http::acceptJson()->timeout(10)->get($uri)->throw()->json());

        return JWK::parseKeySet($jwks, 'RS256');
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
