#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
COMPOSE_FILE="docker-compose.system-test.yml"
COMPOSE_PROJECT="flysystem-bundle-system-test"
COMPOSER_SHA256="6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c"
COMPOSER_URL="https://github.com/composer/composer/releases/download/2.11.1/composer.phar"
COMPOSER_PHAR="${ROOT_DIR}/.system-test/composer.phar"

if [[ "${USE_SUDO_DOCKER}" == "1" ]]; then
  DOCKER=(sudo docker)
else
  DOCKER=(docker)
fi

COMPOSE=("${DOCKER[@]}" compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT}")

log() {
  printf '%s\n' "$*"
}

run_in_app() {
  "${DOCKER[@]}" run --rm \
    --network host \
    -v "${ROOT_DIR}:/app" \
    -w /app \
  -e SYMFONY_DEPRECATIONS_HELPER=max[total]=0 \
    "${APP_IMAGE}" \
    bash -lc "$1"
}

run_in_app_host_network() {
  "${DOCKER[@]}" run --rm --network host \
    -v "${ROOT_DIR}:/app" \
    -w /app \
    "${APP_IMAGE}" \
    bash -lc "$1"
}

validate_phpunit_output() {
  local output_file="$1"
  if grep -Eq '^(PHP )?Warning:' "${output_file}"; then
    log "PHPUnit output contains Warning:"
    grep -E '^(PHP )?Warning:' "${output_file}" || true
    return 1
  fi
  if grep -Eq '^(PHP )?Deprecated:' "${output_file}"; then
    log "PHPUnit output contains Deprecated:"
    grep -E '^(PHP )?Deprecated:' "${output_file}" || true
    return 1
  fi
  if ! grep -Eq '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}"; then
    log 'PHPUnit output is missing strict success line: OK (N tests, M assertions)'
    return 1
  fi
  grep -Eo '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}" | tail -n 1
  return 0
}

mkdir -p "${ROOT_DIR}/.system-test"
if [[ ! -f "${COMPOSER_PHAR}" ]]; then
  log "Downloading checksum-verified Composer phar on host"
  curl -fsSL -o "${COMPOSER_PHAR}" "${COMPOSER_URL}"
  echo "${COMPOSER_SHA256}  ${COMPOSER_PHAR}" | sha256sum -c -
  chmod +x "${COMPOSER_PHAR}"
fi

log "Building system-test image"
"${COMPOSE[@]}" build app
APP_IMAGE="$("${DOCKER[@]}" images --format '{{.Repository}}:{{.Tag}}' | grep "^${COMPOSE_PROJECT}-app" | head -n 1)"
if [[ -z "${APP_IMAGE}" ]]; then
  APP_IMAGE="${COMPOSE_PROJECT}-app:latest"
fi

log "Starting sidecar services"
"${COMPOSE[@]}" up -d ftp sftp s3 gcs

sleep 5

log "Creating S3 and GCS buckets for cluster tests"
curl -sf -X PUT "http://127.0.0.1:9090/flysystem-system-test" -H "Content-Length: 0" -o /dev/null || true
curl -sf -X POST "http://127.0.0.1:4443/storage/v1/b?project=test" \
  -H "Content-Type: application/json" \
  -d '{"name":"flysystem-system-test"}' -o /dev/null || true

INSTALL_DEPS='
set -euo pipefail
cd /app
export COMPOSER_ALLOW_SUPERUSER=1
install -m 0755 /app/.system-test/composer.phar /usr/local/bin/composer
git config --global --add safe.directory /app
php /app/.system-test/composer.phar update --no-interaction --prefer-stable --no-progress
vendor/bin/simple-phpunit --version > /dev/null
set +e
php /app/.system-test/composer.phar audit --no-interaction > /tmp/composer-audit.log 2>&1
audit_status=$?
set -e
cat /tmp/composer-audit.log
if ! grep -q "No security vulnerability advisories found." /tmp/composer-audit.log; then
  exit "${audit_status}"
fi
'

RUN_TESTS='
set -euo pipefail
cd /app
export SYMFONY_DEPRECATIONS_HELPER=max[total]=0
install -m 0755 /app/.system-test/composer.phar /usr/local/bin/composer || true
php scripts/check-cluster-pins.php
mkdir -p /app/.system-test
vendor/bin/simple-phpunit --colors=never 2>&1 | tee /app/.system-test/phpunit.out
'

if ! run_in_app_host_network "${INSTALL_DEPS}"; then
  log "Composer install/audit failed"
  "${COMPOSE[@]}" down 2>/dev/null || true
  exit 1
fi

OUTPUT_FILE="$(mktemp)"
if ! run_in_app "${RUN_TESTS}" | tee "${OUTPUT_FILE}"; then
  log "System test commands failed"
  "${COMPOSE[@]}" down
  exit 1
fi

PHPUNIT_OUT="${ROOT_DIR}/.system-test/phpunit.out"

if ! validate_phpunit_output "${PHPUNIT_OUT}"; then
  "${COMPOSE[@]}" down
  exit 1
fi

OK_LINE="$(grep -Eo '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${PHPUNIT_OUT}" | tail -n 1)"
log "Strict success line: ${OK_LINE}"

log "Installed cluster references:"
"${DOCKER[@]}" run --rm --network host \
  -v "${ROOT_DIR}:/app" -w /app "${APP_IMAGE}" \
  php scripts/print-cluster-references.php

"${COMPOSE[@]}" down

log "System test passed."
