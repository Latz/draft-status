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
