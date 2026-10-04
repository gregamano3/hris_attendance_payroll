# Project guide for AI agents

- Laravel 13 + AdminLTE, PostgreSQL, Redis, Docker. Vertical slice architecture — see CONTRIBUTING.md.
- No PHP/Composer toolchain on the host: run everything through Docker (`make artisan cmd=...`, `make composer cmd=...`, `make check`).
- Workflow for every change: GitHub issue → branch from `master` (`feat/<n>-<slug>`) → PR with `Closes #<n>` → squash merge. Assign issues and PRs to `@me`.
- Money is integer centavos. Payroll follows Philippine rules; statutory rates live in seeded, effective-dated tables, not code.
