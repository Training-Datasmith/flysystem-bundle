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
  "${COMPOSE[@]}" run --rm --no-TTY app bash -lc "$1"
}

run_composer_install() {
  local image="$1"
  shift
  "${DOCKER[@]}" run --rm --network host \
    -v "${ROOT_DIR}:/app" \
    -w /app \
    -e COMPOSER_ALLOW_SUPERUSER=1 \
    "${image}" \
    bash -lc "$*"
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
  local deprecation_heading
  for deprecation_heading in \
    'Unsilenced deprecation notices' \
    'Remaining self deprecation notices' \
    'Remaining direct deprecation notices' \
    'Remaining indirect deprecation notices' \
    'Remaining deprecations' \
    ; do
    if grep -Fq "${deprecation_heading}" "${output_file}"; then
      log "PHPUnit output contains Symfony deprecation summary: ${deprecation_heading}"
      grep -F "${deprecation_heading}" "${output_file}" || true
      return 1
    fi
  done
  if ! grep -Eq '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}"; then
    log 'PHPUnit output is missing strict success line: OK (N tests, M assertions)'
    return 1
  fi
  grep -Eo '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}" | tail -n 1
  return 0
}

wait_for_sidecars() {
  run_in_app '
set -euo pipefail
for attempt in $(seq 1 60); do
  s3_code="$(curl -sS -o /dev/null -w "%{http_code}" --max-time 2 "http://s3:9090/" || true)"
  gcs_code="$(curl -sS -o /dev/null -w "%{http_code}" --max-time 2 "http://gcs:4443/storage/v1/b?project=test" || true)"
  if [[ "${s3_code}" == "200" && "${gcs_code}" != "000" && -n "${gcs_code}" ]]; then
    exit 0
  fi
  sleep 1
done
echo "Sidecar emulators did not become reachable in time"
exit 1
'
}

create_cluster_buckets() {
  run_in_app '
set -euo pipefail
if ! s3_code="$(curl -sS -o /tmp/s3-bucket-body.txt -w "%{http_code}" -X PUT "http://s3:9090/flysystem-system-test" -H "Content-Length: 0")"; then
  echo "S3 emulator unreachable"
  exit 1
fi
if [[ "${s3_code}" != "200" ]]; then
  if grep -qiE "BucketAlreadyOwnedByYou|BucketAlreadyExists|already exists" /tmp/s3-bucket-body.txt 2>/dev/null; then
  :
  else
    echo "S3 bucket create failed (HTTP ${s3_code}):"
    cat /tmp/s3-bucket-body.txt
    exit 1
  fi
fi

if ! gcs_code="$(curl -sS -o /tmp/gcs-bucket-body.txt -w "%{http_code}" -X POST "http://gcs:4443/storage/v1/b?project=test" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"flysystem-system-test\"}")"; then
  echo "GCS emulator unreachable"
  exit 1
fi
if [[ "${gcs_code}" != "200" ]]; then
  if grep -qiE "already exists|409|AlreadyExists" /tmp/gcs-bucket-body.txt 2>/dev/null; then
  :
  else
    echo "GCS bucket create failed (HTTP ${gcs_code}):"
    cat /tmp/gcs-bucket-body.txt
    exit 1
  fi
fi
'
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

log "Waiting for sidecar emulators"
wait_for_sidecars

log "Creating S3 and GCS buckets for cluster tests"
create_cluster_buckets

COMPOSER_UPDATE='
set -euo pipefail
cd /app
export COMPOSER_ALLOW_SUPERUSER=1
install -m 0755 /app/.system-test/composer.phar /usr/local/bin/composer
git config --global --add safe.directory /app
php /app/.system-test/composer.phar update --no-interaction --prefer-stable --no-progress
'

COMPOSER_AUDIT='
set -euo pipefail
cd /app
export COMPOSER_ALLOW_SUPERUSER=1
install -m 0755 /app/.system-test/composer.phar /usr/local/bin/composer
vendor/bin/simple-phpunit --version > /dev/null
php /app/.system-test/composer.phar audit --no-interaction --abandoned=report
'

RUN_TESTS='
set -euo pipefail
cd /app
export SYMFONY_DEPRECATIONS_HELPER=max[total]=0
php scripts/check-cluster-pins.php
mkdir -p /app/.system-test
vendor/bin/simple-phpunit --colors=never 2>&1 | tee /app/.system-test/phpunit.out
'

if ! run_composer_install "${APP_IMAGE}" "${COMPOSER_UPDATE}"; then
  log "Composer update failed"
  "${COMPOSE[@]}" down 2>/dev/null || true
  exit 1
fi

if ! run_composer_install "${APP_IMAGE}" "${COMPOSER_AUDIT}"; then
  log "Composer audit failed"
  "${COMPOSE[@]}" down 2>/dev/null || true
  exit 1
fi

if ! run_in_app "${RUN_TESTS}"; then
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
run_in_app 'php scripts/print-cluster-references.php'

"${COMPOSE[@]}" down

log "System test passed."
