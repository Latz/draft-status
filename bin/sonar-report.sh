#!/usr/bin/env bash
set -euo pipefail

# Determine project root regardless of where script is called from
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# --- Prerequisite checks ---
if ! command -v curl &>/dev/null; then
  echo "Error: curl is required but not installed." >&2
  exit 1
fi

if ! command -v jq &>/dev/null; then
  echo "Error: jq is required but not installed." >&2
  exit 1
fi

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
