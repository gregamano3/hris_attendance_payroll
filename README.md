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
- **Overtime** is time after the shift ends, ignored below `ATTENDANCE_OT_THRESHOLD` (default 30 minutes). With `ATTENDANCE_OT_REQUIRES_APPROVAL` (default on) only approved overtime is paid, capped at the approved hours.
- **Night differential** is paid time between 22:00 and 06:00. Overnight shifts are supported.
- On rest days and holidays all time worked counts, and anything beyond the scheduled hours becomes overtime.

## Payroll rules

Payroll runs are semi-monthly by default and move through **draft → computed → finalized**. A finalized run is
locked and its payslips become visible to employees. `PayslipCalculator` is pure and covered by hand-computed tests.

| Item | Rule |
|------|------|
| Daily rate | monthly × 12 ÷ `PAYROLL_DAYS_PER_YEAR` (default 261), or the daily rate |
| Monthly-rated | ½ monthly salary − absences − late/undertime, plus premiums not already covered by the salary |
| Daily-rated | hours worked + unworked regular holidays + paid leaves |
| Overtime | 125% on ordinary days; day rate × 130% on rest days and holidays |
| Rest / special day | 130% (150% when a special day falls on a rest day) |
| Regular holiday | 200% (260% on a rest day) |
| Night differential | +10% of the applicable hourly rate |
| SSS / PhilHealth / Pag-IBIG | monthly amount on the monthly-equivalent pay, half deducted per run |
| Withholding tax | BIR TRAIN semi-monthly table on taxable pay after employee contributions |
| Recurring allowances | Added to every run between their dates as taxable, de minimis or non-taxable |
| Loans | SSS / Pag-IBIG / company loans and cash advances deducted each run (capped at the balance). The balance drops when the run is finalized |
| Minimum wage earners | Statutory wages, holiday pay, OT, ND and premiums are tax-exempt; other taxable income is taxed |
| Final pay | Pro-rated 13th month, unused convertible leave (first 10 days tax-exempt), outstanding loans and the tax annualization on separation |
| 13th month pay | Separate yearly run: 1/12 of basic salary from finalized regular runs, tax-exempt up to ₱90,000 |

Statutory parameters (SSS brackets, PhilHealth rate/floor/ceiling, Pag-IBIG, BIR tables) are **effective-dated
records** that can be edited under *Payroll → Statutory rates*, so a rate change doesn't need a release. The seeded
values match the published rates at the time of writing. Verify them against the latest SSS, PhilHealth, HDMF and
BIR issuances.

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

## Testing

```bash
make check   # Pint + Larastan + Pest (unit and feature tests against PostgreSQL)
make e2e     # Playwright end-to-end tests
```

`make e2e` starts a separate Docker Compose project (`hris-e2e`, http://localhost:8090) with its own database
volume, reseeds it and runs the suite in `e2e/`, so your development data is never touched. Stop it with
`make e2e-down`. The same suite runs in GitHub Actions (`.github/workflows/e2e.yml`), which uploads an HTML report.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Please read our [Code of Conduct](CODE_OF_CONDUCT.md)
and report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE)
