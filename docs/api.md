# REST API

Integrations and mobile apps can use a JSON API under `/api/v1`. It is described by an
OpenAPI 3.1 document at **`/api/v1/openapi.yaml`**. Import it into Postman, Insomnia or
Swagger UI, or use it to generate a client.

## Tokens

1. Sign in and open **My account → Manage API tokens**.
2. Name the token, pick its scopes and an expiry (up to `API_MAX_TOKEN_DAYS`, 365 by default).
3. Copy the token. It is shown only once; only a hash is stored.

Send the token on every request:

```bash
curl -H "Authorization: Bearer 12|hris_xxxxxxxx" -H "Accept: application/json" \
     https://hris.example.com/api/v1/me
```

Revoke a token on the same page. Deactivating a user blocks all of that user's tokens immediately.
The web login session never authenticates API calls, so the API always uses bearer tokens.

## Scopes and permissions

A call succeeds only when the token has the scope **and** the owner's role allows it.
For example, an `employees:read` token created by an HR user stops working if that user
loses the HR role.

| Scope | Endpoints | Notes |
|-------|-----------|-------|
| `profile:read` | `GET /me` | Account, role, permissions, linked employee |
| `employees:read` | `GET /employees`, `GET /employees/{id}` | Requires `employees.view`. Never returns government IDs, bank details, pay, birth date or address |
| `attendance:read` | `GET /attendance/days`, `GET /attendance/time-logs` | Own record by default; `employee_id` needs `attendance.view` or a team member. Max 62-day range |
| `attendance:write` | `POST /attendance/time-logs` | Own punch at server time; branch IP/geofence rules apply |
| `leaves:read` | `GET /leave-types`, `GET /leave-requests` | Own requests and balances |
| `leaves:write` | `POST /leave-requests`, `POST /leave-requests/{id}/cancel` | Same validation as the web form; leaves that need a document must be filed on the web |
| `payslips:read` | `GET /payslips`, `GET /payslips/{id}` | Own payslips from finalized runs only |

Amounts are decimal strings in PHP (e.g. `"15000.00"`). Dates are `YYYY-MM-DD` and timestamps are ISO 8601.

## Errors and limits

| Status | Meaning |
|--------|---------|
| 401 | Missing, invalid or expired token |
| 403 | Missing scope, missing permission or deactivated account |
| 404 | Not found, or not yours |
| 409 | Conflicting state (e.g. cancelling a non-pending leave) |
| 422 | Validation failed (`errors` object per field) |
| 429 | Rate limited; wait `Retry-After` seconds |

Requests are limited per user (`API_RATE_LIMIT`, 60/minute by default). Set `API_ENABLED=false`
to turn the API off entirely.

## Biometric devices

Devices push punches to a separate endpoint using a **device token**. These tokens are created under
**Attendance → Devices**, not with personal access tokens:

```http
POST /api/attendance/punches
Authorization: Bearer <device token>
Content-Type: application/json

{"punches": [{"employee_no": "EMP-0001", "timestamp": "2026-10-05T08:02:00+08:00", "type": "in"}]}
```

`type` is one of `in`, `out`, `break_out` or `break_in`. A batch holds up to 500 punches, and re-sent
punches are counted as `duplicates` instead of being saved twice. The response lists `accepted`,
`duplicates` and `rejected` (each rejected item has its index and reason).
