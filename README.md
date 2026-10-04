# HRIS · Attendance · Payroll

[![CI](https://github.com/gregamano3/hris_attendance_payroll/actions/workflows/ci.yml/badge.svg)](https://github.com/gregamano3/hris_attendance_payroll/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

An open-source Human Resource Information System with attendance tracking and
Philippine payroll (SSS, PhilHealth, Pag-IBIG, BIR withholding tax).

## Stack

- **Laravel 13** (PHP 8.4) + **AdminLTE 4** (Bootstrap 5) UI
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

Sign in with the seeded administrator **admin@example.com / password**
(configurable through `ADMIN_EMAIL` / `ADMIN_PASSWORD`) and change the password immediately.

Outside production the seeder also creates a demo organisation with one account per role
(`hr@example.com`, `payroll@example.com`, `employee@example.com`, all with password `password`).

Run `make help` to see all commands.

## Attendance rules

Raw punches (`time_logs`) are turned into one `attendance_days` row per employee and date by
`App\Features\Attendance\Compute\AttendanceCalculator`. A queued job recomputes affected days whenever
punches, shift assignments, holidays or approved leaves change, and `php artisan attendance:compute` runs nightly.

- **Late** counts only beyond the shift's grace period, and then counts in full.
- **Undertime** is the time left before the shift ends. **Worked** is the scheduled hours minus late and undertime.
- **Overtime** is time after the shift ends, ignored below `ATTENDANCE_OT_THRESHOLD` (default 30 minutes).
- **Night differential** is paid time between 22:00 and 06:00. Overnight shifts are supported.
- On rest days and holidays all time worked counts, and anything beyond the scheduled hours becomes overtime.

## Roles

| Role | Can |
|------|-----|
| Administrator | Everything, including user management |
| Human Resources | Employees, attendance, leave approvals |
| Payroll Officer | Payroll runs, payslips, statutory tables |
| Employee | Clock in/out, leave requests, own payslips |

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
