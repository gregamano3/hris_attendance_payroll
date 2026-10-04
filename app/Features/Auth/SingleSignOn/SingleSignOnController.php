<?php

namespace App\Features\Auth\SingleSignOn;

use App\Features\Auth\TwoFactorChallenge\TwoFactorChallengeController;
use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * "Sign in with <provider>" via OpenID Connect. Existing users are linked by
 * their verified email on first login and by subject afterwards. New users
 * are only created when OIDC_AUTO_PROVISION is on.
 */
class SingleSignOnController
{
    private const SESSION_KEY = 'oidc.request';

    public function __construct(private OidcClient $client) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(config('hris.sso.enabled'), 404);

        try {
            $auth = $this->client->authorizationRequest(route('sso.callback'));
        } catch (Throwable $e) {
            Log::warning('OIDC discovery failed', ['error' => $e->getMessage()]);

            return $this->fail('Single sign-on is unavailable right now. Please try again later.');
        }

        $request->session()->put(self::SESSION_KEY, [
            'state' => $auth['state'], 'nonce' => $auth['nonce'], 'verifier' => $auth['verifier'], 'at' => now()->timestamp,
        ]);

        return redirect()->away($auth['url']);
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(config('hris.sso.enabled'), 404);

        /** @var array{state: string, nonce: string, verifier: string, at: int}|null $pending */
        $pending = $request->session()->pull(self::SESSION_KEY);

        if ($request->filled('error')) {
            return $this->fail('Sign-in was cancelled or denied by the identity provider.');
        }

        if ($pending === null || now()->timestamp - $pending['at'] > 600
            || ! hash_equals($pending['state'], $request->string('state')->toString()) || ! $request->filled('code')) {
            return $this->fail('The sign-in request expired or is invalid. Please try again.');
        }

        try {
            $claims = $this->client->claims($request->string('code')->toString(), route('sso.callback'), $pending['verifier'], $pending['nonce']);
            $user = $this->resolveUser($claims);
        } catch (RuntimeException $e) {
            Log::warning('OIDC sign-in rejected', ['error' => $e->getMessage()]);

            return $this->fail($e instanceof SsoUserException ? $e->getMessage() : 'Single sign-on failed. Please try again or contact an administrator.');
        }

        $request->session()->regenerate();

        // Users with two-factor authentication still complete our challenge.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, ['id' => $user->id, 'remember' => false, 'at' => now()->timestamp]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::guard('web')->login($user);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * @param  array<string, mixed>  $claims
     *
     * @throws SsoUserException
     */
    private function resolveUser(array $claims): User
    {
        $subject = (string) $claims['sub'];
        $user = User::query()->where('oidc_subject', $subject)->first();

        if ($user === null) {
            $email = Str::lower(trim((string) ($claims['email'] ?? '')));

            if ($email === '' || (config('hris.sso.require_verified_email') && ! filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL))) {
                throw new SsoUserException('Your identity provider did not share a verified email address.');
            }

            $domains = (array) config('hris.sso.allowed_domains');

            if ($domains !== [] && ! in_array(Str::after($email, '@'), $domains, true)) {
                throw new SsoUserException('Accounts from this email domain cannot sign in here.');
            }

            $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

            if ($user?->oidc_subject !== null) {
                // The email belongs to a user already linked to another identity.
                throw new SsoUserException('This account is linked to a different single sign-on identity.');
            }

            $user ??= $this->provision($email, (string) ($claims['name'] ?? $email));
            $user->forceFill(['oidc_subject' => $subject])->save();
        }

        if (! $user->is_active) {
            throw new SsoUserException('Your account is deactivated. Contact an administrator.');
        }

        return $user;
    }

    /**
     * @throws SsoUserException
     */
    private function provision(string $email, string $name): User
    {
        if (! config('hris.sso.auto_provision')) {
            throw new SsoUserException('No account exists for '.$email.'. Ask an administrator to create one.');
        }

        $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => Str::password(40), 'is_active' => true]);
        $user->assignRole(Role::tryFrom((string) config('hris.sso.default_role')) ?? Role::Employee);

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
