# HRIS · Attendance · Payroll

An open-source Human Resource Information System with attendance tracking and
Philippine payroll (SSS, PhilHealth, Pag-IBIG, BIR withholding tax).

## Stack

- **Laravel** + **AdminLTE** UI
- **PostgreSQL** database, **Redis** for cache / sessions / queues
- **Docker** (Compose) for local development and production images
- **Vertical slice architecture** — each feature lives in `app/Features/<Feature>`
- **Pest** for unit/feature tests, **Playwright** for end-to-end tests

## Modules

| Module     | Highlights |
|------------|-----------|
| HRIS       | Departments, positions, employee records, government IDs |
| Attendance | Shifts, clock in/out, DTR, late/undertime/OT/night differential, holidays, leaves |
| Payroll    | Semi-monthly runs, PH contributions & withholding tax, payslips |

> 🚧 Under active development.

## License

[MIT](LICENSE)
