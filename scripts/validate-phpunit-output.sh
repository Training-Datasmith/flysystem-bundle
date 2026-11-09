#!/usr/bin/env bash

validate_phpunit_output() {
  local output_file="$1"
  if grep -Eq '^(PHP )?Warning:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Warning:"
    grep -E '^(PHP )?Warning:' "${output_file}" || true
    return 1
  fi
  if grep -Eq '^(PHP )?Deprecated:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Deprecated:"
    grep -E '^(PHP )?Deprecated:' "${output_file}" || true
    return 1
  fi
  local deprecation_heading
  for deprecation_heading in \
    'Unsilenced deprecation notices' \
    'Remaining self deprecation notices' \
    'Remaining direct deprecation notices' \
    'Remaining indirect deprecation notices' \
    'Legacy deprecation notices' \
    'Other deprecation notices' \
    ; do
    if grep -Fq "${deprecation_heading}" "${output_file}"; then
      printf '%s\n' "PHPUnit output contains Symfony deprecation summary: ${deprecation_heading}"
      grep -F "${deprecation_heading}" "${output_file}" || true
      return 1
    fi
  done
  if ! grep -Eq '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}"; then
    printf '%s\n' 'PHPUnit output is missing strict success line: OK (N tests, M assertions)'
    return 1
  fi
  grep -Eo '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${output_file}" | tail -n 1
  return 0
}
