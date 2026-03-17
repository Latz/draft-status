# GitHub Actions: SonarCloud + Unit Tests — Design Spec

_Date: 2026-03-17_

## Goal

Run PHPUnit unit tests and SonarCloud static analysis automatically on every push to `main`.

## Trigger

```yaml
on:
  push:
    branches: [main]
```

## Workflow File

`.github/workflows/sonarcloud.yml`

## Steps

| # | Step | Action / Command | Notes |
|---|------|-----------------|-------|
| 1 | Checkout | `actions/checkout@v4` | `fetch-depth: 0` — full git history required by SonarCloud |
| 2 | Setup PHP | `shivammathur/setup-php@v2` | PHP 8.1, default extensions |
| 3 | Install dependencies | `composer install --no-interaction --prefer-dist` | Installs PHPUnit and WP Mock |
| 4 | Run unit tests | `vendor/bin/phpunit --testsuite unit` | Fails the build on test failure |
| 5 | SonarCloud analysis | `SonarSource/sonarcloud-github-action@master` | Reads `sonar-project.properties`; runs only if tests pass |

## Secrets

| Secret | Where to add | Notes |
|--------|-------------|-------|
| `SONAR_TOKEN` | GitHub repo → Settings → Secrets and variables → Actions | One-time manual setup |
| `GITHUB_TOKEN` | Provided automatically by GitHub Actions | No setup needed |

## Non-Goals

- Integration tests (require WordPress/MySQL environment — out of scope)
- Pull request triggers (push to `main` only)
- Code coverage reporting

## Files Changed

| File | Change |
|------|--------|
| `.github/workflows/sonarcloud.yml` | New file — CI workflow |
