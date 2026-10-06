#!/usr/bin/env bash
#
# Run PHPStan for every extension using skeleton autoload.
#
# Usage:
#   ./phpstan-all.sh
#   ./phpstan-all.sh view
#   ./phpstan-all.sh extension-view-twig
#
# Docker:
#   SKELETON_ROOT=/var/www/html ./phpstan-all.sh
#
# Env:
#   SKELETON_ROOT   — path to skeleton (default: /var/www/concept-skeleton-dev-2)
#   EXTENSIONS_ROOT — path to extensions dir (default: this script directory)
#   PHPSTAN_MEMORY  — memory limit (default: 1G)
#
set -euo pipefail

ROOT="${EXTENSIONS_ROOT:-$(cd "$(dirname "$0")" && pwd)}"
SKELETON="${SKELETON_ROOT:-/var/www/concept-skeleton-dev-2}"
PHPSTAN="$SKELETON/vendor/bin/phpstan"
AUTOLOAD="$SKELETON/vendor/autoload.php"
SHARED_CONFIG="$ROOT/phpstan.neon.dist"
MEMORY="${PHPSTAN_MEMORY:-1G}"

usage() {
    cat <<EOF
Usage:
  $(basename "$0") [extension]

Examples:
  $(basename "$0")
  $(basename "$0") view
  $(basename "$0") extension-http
EOF
}

print_cmd() {
    echo -n "   command:"
    for arg in "$@"; do
        printf ' %q' "$arg"
    done
    echo
}

resolve_extension_dir() {
    local arg="$1"
    local candidate=""

    if [[ "$arg" == extension-* ]]; then
        candidate="$ROOT/$arg"
    else
        candidate="$ROOT/extension-$arg"
    fi

    if [[ -d "$candidate" ]]; then
        echo "$candidate"
        return 0
    fi

    return 1
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

if [[ $# -gt 1 ]]; then
    usage >&2
    exit 1
fi

if [[ ! -x "$PHPSTAN" ]]; then
    echo "PHPStan not found: $PHPSTAN" >&2
    echo "Set SKELETON_ROOT to a skeleton with vendor/ installed." >&2
    exit 1
fi

if [[ ! -f "$AUTOLOAD" ]]; then
    echo "Autoload not found: $AUTOLOAD" >&2
    exit 1
fi

if [[ ! -f "$SHARED_CONFIG" ]]; then
    echo "Shared PHPStan config not found: $SHARED_CONFIG" >&2
    exit 1
fi

failed=()

run_phpstan_for_dir() {
    local dir="$1"
    local name
    local src
    local config
    local -a target
    local -a cmd

    name="$(basename "$dir")"
    src="$dir/src"

    if [[ ! -d "$src" ]]; then
        echo "==> $name — SKIP (no src/)"
        return 0
    fi

    if [[ -f "$dir/phpstan.neon" ]]; then
        config="$dir/phpstan.neon"
        target=("$config")
    else
        config="$SHARED_CONFIG"
        target=("$src")
    fi

    echo "========================================"
    echo "=> $name"
    echo "   config: $config"

    cmd=(
        "$PHPSTAN" analyse
        -c "$config"
        --autoload-file="$AUTOLOAD"
        --memory-limit="$MEMORY"
        --no-progress
        "${target[@]}"
    )

    print_cmd "${cmd[@]}"
    echo "========================================"

    if ! "${cmd[@]}"; then
        failed+=("$name")
    fi

    echo
}

if [[ $# -eq 1 ]]; then
    if ! dir="$(resolve_extension_dir "$1")"; then
        echo "Extension not found: $1" >&2
        echo "Expected directory under $ROOT (e.g. view or extension-view)." >&2
        exit 1
    fi

    run_phpstan_for_dir "$dir"
else
    for dir in "$ROOT"/extension-*/; do
        [[ -d "$dir" ]] || continue
        run_phpstan_for_dir "$dir"
    done
fi

if ((${#failed[@]} > 0)); then
    echo "PHPStan failed for:" "${failed[@]}" >&2
    exit 1
fi

if [[ $# -eq 1 ]]; then
    echo "PHPStan passed for $1."
else
    echo "PHPStan passed for all extensions."
fi
