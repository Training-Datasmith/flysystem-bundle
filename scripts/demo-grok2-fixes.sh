#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

log() {
  printf '%s\n' "$*"
}

run_php() {
  if command -v php >/dev/null 2>&1; then
    php "$@"
    return
  fi
  local image="${SYSTEM_TEST_APP_IMAGE:-flysystem-bundle-system-test-app:latest}"
  if [[ "${USE_SUDO_DOCKER:-0}" == "1" ]]; then
    sudo docker run --rm -v "${ROOT_DIR}:/app" -w /app "${image}" php "$@"
  else
    docker run --rm -v "${ROOT_DIR}:/app" -w /app "${image}" php "$@"
  fi
}

fixture_dir=".system-test/demo-grok2-fixtures"
fixture_root="${ROOT_DIR}/${fixture_dir}"
mkdir -p "${fixture_root}"

real_s3="${fixture_root}/real-s3.xml"
real_gcs="${fixture_root}/real-gcs.json"
bad_s3="${fixture_root}/bad-s3.xml"
bad_gcs_4091="${fixture_root}/bad-gcs-4091.json"
bad_gcs_400="${fixture_root}/bad-gcs-400.json"
cat >"${real_s3}" <<'XML'
<?xml version="1.0" encoding="UTF-8"?><Error><Code>BucketAlreadyOwnedByYou</Code><Message>Your previous request to create the named bucket succeeded and you already own it.</Message></Error>
XML

cat >"${real_gcs}" <<'JSON'
{"error":{"code":409,"message":"A Cloud Storage bucket named 'flysystem-system-test' already exists. Try another name. Bucket names must be globally unique across all Google Cloud projects, including those outside of your organization.","errors":[{"domain":"global","reason":"conflict","message":"A Cloud Storage bucket named 'flysystem-system-test' already exists. Try another name. Bucket names must be globally unique across all Google Cloud projects, including those outside of your organization."}]}}
JSON

cat >"${bad_s3}" <<'XML'
<?xml version="1.0" encoding="UTF-8"?><Error><Code>InternalError</Code><Message>metadata already exists for this key</Message></Error>
XML

cat >"${bad_gcs_4091}" <<'JSON'
{"error":{"code":500,"message":"backend request 4091 failed"}}
JSON

cat >"${bad_gcs_400}" <<'JSON'
{"error":{"code":400,"message":"invalid bucket name","errors":[{"domain":"global","reason":"invalid","message":"invalid bucket name"}]}}
JSON

classify() {
  local label="$1"
  shift
  if run_php scripts/classify-bucket-response.php "$@" >/dev/null 2>&1; then
    log "classifier ACCEPT: ${label}"
  else
    log "classifier REJECT: ${label}"
  fi
}

log "=== bucket response classifier (offline) ==="
classify "S3 real 409 BucketAlreadyOwnedByYou" s3 409 "/app/${fixture_dir}/real-s3.xml"
classify "GCS real 409 conflict JSON" gcs 409 "/app/${fixture_dir}/real-gcs.json"
classify "S3 InternalError metadata already exists" s3 500 "/app/${fixture_dir}/bad-s3.xml"
classify "GCS HTTP 500 backend request 4091 failed" gcs 500 "/app/${fixture_dir}/bad-gcs-4091.json"
classify "GCS HTTP 400 invalid bucket name" gcs 400 "/app/${fixture_dir}/bad-gcs-400.json"
touch "${fixture_root}/empty"
classify "S3 HTTP 200 success" s3 200 "/app/${fixture_dir}/empty"
classify "GCS HTTP 200 success" gcs 200 "/app/${fixture_dir}/empty"

# shellcheck source=scripts/validate-phpunit-output.sh
source "${ROOT_DIR}/scripts/validate-phpunit-output.sh"

base_log="${fixture_root}/phpunit-base.log"
cat >"${base_log}" <<'LOG'
PHPUnit 9.6.38 by Sebastian Bergmann and contributors.

Testing 
.

Time: 00:00.001, Memory: 10.00 MB

OK (1 tests, 1 assertions)
LOG

log ""
log "=== PHPUnit output gate (injected headings) ==="
if validate_phpunit_output "${base_log}" >/dev/null; then
  log "gate PASS: clean log"
else
  log "gate FAIL: clean log (unexpected)"
  exit 1
fi

headings=(
  'Unsilenced deprecation notices'
  'Remaining self deprecation notices'
  'Remaining direct deprecation notices'
  'Remaining indirect deprecation notices'
  'Legacy deprecation notices'
  'Other deprecation notices'
)

for heading in "${headings[@]}"; do
  injected="${fixture_root}/phpunit-${heading// /-}.log"
  cp "${base_log}" "${injected}"
  printf '\n%s (1)\n' "${heading}" >>"${injected}"
  if validate_phpunit_output "${injected}" >/dev/null 2>&1; then
    log "gate FAIL: should reject \"${heading}\""
    exit 1
  fi
  log "gate PASS rejects: ${heading}"
done

if grep -Fq 'Remaining deprecations' "${base_log}"; then
  log "gate FAIL: base log should not contain Remaining deprecations"
  exit 1
fi
log "gate: Removed-unused check confirmed (no Remaining deprecations in gate list)"
