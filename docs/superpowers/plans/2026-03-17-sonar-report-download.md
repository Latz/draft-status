# SonarCloud Report Download Script Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create `bin/sonar-report.sh` — a standalone bash script that downloads all open SonarCloud issues and writes `sonar-report.json` (bare JSON array) and `sonar-report.md` (markdown table) to the project root.

**Architecture:** Single bash script with `set -euo pipefail`. Reads config from `.env` (token) and `sonar-project.properties` (project key, org) using safe `grep/cut/tr` parsing. Paginates the SonarCloud REST API with `curl`, merges all pages, then uses `jq` to produce both output files.

**Tech Stack:** bash, curl, jq (standard CLI tools — no installation beyond what's already on the machine)

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `bin/sonar-report.sh` | Create | Main script — config loading, API fetch, JSON + MD output |
| `sonar-report.json` | Overwrite | Generated output — committed after final run |
| `sonar-report.md` | Overwrite | Generated output — committed after final run |
| `.env.example` | Create | Documents required `SONAR_TOKEN` var for developers |
| `.gitignore` | Edit | Add `.env` line (not `.env*`) |

---

## Task 1: Create `bin/` directory and `.env.example`

**Files:**
- Create: `bin/` (directory)
- Create: `.env.example`
- Edit: `.gitignore`

- [ ] **Step 1: Create the `bin/` directory with a `.gitkeep` so git tracks it**

```bash
mkdir -p bin
touch bin/.gitkeep
```

- [ ] **Step 2: Create `.env.example`**

Create `.env.example` in the project root with this exact content:

```
SONAR_TOKEN=your_sonarcloud_token_here
```

`.env.example` is committed to the repo and must NOT be git-ignored.

- [ ] **Step 3: Add `.env` to `.gitignore`**

Append a single line `.env` to `.gitignore`. Do NOT use `.env*` — that would also ignore `.env.example`.

```bash
echo '.env' >> .gitignore
```

Verify it was added:
```bash
grep -n '^\.env$' .gitignore
# Expected: prints the line number and ".env"
```

- [ ] **Step 4: Commit**

```bash
git add bin/.gitkeep .env.example .gitignore
git commit -m "chore: add bin/ dir, .env.example, and .env to gitignore"
```

---

## Task 2: Write the script skeleton with prerequisite checks

**Files:**
- Create: `bin/sonar-report.sh`

- [ ] **Step 1: Create the script file with shebang, strict mode, and project root detection**

Create `bin/sonar-report.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

# Determine project root regardless of where script is called from
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
```

- [ ] **Step 2: Add prerequisite checks (curl, jq)**

Append to the script:

```bash
# --- Prerequisite checks ---
if ! command -v curl &>/dev/null; then
  echo "Error: curl is required but not installed." >&2
  exit 1
fi

if ! command -v jq &>/dev/null; then
  echo "Error: jq is required but not installed." >&2
  exit 1
fi
```

- [ ] **Step 3: Add sonar-project.properties parsing with validation**

Append to the script:

```bash
# --- Read sonar-project.properties ---
PROPS="$ROOT/sonar-project.properties"

if [[ ! -f "$PROPS" ]]; then
  echo "Error: sonar-project.properties not found in project root." >&2
  exit 1
fi

PROJECT_KEY=$(grep -E '^sonar.projectKey=' "$PROPS" | cut -d= -f2- | tr -d '[:space:]' || true)
ORG=$(grep -E '^sonar.organization=' "$PROPS" | cut -d= -f2- | tr -d '[:space:]' || true)

if [[ -z "$PROJECT_KEY" ]]; then
  echo "Error: sonar.projectKey not set in sonar-project.properties." >&2
  exit 1
fi

if [[ -z "$ORG" ]]; then
  echo "Error: sonar.organization not set in sonar-project.properties." >&2
  exit 1
fi
```

- [ ] **Step 4: Add `.env` reading and SONAR_TOKEN validation**

Append to the script:

```bash
# --- Read .env ---
ENV_FILE="$ROOT/.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Error: .env not found. Create one with SONAR_TOKEN=your_token." >&2
  exit 1
fi

SONAR_TOKEN=$(grep -E '^SONAR_TOKEN=' "$ENV_FILE" | cut -d= -f2- | tr -d '[:space:]' || true)

if [[ -z "$SONAR_TOKEN" ]]; then
  echo "Error: SONAR_TOKEN not set in .env." >&2
  exit 1
fi
```

- [ ] **Step 5: Make the script executable**

```bash
chmod +x bin/sonar-report.sh
```

- [ ] **Step 6: Verify the `.env` check works**

Temporarily rename `.env` if it exists, then run:

```bash
mv .env .env.bak 2>/dev/null || true
bash bin/sonar-report.sh 2>&1
```

Expected output (exactly):
```
Error: .env not found. Create one with SONAR_TOKEN=your_token.
```

Restore the file:
```bash
mv .env.bak .env 2>/dev/null || true
```

- [ ] **Step 7: Commit the skeleton**

```bash
git add bin/sonar-report.sh
git commit -m "feat: add sonar-report.sh skeleton with prereq and config validation"
```

---

## Task 3: Add API fetching with pagination

**Files:**
- Edit: `bin/sonar-report.sh`

This task adds the pagination loop that calls the SonarCloud API and accumulates all issues into a single bash variable.

- [ ] **Step 1: Append the pagination loop to the script**

```bash
# --- Fetch issues from SonarCloud ---
BASE_URL="https://sonarcloud.io/api/issues/search"
PAGE=1
TOTAL_FETCHED=0
ALL_ISSUES="[]"

echo "Fetching issues from SonarCloud..."

while true; do
  URL="${BASE_URL}?componentKeys=${PROJECT_KEY}&organization=${ORG}&resolved=false&ps=100&p=${PAGE}"

  # Note: head -n -1 requires GNU coreutils (Linux). On macOS use: sed '$d'
  RESPONSE=$(curl -s -w "\n%{http_code}" \
    -H "Authorization: Bearer $SONAR_TOKEN" \
    "$URL")

  HTTP_STATUS=$(echo "$RESPONSE" | tail -1)
  BODY=$(echo "$RESPONSE" | head -n -1)

  if [[ "$HTTP_STATUS" -ge 400 ]]; then
    echo "Error: SonarCloud API returned HTTP ${HTTP_STATUS}: ${BODY}" >&2
    exit 1
  fi

  PAGE_ISSUES=$(echo "$BODY" | jq '.issues')
  PAGE_COUNT=$(echo "$PAGE_ISSUES" | jq 'length')
  TOTAL=$(echo "$BODY" | jq '.paging.total')

  # Merge this page's issues into the accumulated array
  ALL_ISSUES=$(jq -n --argjson acc "$ALL_ISSUES" --argjson page "$PAGE_ISSUES" '$acc + $page')

  TOTAL_FETCHED=$(( TOTAL_FETCHED + PAGE_COUNT ))

  echo "  Page ${PAGE}: fetched ${PAGE_COUNT} issues (${TOTAL_FETCHED}/${TOTAL} total)"

  # Stop when we have all issues, or page returned 0 (safety guard)
  if [[ "$TOTAL_FETCHED" -ge "$TOTAL" ]] || [[ "$PAGE_COUNT" -eq 0 ]]; then
    break
  fi

  PAGE=$(( PAGE + 1 ))
done

echo "Done. ${TOTAL_FETCHED} issue(s) fetched."
```

- [ ] **Step 2: Smoke-test the pagination logic manually**

With a valid `.env` in place (all commands run from project root):

```bash
bash bin/sonar-report.sh && echo "Exit code: 0 (OK)"
```

Expected output: lines like `Page 1: fetched N issues (N/{TOTAL} total)` and `Done. {TOTAL} issue(s) fetched.` followed by `Exit code: 0 (OK)`. The exact count will vary — verify the fetched count matches the total reported by the API, not a specific number.

> Note: The output files `sonar-report.json` and `sonar-report.md` are intentionally left uncommitted here — they will be committed together in Task 6.

If you don't have a `.env` yet, create one:
```bash
echo "SONAR_TOKEN=your_actual_token" > .env
```

- [ ] **Step 3: Commit**

```bash
git add bin/sonar-report.sh
git commit -m "feat: add SonarCloud API pagination to sonar-report.sh"
```

---

## Task 4: Write JSON output

**Files:**
- Edit: `bin/sonar-report.sh`

- [ ] **Step 1: Append JSON output to the script**

```bash
# --- Write sonar-report.json ---
JSON_OUT="$ROOT/sonar-report.json"
echo "$ALL_ISSUES" | jq '.' > "$JSON_OUT"
echo "Written: sonar-report.json"
```

- [ ] **Step 2: Verify the output**

```bash
bash bin/sonar-report.sh
jq type sonar-report.json        # must print: "array"
jq 'length' sonar-report.json   # prints issue count
jq '.[0] | keys' sonar-report.json  # should include "severity", "type", "component", "message"
```

> Note: `sonar-report.json` is intentionally left uncommitted here — it will be committed in Task 6.

- [ ] **Step 3: Commit**

```bash
git add bin/sonar-report.sh
git commit -m "feat: write sonar-report.json from fetched issues"
```

---

## Task 5: Write Markdown output

**Files:**
- Edit: `bin/sonar-report.sh`

- [ ] **Step 1: Append markdown generation to the script**

```bash
# --- Write sonar-report.md ---
MD_OUT="$ROOT/sonar-report.md"
DATETIME=$(date -u +"%Y-%m-%d %H:%M UTC")
ISSUE_COUNT=$(echo "$ALL_ISSUES" | jq 'length')

{
  echo "# SonarCloud Report — ${PROJECT_KEY}"
  echo "_Generated: ${DATETIME} — ${ISSUE_COUNT} open issue(s)_"
  echo ""
  echo "| Severity | Type | File | Line | Message | Effort |"
  echo "|----------|------|------|------|---------|--------|"

  echo "$ALL_ISSUES" | jq -r '
    def sev_order: {"BLOCKER":0,"CRITICAL":1,"MAJOR":2,"MINOR":3,"INFO":4};
    sort_by(.severity | sev_order[.] // 99) |
    .[] |
    [
      .severity,
      .type,
      (.component | split(":") | last),
      (.line // "" | tostring),
      .message,
      (.effort // "")
    ] |
    "| " + join(" | ") + " |"
  '
} > "$MD_OUT"

echo "Written: sonar-report.md"
```

- [ ] **Step 2: Verify the markdown output**

```bash
bash bin/sonar-report.sh
head -10 sonar-report.md
```

Expected (issue count will vary):
```
# SonarCloud Report — Latz_draft-status
_Generated: 2026-03-17 14:30 UTC — {N} open issue(s)_

| Severity | Type | File | Line | Message | Effort |
|----------|------|------|------|---------|--------|
| CRITICAL | ...
| MAJOR    | ...
...
```

Check sort order: any CRITICAL rows must appear before MAJOR rows, MAJOR before MINOR.

> Note: `sonar-report.md` is intentionally left uncommitted here — it will be committed in Task 6.

- [ ] **Step 3: Commit**

```bash
git add bin/sonar-report.sh
git commit -m "feat: write sonar-report.md with sorted markdown table"
```

---

## Task 6: Final wiring — add closing echo and update output files

**Files:**
- Edit: `bin/sonar-report.sh`
- Edit: `sonar-report.json` (generated by running the script)
- Edit: `sonar-report.md` (generated by running the script)

- [ ] **Step 1: Add a final success message at the end of the script**

Append to `bin/sonar-report.sh`:

```bash
echo ""
echo "Report complete."
echo "  sonar-report.json  (${ISSUE_COUNT} issues)"
echo "  sonar-report.md    (${ISSUE_COUNT} issues)"
```

- [ ] **Step 2: Run the script one final time to regenerate both output files**

```bash
bash bin/sonar-report.sh
```

Expected: clean run, both files updated.

- [ ] **Step 3: Commit everything**

```bash
git add bin/sonar-report.sh sonar-report.json sonar-report.md
git commit -m "feat: complete sonar-report.sh with JSON and markdown output"
```

---

## Verification Checklist

After all tasks are complete:

- [ ] `bash bin/sonar-report.sh` runs without errors when `.env` is present
- [ ] `sonar-report.json` is a valid bare JSON array (starts with `[`, ends with `]`)
- [ ] `sonar-report.md` has correct header format and issues sorted BLOCKER → CRITICAL → MAJOR → MINOR → INFO
- [ ] Running without `.env` produces: `Error: .env not found. Create one with SONAR_TOKEN=your_token.`
- [ ] The `curl`/`jq` prerequisite checks are validated by code review of the `command -v` guards in the script (runtime testing requires uninstalling the tools, which is impractical)
- [ ] `.env` is in `.gitignore`, `.env.example` is NOT — verify with:
  ```bash
  git check-ignore -v .env        # should print the .gitignore rule
  git check-ignore -v .env.example # should print nothing (not ignored)
  ```
- [ ] `git status` shows no untracked sensitive files
