# HRIS · Attendance · Payroll

[![CI](https://github.com/gregamano3/hris_attendance_payroll/actions/workflows/ci.yml/badge.svg)](https://github.com/gregamano3/hris_attendance_payroll/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

An open-source Human Resource Information System with attendance tracking and
Philippine payroll (SSS, PhilHealth, Pag-IBIG, BIR withholding tax).

## Stack

- **Laravel 13** (PHP 8.4) + **AdminLTE** UI
- **PostgreSQL 17** database, **Redis 7** for cache / sessions / queues
- **Docker Compose** for development, multi-stage production image
- **Vertical slice architecture** — each feature lives in `app/Features/<Feature>`
- **Pest** for unit/feature tests, **Playwright** for end-to-end tests

## Modules

| Module     | Highlights |
|------------|-----------|
| HRIS       | Departments, positions, employee records, government IDs |
| Attendance | Shifts, clock in/out, DTR, late/undertime/OT/night differential, holidays, leaves |
| Payroll    | Semi-monthly runs, PH contributions & withholding tax, payslips |

## Quick start

Requirements: Docker with Compose v2 and `make`.

```bash
git clone git@github.com:gregamano3/hris_attendance_payroll.git
cd hris_attendance_payroll
make setup
```

| Service | URL |
|---------|-----|
| App     | http://localhost:8080 |
| Mailpit | http://localhost:8026 |
| Health  | http://localhost:8080/health |

Run `make help` to see all commands.

## Services

| Container   | Purpose |
|-------------|---------|
| `app`       | PHP-FPM 8.4 (opcache, redis, pdo_pgsql) |
| `web`       | nginx |
| `queue`     | `php artisan queue:work` |
| `scheduler` | `php artisan schedule:work` |
| `db`        | PostgreSQL 17 (`hris`, plus `hris_testing` for the test suite) |
| `redis`     | Redis 7 — cache, sessions, queues |
| `mailpit`   | Local mail catcher |
| `node`      | Vite dev server (`docker compose --profile dev up node`) |

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Please read our [Code of Conduct](CODE_OF_CONDUCT.md)
and report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE)
