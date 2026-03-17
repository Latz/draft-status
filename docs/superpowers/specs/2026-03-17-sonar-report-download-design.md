# Design Spec: SonarCloud Report Download Script
_Date: 2026-03-17_

## Overview

A standalone bash script `bin/sonar-report.sh` that downloads all open issues from SonarCloud via its REST API and writes two output files to the project root: `sonar-report.json` (raw issues array) and `sonar-report.md` (human-readable markdown table). Both files already exist in the repo and are overwritten on each run (they are NOT git-ignored — they are committed output artifacts).

Configuration comes from two sources:
- **`.env`** (project root, git-ignored) — provides `SONAR_TOKEN`
- **`sonar-project.properties`** (project root, already exists) — provides `sonar.projectKey` and `sonar.organization`

The script determines the project root as `$(cd "$(dirname "$0")/.." && pwd)` so it works regardless of the working directory it is invoked from.

## Shell Settings

The script uses `set -euo pipefail`. Because `grep` exits 1 when no match is found, the `grep/cut` parsing for config values is done via process substitution with a fallback (e.g., `grep ... || true`), and empty-value validation is done explicitly after — so missing keys produce the specified error messages rather than a silent `set -e` exit.

## Components

### `bin/sonar-report.sh`

**Responsibilities:**
1. Validate prerequisites (`curl`, `jq` installed; `sonar-project.properties` present and parseable; `.env` present with `SONAR_TOKEN`)
2. Parse `sonar.projectKey` and `sonar.organization` from `sonar-project.properties`:
   ```bash
   PROJECT_KEY=$(grep -E '^sonar.projectKey=' "$ROOT/sonar-project.properties" | cut -d= -f2- | tr -d '[:space:]' || true)
   ORG=$(grep -E '^sonar.organization=' "$ROOT/sonar-project.properties" | cut -d= -f2- | tr -d '[:space:]' || true)
   ```
3. Read `SONAR_TOKEN` from `.env` using safe parsing (no `source`):
   ```bash
   SONAR_TOKEN=$(grep -E '^SONAR_TOKEN=' "$ROOT/.env" | cut -d= -f2- | tr -d '[:space:]' || true)
   ```
4. Validate all three values are non-empty (exit 1 with specific messages if not)
5. Fetch all open issues from SonarCloud API with pagination; separate HTTP status from body:
   ```bash
   RESPONSE=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $SONAR_TOKEN" "$URL")
   HTTP_STATUS=$(echo "$RESPONSE" | tail -1)
   BODY=$(echo "$RESPONSE" | head -n -1)
   ```
   Check `$HTTP_STATUS` >= 400 before passing `$BODY` to `jq`.
6. Write merged issues array to `sonar-report.json` (overwrite)
7. Transform JSON to markdown table and write to `sonar-report.md` (overwrite)

### `.env` (git-ignored)

Contains `SONAR_TOKEN=<token>`. Never committed. Must be created manually by the developer.

### `.env.example`

Documents the required env var: `SONAR_TOKEN=your_token_here`. Committed to the repo (not git-ignored).

## Data Flow

```
.env              → SONAR_TOKEN (grep/cut/tr, || true, then validated non-empty)
sonar-project.properties → projectKey, organization (same pattern)
         ↓
SonarCloud API (https://sonarcloud.io/api/issues/search)
  - Auth: Authorization: Bearer $SONAR_TOKEN
  - Params: componentKeys, organization, resolved=false, ps=100
  - curl -s -w "\n%{http_code}" → split body and status, check status before jq
  - Pagination: loop p=1,2,... until cumulative count >= paging.total or page returns 0 issues
         ↓
sonar-report.json  (bare JSON array of issue objects, overwrites existing file)
         ↓  jq transform
sonar-report.md    (markdown table, overwrites existing file)
```

## API Details

- **Endpoint:** `GET https://sonarcloud.io/api/issues/search`
- **Params:** `componentKeys={projectKey}`, `organization={org}`, `resolved=false`, `ps=100`, `p={page}`
- **Auth:** `Authorization: Bearer $SONAR_TOKEN`
- **HTTP error detection:** `curl -s -w "\n%{http_code}"` appends status on final line. Extract with `tail -1` / `head -n -1`. If status >= 400, exit 1 with the body text; otherwise pass body to `jq`.
- **Pagination:** Response includes `paging.total`. Loop from `p=1` incrementing by 1. Stop when cumulative collected count >= `paging.total`, OR when the current page returns 0 issues (guards against infinite loop). Merge `.issues[]` arrays from all pages into a single array.

## Output Format

### sonar-report.json

Bare JSON array of issue objects (not wrapped in a response envelope):

```json
[
  { "key": "...", "severity": "MAJOR", "type": "BUG", "component": "...", "line": 42, "message": "...", "effort": "10min" },
  ...
]
```

### sonar-report.md

```
# SonarCloud Report — {projectKey}
_Generated: {datetime} — {N} open issue(s)_

| Severity | Type | File | Line | Message | Effort |
|----------|------|------|------|---------|--------|
```

- **Datetime format:** `date -u +"%Y-%m-%d %H:%M UTC"`
- **Severity sort:** BLOCKER → CRITICAL → MAJOR → MINOR → INFO, implemented in `jq` using an index map (not alphabetical sort):
  ```jq
  def sev_order: {"BLOCKER":0,"CRITICAL":1,"MAJOR":2,"MINOR":3,"INFO":4};
  sort_by(.severity | sev_order[.])
  ```
- **File paths:** Strip `{projectKey}:` prefix from `.component` field
- **0 issues:** Table header written with "0 open issue(s)"; no data rows; exit 0

## Error Handling

| Condition | Behaviour |
|-----------|-----------|
| `curl` not installed | Exit 1: "Error: curl is required but not installed." |
| `jq` not installed | Exit 1: "Error: jq is required but not installed." |
| `sonar-project.properties` not found | Exit 1: "Error: sonar-project.properties not found in project root." |
| `sonar.projectKey` missing/empty | Exit 1: "Error: sonar.projectKey not set in sonar-project.properties." |
| `sonar.organization` missing/empty | Exit 1: "Error: sonar.organization not set in sonar-project.properties." |
| `.env` not found | Exit 1: "Error: .env not found. Create one with SONAR_TOKEN=your_token." |
| `SONAR_TOKEN` missing/empty | Exit 1: "Error: SONAR_TOKEN not set in .env." |
| API HTTP status >= 400 | Exit 1: "Error: SonarCloud API returned HTTP {status}: {body}" |
| Page returns 0 issues unexpectedly | Exit pagination loop, proceed with collected issues |
| 0 issues returned total | Write empty JSON array; write markdown with "0 open issue(s)"; exit 0 |

## File Changes

| File | Action |
|------|--------|
| `bin/` | Create directory |
| `bin/sonar-report.sh` | Create (executable: `chmod +x`) |
| `sonar-report.json` | Overwritten on each run (already exists, not git-ignored) |
| `sonar-report.md` | Overwritten on each run (already exists, not git-ignored) |
| `.env` | Created manually by developer (git-ignored, never committed) |
| `.env.example` | Create (committed, not git-ignored) |
| `.gitignore` | Edit: add `.env` line (not `.env*`, so `.env.example` remains tracked) |
