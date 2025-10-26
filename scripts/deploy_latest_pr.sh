#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${GITHUB_OWNER:-}" || -z "${GITHUB_REPO:-}" ]]; then
  echo "GITHUB_OWNER und GITHUB_REPO müssen gesetzt sein." >&2
  exit 1
fi

if [[ -z "${GITHUB_TOKEN:-}" ]]; then
  echo "Warnung: Kein GITHUB_TOKEN gesetzt – öffentliche Repositories können ohne Token abgerufen werden, rate-limits könnten jedoch greifen." >&2
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

API_URL="https://api.github.com/repos/${GITHUB_OWNER}/${GITHUB_REPO}/pulls?state=open&sort=updated&direction=desc&per_page=1"
AUTH_HEADER=()
if [[ -n "${GITHUB_TOKEN:-}" ]]; then
  AUTH_HEADER=(-H "Authorization: Bearer ${GITHUB_TOKEN}")
fi

response="$(curl -fsSL -H "Accept: application/vnd.github+json" "${AUTH_HEADER[@]}" "${API_URL}")"

pr_number="$(echo "$response" | jq '.[0].number // empty')"

if [[ -z "${pr_number}" ]]; then
  echo "Keine offenen Pull Requests gefunden." >&2
  exit 1
fi

echo "Deploye Pull Request #${pr_number}" >&2

cd "${PROJECT_ROOT}"

branch="pr-${pr_number}"

git fetch origin "pull/${pr_number}/head"
git checkout -B "${branch}" FETCH_HEAD

echo "Installiere Abhängigkeiten …" >&2
npm install

echo "Baue Produktionsbundle …" >&2
npm run build

echo "Fertig. Starte optional mit 'npm run preview -- --host 0.0.0.0 --port 4173' in einem separaten Dienst." >&2
