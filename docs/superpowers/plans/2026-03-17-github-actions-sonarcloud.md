# GitHub Actions: SonarCloud + Unit Tests Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a GitHub Actions workflow that runs PHPUnit unit tests and SonarCloud analysis on every push to `main`.

**Architecture:** A single workflow file triggers on push to `main`, runs `composer install` and PHPUnit unit tests, then runs SonarCloud static analysis. SonarCloud reads the existing `sonar-project.properties` file; no extra configuration is needed in the workflow.

**Tech Stack:** GitHub Actions, `shivammathur/setup-php@v2`, `SonarSource/sonarcloud-github-action@v3`, PHPUnit 9.6, Composer

---

## Chunk 1: Create the workflow file

**Spec:** `docs/superpowers/specs/2026-03-17-github-actions-sonarcloud-design.md`

### Task 1: Create `.github/workflows/sonarcloud.yml`

**Files:**
- Create: `.github/workflows/sonarcloud.yml`

- [ ] **Step 1: Create the workflows directory and file**

Create `.github/workflows/sonarcloud.yml` with the following content:

```yaml
name: SonarCloud

on:
  push:
    branches: [main]

jobs:
  sonarcloud:
    name: Unit Tests & SonarCloud
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run unit tests
        run: vendor/bin/phpunit --testsuite unit

      - name: SonarCloud analysis
        uses: SonarSource/sonarcloud-github-action@v3
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
```

- [ ] **Step 2: Verify the file exists and looks correct**

Run:
```bash
cat .github/workflows/sonarcloud.yml
```
Expected: the full YAML printed with no truncation.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/sonarcloud.yml
git commit -m "ci: add GitHub Actions workflow for unit tests and SonarCloud analysis"
```

- [ ] **Step 4: Add the SONAR_TOKEN secret to GitHub**

Go to: `https://github.com/Latz/draft-status/settings/secrets/actions`

Click **New repository secret**:
- Name: `SONAR_TOKEN`
- Value: your SonarCloud token (get it from https://sonarcloud.io/account/security)

Click **Add secret**.

- [ ] **Step 5: Push and verify the workflow runs**

```bash
git push origin main
```

Then go to `https://github.com/Latz/draft-status/actions` and confirm:
- A new workflow run appears for the push
- The "Unit Tests & SonarCloud" job starts and completes green
