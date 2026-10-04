# Production deployment

The production stack (`compose.prod.yaml`) runs the application image built from `docker/php/Dockerfile`:

| Service | Purpose |
|---------|---------|
| `web` | Caddy: automatic HTTPS (Let's Encrypt) for `APP_DOMAIN`, security headers, static assets, FastCGI to `app` |
| `app` | PHP-FPM with opcache; config/route/view caches built at start (`php artisan optimize`) |
| `horizon` | Redis queue workers + dashboard at `/horizon` (administrators only) |
| `scheduler` | `schedule:work`: nightly attendance computation, leave accrual, salary changes |
| `db` | PostgreSQL 17 (volume `pgdata`) |
| `redis` | Redis 7 with a password and AOF persistence |
| `backup` | Daily compressed `pg_dump` with retention (`BACKUP_AT`, `BACKUP_RETENTION_DAYS`) to `./backups` |

## First install

```bash
git clone https://github.com/gregamano3/hris_attendance_payroll.git && cd hris_attendance_payroll
cp .env.production.example .env.production   # fill in every value
docker compose -f compose.prod.yaml run --rm --no-deps app php artisan key:generate --show   # paste into APP_KEY
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml exec app php artisan migrate --force
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=RolesAndPermissionsSeeder --force
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=AttendanceSeeder --force
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=PayrollSeeder --force
docker compose -f compose.prod.yaml exec app php artisan tinker   # create the first administrator, or set ADMIN_EMAIL/ADMIN_PASSWORD and run DatabaseSeeder
```

Point the domain's DNS A/AAAA records at the server and open ports 80/443; Caddy obtains the certificate automatically.

## Keys — back them up separately

- `APP_KEY` encrypts PII, 2FA secrets and documents. **Without it the data cannot be decrypted.**
- `BLIND_INDEX_KEY` keys the government ID blind indexes.

Store both in a password manager / secrets vault, not next to the backups. Rotation: see [SECURITY.md](../SECURITY.md).

## Updating

```bash
git pull
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml exec app php artisan migrate --force
docker compose -f compose.prod.yaml exec horizon php artisan horizon:terminate   # workers restart with the new code
```

## Backups and restore

- Dumps land in `./backups` (override with `BACKUP_PATH`); copy them off the server (object storage, another host).
- Ad-hoc backup: `docker compose -f compose.prod.yaml run --rm backup once`
- Restore: `docker compose -f compose.prod.yaml run --rm -it --entrypoint /scripts/restore.sh backup /backups/<file>.sql.gz`
- Uploaded documents live in the `storage` volume (already encrypted); include it in volume snapshots.
- Test a restore regularly.

## Monitoring

- `GET /health` returns `{"status":"ok"}` with database and cache checks (HTTP 503 when degraded); point an uptime monitor at it.
- Logs go to stdout/stderr in JSON (Caddy) and text (Laravel): `docker compose -f compose.prod.yaml logs -f`. Ship them to your log platform with a Docker logging driver.
- Queue health, throughput and failed jobs: `/horizon` (administrators).
- Error tracking: set `LOG_STACK`/`LOG_CHANNEL` to a channel of your provider (e.g. `slack`, `papertrail`) in `.env.production`; failures are also visible in Horizon.

## Hardening checklist

- `APP_DEBUG=false` (forced by compose), `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
- `REQUIRE_2FA_ROLES=admin,payroll`
- Strong unique `DB_PASSWORD` and `REDIS_PASSWORD`; database and Redis are not published to the host
- Firewall: only 22, 80, 443
- Verify statutory rates, holidays and file formats before the first payroll (see README and `docs/file-formats.md`)
