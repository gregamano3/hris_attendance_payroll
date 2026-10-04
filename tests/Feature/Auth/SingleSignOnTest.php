<?php

use App\Models\User;
use App\Shared\Authorization\Role;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

const ISSUER = 'https://idp.example.test/realms/acme';

beforeEach(function () {
    config(['hris.sso' => [
        'enabled' => true, 'label' => 'Sign in with Acme', 'issuer' => ISSUER, 'client_id' => 'hris', 'client_secret' => 's3cret',
        'scopes' => 'openid email profile', 'require_verified_email' => true, 'allowed_domains' => [], 'auto_provision' => false, 'default_role' => 'employee',
    ]]);

    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $this->privateKey);
    $rsa = openssl_pkey_get_details($key)['rsa'];
    $b64 = fn (string $v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
    $this->jwk = ['kty' => 'RSA', 'kid' => 'k1', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($rsa['n']), 'e' => $b64($rsa['e'])];
    $this->idTokenClaims = [];
    $this->kid = 'k1';

    Http::fake([
        ISSUER.'/.well-known/openid-configuration' => Http::response([
            'issuer' => ISSUER, 'authorization_endpoint' => ISSUER.'/auth', 'token_endpoint' => ISSUER.'/token', 'jwks_uri' => ISSUER.'/certs',
        ]),
        ISSUER.'/certs' => fn () => Http::response(['keys' => [$this->jwk]]),
        ISSUER.'/token' => fn () => Http::response(['access_token' => 'at', 'token_type' => 'Bearer', 'id_token' => JWT::encode($this->idTokenClaims, $this->privateKey, 'RS256', $this->kid)]),
    ]);
});

/**
 * Starts the flow, lets the "provider" sign the given claims and hits the callback.
 *
 * @param  array<string, mixed>  $claims
 */
function ssoLogin($test, array $claims): TestResponse
{
    $redirect = $test->get('/auth/sso/redirect')->assertRedirect()->headers->get('Location');
    parse_str((string) parse_url((string) $redirect, PHP_URL_QUERY), $query);

    $test->idTokenClaims = [
        'iss' => ISSUER, 'aud' => 'hris', 'sub' => 'user-123', 'iat' => time(), 'exp' => time() + 300,
        'nonce' => $query['nonce'], 'email' => 'juan@acme.ph', 'email_verified' => true, 'name' => 'Juan dela Cruz',
        ...$claims,
    ];

    return $test->get('/auth/sso/callback?'.http_build_query(['code' => 'abc', 'state' => $query['state']]));
}

it('shows the SSO button only when enabled and sends a PKCE authorization request', function () {
    $this->get('/login')->assertOk()->assertSee('Sign in with Acme');

    $location = $this->get('/auth/sso/redirect')->assertRedirect()->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith(ISSUER.'/auth?')
        ->and($query)->toMatchArray(['response_type' => 'code', 'client_id' => 'hris', 'code_challenge_method' => 'S256', 'redirect_uri' => route('sso.callback')])
        ->and($query['state'])->toHaveLength(40);

    config(['hris.sso.enabled' => false]);
    $this->get('/login')->assertDontSee('Sign in with Acme');
    $this->get('/auth/sso/redirect')->assertNotFound();
});

