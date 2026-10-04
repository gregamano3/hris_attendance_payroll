# Single sign-on (OpenID Connect)

Users can sign in with your organization's identity provider (IdP), for example Google Workspace,
Microsoft Entra ID, Keycloak, Okta or Authentik. SSO is off by default, and password login keeps
working alongside it.

## How accounts are matched

1. **First SSO sign-in.** The IdP's verified `email` is matched case-insensitively to an existing
   user. The IdP's subject (`sub`) is then stored on that user.
2. **Later sign-ins** match on the stored subject, so a renamed email at the IdP still works.
3. **No match.** Sign-in is refused unless `OIDC_AUTO_PROVISION=true`. With auto-provisioning on,
   a new user gets the `OIDC_DEFAULT_ROLE` role (default `employee`). HR still links the user to an
   employee record.
4. **Refusals.** Deactivated users are refused. So is an email that is already linked to a
   *different* subject.

Users who turned on two-factor authentication in this app still complete the code challenge after
SSO. Admins can **unlink** an identity from **Users → Edit**; it is linked again on the next SSO
sign-in.

## Configuration

Register a web application or confidential client at your IdP with this redirect URI:

```
https://<your APP_URL>/auth/sso/callback
```

Then set:

```dotenv
OIDC_ENABLED=true
OIDC_LABEL="Sign in with Google"
OIDC_ISSUER=https://accounts.google.com
OIDC_CLIENT_ID=...
OIDC_CLIENT_SECRET=...
OIDC_ALLOWED_DOMAINS=acme.ph            # optional, comma-separated
OIDC_REQUIRE_VERIFIED_EMAIL=true
OIDC_AUTO_PROVISION=false
OIDC_DEFAULT_ROLE=employee
```

After changing `.env` in production, run `php artisan config:cache`.

| Provider | `OIDC_ISSUER` |
|----------|---------------|
| Google Workspace | `https://accounts.google.com` (set `OIDC_ALLOWED_DOMAINS` to your domain) |
| Microsoft Entra ID | `https://login.microsoftonline.com/<tenant-id>/v2.0` |
| Keycloak | `https://<host>/realms/<realm>` |
| Okta | `https://<org>.okta.com` |
| Authentik | `https://<host>/application/o/<slug>/` |

**Microsoft Entra ID.** Entra does not send `email_verified`, so with the default
`OIDC_REQUIRE_VERIFIED_EMAIL=true` its users are refused on their first sign-in. To use Entra, set it to
`false`. At the same time, restrict `OIDC_ALLOWED_DOMAINS` to domains verified in your tenant and keep
auto-provisioning off, so a forged `email` claim cannot take over an account.

## Security notes

- Authorization code flow with PKCE (S256), `state` and `nonce`. The request expires after 10 minutes.
- The ID token signature is checked against the IdP's JWKS (discovery and keys are cached for one hour
  and refreshed when an unknown key ID appears). The checks also cover issuer, audience (`azp` when
  there are several audiences), expiry and nonce.
- The client authenticates with `client_secret_basic`. Only `openid email profile` is requested.
- Signing out of this app does not sign the user out of the IdP.
