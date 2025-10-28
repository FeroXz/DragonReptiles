#!/usr/bin/env bash
set -euo pipefail

info() {
  printf '[INFO] %s\n' "$*"
}

step() {
  printf '[STEP] %s\n' "$*"
}

warn() {
  printf '[WARN] %s\n' "$*"
}

error_exit() {
  printf '[ERROR] %s\n' "$*" >&2
  exit 1
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
UPDATE_DIR="${PROJECT_ROOT}/storage/update"
LOG_FILE="${UPDATE_DIR}/job.log"
MAINTENANCE_FLAG="${PROJECT_ROOT}/storage/maintenance.flag"
STATUS_FILE="${UPDATE_DIR}/job.status.json"

mkdir -p "${UPDATE_DIR}"
touch "${LOG_FILE}"

DRY_RUN_VALUE="${DRY_RUN:-0}"
DRY_RUN_NORMALIZED="$(printf '%s' "${DRY_RUN_VALUE}" | tr '[:upper:]' '[:lower:]')"
if [[ "${DRY_RUN_NORMALIZED}" =~ ^(1|true|yes|on)$ ]]; then
  DRY_RUN_MODE=1
else
  DRY_RUN_MODE=0
fi

TARGET_DIR="${DEPLOY_TARGET_DIR:-/cms}"
SFTP_HOST="${SFTP_HOST:-}"
SFTP_PORT="${SFTP_PORT:-22}"
SFTP_USER="${SFTP_USER:-}"
SFTP_PASS="${SFTP_PASS:-}"
SFTP_KEY_BASE64="${SFTP_KEY_BASE64:-}"
GITHUB_OWNER="${GITHUB_OWNER:-}"
GITHUB_REPO="${GITHUB_REPO:-}"
GITHUB_TOKEN="${GITHUB_TOKEN:-}"
PR_OVERRIDE="${PR_NUMBER:-}"

if [[ -z "${GITHUB_OWNER}" || -z "${GITHUB_REPO}" ]]; then
  error_exit "GITHUB_OWNER und GITHUB_REPO müssen gesetzt sein."
fi

REQUIRED_CMDS=(git curl jq npm)
if [[ "${DRY_RUN_MODE}" -eq 0 ]]; then
  REQUIRED_CMDS+=(lftp)
fi

for cmd in "${REQUIRED_CMDS[@]}"; do
  if ! command -v "${cmd}" >/dev/null 2>&1; then
    error_exit "Benötigtes Werkzeug '${cmd}' wurde nicht gefunden."
  fi
done

INITIAL_REF=""
TEMP_BRANCH=""
TEMP_KEY_FILE=""

cleanup() {
  local exit_code=$?
  trap - EXIT INT TERM

  if [[ -n "${TEMP_BRANCH}" && -n "${INITIAL_REF}" ]]; then
    git checkout "${INITIAL_REF}" >/dev/null 2>&1 || true
    if [[ "${TEMP_BRANCH}" != "${INITIAL_REF}" ]]; then
      git branch -D "${TEMP_BRANCH}" >/dev/null 2>&1 || true
    fi
  elif [[ -n "${INITIAL_REF}" ]]; then
    git checkout "${INITIAL_REF}" >/dev/null 2>&1 || true
  fi

  if [[ -n "${TEMP_KEY_FILE}" && -f "${TEMP_KEY_FILE}" ]]; then
    shred -u "${TEMP_KEY_FILE}" 2>/dev/null || rm -f "${TEMP_KEY_FILE}"
  fi

  rm -f "${MAINTENANCE_FLAG}"
  unset LFTP_PASSWORD

  printf '__EXIT_CODE:%s\n' "${exit_code}" >> "${LOG_FILE}"
  exit "${exit_code}"
}

trap cleanup EXIT
trap 'warn "Deploy durch SIGINT beendet."; exit 130' INT
trap 'warn "Deploy durch SIGTERM beendet."; exit 143' TERM

step "Aktiviere Wartungsmodus"
printf '%s' "$(date -Iseconds)" > "${MAINTENANCE_FLAG}"

if [[ "${DRY_RUN_MODE}" -eq 1 ]]; then
  info "Dry-Run aktiviert – Upload wird übersprungen."
fi

info "Arbeitsverzeichnis: ${PROJECT_ROOT}"
cd "${PROJECT_ROOT}"

INITIAL_REF="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || git rev-parse HEAD 2>/dev/null || echo '')"

if [[ -n "${PR_OVERRIDE}" ]]; then
  if ! [[ "${PR_OVERRIDE}" =~ ^[0-9]+$ ]]; then
    error_exit "Ungültige Pull-Request-Nummer '${PR_OVERRIDE}'."
  fi
  pr_number="${PR_OVERRIDE}"
  info "Nutze Pull Request #${pr_number} aus der Anfrage."
else
  step "Ermittle aktuellsten Pull Request"
  API_URL="https://api.github.com/repos/${GITHUB_OWNER}/${GITHUB_REPO}/pulls?state=open&sort=updated&direction=desc&per_page=1"
  AUTH_HEADER=()
  if [[ -n "${GITHUB_TOKEN}" ]]; then
    AUTH_HEADER=(-H "Authorization: Bearer ${GITHUB_TOKEN}")
  fi
  response="$(curl -fsSL -H "Accept: application/vnd.github+json" "${AUTH_HEADER[@]}" "${API_URL}")" || error_exit "GitHub API Anfrage fehlgeschlagen."
  pr_number="$(printf '%s' "${response}" | jq '.[0].number // empty')"
  pr_title="$(printf '%s' "${response}" | jq -r '.[0].title // empty')"
  if [[ -z "${pr_number}" ]]; then
    error_exit "Keine offenen Pull Requests gefunden."
  fi
  info "Deploye Pull Request #${pr_number}: ${pr_title}"
fi

step "Hole Pull Request #${pr_number}"
TEMP_BRANCH="deploy-pr-${pr_number}"

git fetch origin "pull/${pr_number}/head:${TEMP_BRANCH}" >/dev/null 2>&1 || error_exit "Git Fetch für PR #${pr_number} fehlgeschlagen."
git checkout "${TEMP_BRANCH}" >/dev/null 2>&1 || error_exit "Checkout des Branches ${TEMP_BRANCH} fehlgeschlagen."

data_dir="${PROJECT_ROOT}/storage"
mkdir -p "${data_dir}"

step "Installiere Abhängigkeiten"
npm install --no-fund --no-audit

step "Erstelle Produktionsbuild"
npm run build

if [[ "${DRY_RUN_MODE}" -eq 1 ]]; then
  info "Dry-Run abgeschlossen. Überspringe Upload zum Server."
  info "Deploy-Prozess beendet (Dry-Run)."
  exit 0
fi

if [[ -z "${SFTP_HOST}" || -z "${SFTP_USER}" ]]; then
  error_exit "SFTP Zugangsdaten unvollständig."
fi

if [[ -n "${SFTP_KEY_BASE64}" ]]; then
  TEMP_KEY_FILE="$(mktemp)"
  printf '%s' "${SFTP_KEY_BASE64}" | base64 -d > "${TEMP_KEY_FILE}" || error_exit "SFTP Schlüssel konnte nicht dekodiert werden."
  chmod 600 "${TEMP_KEY_FILE}"
fi

LFTP_SCRIPT="set cmd:fail-exit yes; set net:max-retries 1; set net:timeout 20; set sftp:auto-confirm yes;"
if [[ -n "${TEMP_KEY_FILE}" ]]; then
  LFTP_SCRIPT="${LFTP_SCRIPT} set sftp:connect-program 'ssh -a -x -i ${TEMP_KEY_FILE} -o StrictHostKeyChecking=no';"
fi
LFTP_SCRIPT="${LFTP_SCRIPT} mirror -R --delete --parallel=4 \
  --exclude-glob .git/ \
  --exclude-glob node_modules/ \
  --exclude-glob storage/ \
  --exclude-glob public/uploads/ \
  --exclude-glob .github/ \
  ./ ${TARGET_DIR}; bye"

if [[ -n "${SFTP_PASS}" ]]; then
  export LFTP_PASSWORD="${SFTP_PASS}"
else
  unset LFTP_PASSWORD
fi

step "Starte Upload zu ${SFTP_HOST}:${TARGET_DIR}"
lftp -u "${SFTP_USER}" -p "${SFTP_PORT}" "${SFTP_HOST}" -e "${LFTP_SCRIPT}"

step "Upload abgeschlossen – entferne Wartungsmodus"

info "Deploy erfolgreich abgeschlossen."