it('links an existing user by verified email and then by subject', function () {
    $user = userWithRole(Role::Employee, ['email' => 'Juan@acme.ph']);

    ssoLogin($this, [])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->oidc_subject)->toBe('user-123')->and($user->fresh()->last_login_at)->not->toBeNull();

    Http::assertSent(fn ($request) => $request->url() === ISSUER.'/token'
        && $request['grant_type'] === 'authorization_code' && strlen((string) $request['code_verifier']) === 64
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('hris:s3cret')));

    // Later the email at the provider changes; the subject still matches.
    auth()->logout();
    ssoLogin($this, ['email' => 'juan.delacruz@acme.ph'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('refuses unknown, unverified, foreign-domain, conflicting and inactive users', function () {
    ssoLogin($this, [])->assertRedirect('/login')->assertSessionHas('error', fn ($m) => str_contains($m, 'No account exists'));
    $this->assertGuest();

    userWithRole(Role::Employee, ['email' => 'juan@acme.ph']);
    ssoLogin($this, ['email_verified' => false])->assertSessionHas('error', fn ($m) => str_contains($m, 'verified email'));

    config(['hris.sso.allowed_domains' => ['example.com']]);
    ssoLogin($this, [])->assertSessionHas('error', fn ($m) => str_contains($m, 'email domain'));
    config(['hris.sso.allowed_domains' => ['acme.ph']]);

    User::query()->where('email', 'juan@acme.ph')->update(['oidc_subject' => 'someone-else']);
    ssoLogin($this, [])->assertSessionHas('error', fn ($m) => str_contains($m, 'different single sign-on identity'));

    User::query()->where('email', 'juan@acme.ph')->update(['oidc_subject' => 'user-123', 'is_active' => false]);
    ssoLogin($this, [])->assertSessionHas('error', fn ($m) => str_contains($m, 'deactivated'));
    $this->assertGuest();
});

it('rejects tampered tokens, wrong audience, wrong nonce and bad state', function () {
    userWithRole(Role::Employee, ['email' => 'juan@acme.ph']);

    ssoLogin($this, ['aud' => 'another-app'])->assertSessionHas('error', fn ($m) => str_contains($m, 'Single sign-on failed'));
    ssoLogin($this, ['iss' => 'https://evil.test'])->assertSessionHas('error', fn ($m) => str_contains($m, 'Single sign-on failed'));
    ssoLogin($this, ['nonce' => 'replayed'])->assertSessionHas('error', fn ($m) => str_contains($m, 'Single sign-on failed'));
    ssoLogin($this, ['exp' => time() - 3600])->assertSessionHas('error', fn ($m) => str_contains($m, 'Single sign-on failed'));

    // Signed with a key the provider does not publish.
    $realKey = $this->privateKey;
    openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]), $this->privateKey);
    ssoLogin($this, [])->assertSessionHas('error', fn ($m) => str_contains($m, 'Single sign-on failed'));
    $this->privateKey = $realKey;
    $this->assertGuest();

    $this->get('/auth/sso/callback?code=abc&state=forged')->assertRedirect('/login')->assertSessionHas('error', fn ($m) => str_contains($m, 'expired or is invalid'));
    $this->get('/auth/sso/callback?error=access_denied')->assertSessionHas('error', fn ($m) => str_contains($m, 'cancelled'));
    $this->assertGuest();
});

it('refreshes the provider keys after a key rotation', function () {
    userWithRole(Role::Employee, ['email' => 'juan@acme.ph']);
    ssoLogin($this, [])->assertRedirect(route('dashboard'));
    auth()->logout();

    // The provider rotates to a new key with a new kid; the cached JWKS lacks it.
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $this->privateKey);
    $b64 = fn (string $v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
    $rsa = openssl_pkey_get_details($key)['rsa'];
    $this->jwk = ['kty' => 'RSA', 'kid' => 'k2', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($rsa['n']), 'e' => $b64($rsa['e'])];
    $this->kid = 'k2';

    ssoLogin($this, [])->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('auto-provisions users when enabled', function () {
    config(['hris.sso.auto_provision' => true, 'hris.sso.allowed_domains' => ['acme.ph']]);

    ssoLogin($this, [])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'juan@acme.ph')->sole();
    expect($user->name)->toBe('Juan dela Cruz')->and($user->hasRole(Role::Employee->value))->toBeTrue()->and($user->oidc_subject)->toBe('user-123');
});

it('still requires the two-factor challenge for users who enabled it', function () {
    $user = userWithRole(Role::Employee, ['email' => 'juan@acme.ph']);
    $user->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();

    ssoLogin($this, [])->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

it('lets admins unlink an SSO identity', function () {
    $user = userWithRole(Role::Employee);
    $user->forceFill(['oidc_subject' => 'user-123'])->save();

    $this->actingAs(userWithRole(Role::Admin))->get("/users/{$user->id}/edit")->assertSee('Unlink');
    $this->actingAs(userWithRole(Role::Admin))->delete("/users/{$user->id}/sso")->assertRedirect();
    expect($user->fresh()->oidc_subject)->toBeNull();

    $this->actingAs(userWithRole(Role::Employee))->delete("/users/{$user->id}/sso")->assertForbidden();
});
