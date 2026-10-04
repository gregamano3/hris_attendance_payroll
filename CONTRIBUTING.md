# Contributing

Thanks for your interest in improving HRIS · Attendance · Payroll!

## Workflow

1. **Open an issue** describing the bug or feature (use the templates).
2. **Branch from `master`**: `feat/<issue-number>-<short-slug>` or `fix/<issue-number>-<short-slug>`.
3. **Open a pull request** against `master` with `Closes #<issue-number>` in the description.
4. CI (Pint, Larastan, Pest, Docker build) must be green before merging.
5. PRs are **squash-merged**.

## Local development

```bash
make setup     # first run: builds images, installs deps, migrates & seeds
make up        # start the stack -> http://localhost:8080
make check     # pint + larastan + pest
make e2e       # Playwright end-to-end tests (isolated stack on :8090)
```

Composer, Artisan and npm run inside containers — no local PHP toolchain required.

## Architecture: vertical slices

Code is organised by **feature**, not by technical layer:

```
app/Features/<Feature>/
    <UseCase>/                 one folder per use case (slice)
        <UseCase>Controller.php   invokable controller
        <UseCase>Request.php      validation (FormRequest)
        <UseCase>Action.php       the business logic
    Models/                    Eloquent models owned by the feature
    Queries/                   read models other features may depend on
    Views/                     Blade views, namespaced as "<feature>::"
    routes.php                 the feature's routes
    <Feature>ServiceProvider.php  extends App\Shared\Providers\FeatureServiceProvider
app/Shared/                    truly cross-cutting code only
```

Rules of thumb:

- A slice should be readable top-to-bottom without jumping across the codebase.
- Slices don't call each other. Cross-feature reads go through a feature's `Queries/` classes.
- Register new feature providers in `bootstrap/providers.php`.
- Tests mirror the structure: `tests/Feature/<Feature>/<UseCase>Test.php`.

## Coding standards

- PSR-12 via Laravel Pint (`make fix`).
- Larastan level 6 must pass (`make stan`).
- Money is stored as integer **centavos**.
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `chore:` ...).
