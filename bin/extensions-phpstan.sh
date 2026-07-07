#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib/extensions.sh"

usage() {
    cat <<'EOF'
Usage:
  bin/extensions-phpstan.sh                 Run PHPStan for all extensions
  bin/extensions-phpstan.sh <extension>     Run PHPStan for one extension

Extension name examples:
  http
  extension-http
  error-handler-whoops

Environment:
  EXTENSIONS_DIR   Override extensions root (default: ../concept-extensions)
  PHPSTAN_ARGS     Extra args passed to phpstan (default: --memory-limit=2G)
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

PHPSTAN="$(extensions_phpstan_bin)"
PHPSTAN_ARGS="${PHPSTAN_ARGS:---memory-limit=2G}"
FAILED=0
RAN=0

run_phpstan() {
    local dir="$1"
    local name
    name="$(extensions_basename "$dir")"
    local config="$dir/phpstan.neon"

    if [[ ! -f "$config" ]]; then
        printf '[skip]  %-32s no phpstan.neon\n' "$name"
        return 0
    fi

    RAN=$((RAN + 1))
    echo "==> $name"
    if (cd "$dir" && "$PHPSTAN" analyse -c phpstan.neon --no-progress $PHPSTAN_ARGS); then
        echo
        return 0
    fi

    echo
    FAILED=$((FAILED + 1))
    return 0
}

if [[ $# -gt 1 ]]; then
    usage >&2
    exit 1
fi

echo "PHPStan: $PHPSTAN"
echo "Extensions: $(extensions_dir)"
echo

if [[ $# -eq 1 ]]; then
    run_phpstan "$(extensions_resolve_dir "$1")"
else
    while IFS= read -r dir; do
        run_phpstan "$dir"
    done < <(extensions_list_dirs)
fi

if [[ "$RAN" -eq 0 ]]; then
    echo "No extensions with phpstan.neon found."
    exit 1
fi

if [[ "$FAILED" -gt 0 ]]; then
    echo "$FAILED extension(s) failed PHPStan."
    exit 1
fi

echo "PHPStan passed for $RAN extension(s)."
