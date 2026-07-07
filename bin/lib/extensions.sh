#!/usr/bin/env bash

extensions_root() {
    cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd
}

extensions_dir() {
    local root
    root="$(extensions_root)"
    local dir="${EXTENSIONS_DIR:-$root/../concept-extensions}"

    if [[ ! -d "$dir" ]]; then
        echo "Extensions directory not found: $dir" >&2
        exit 1
    fi

    echo "$dir"
}

extensions_phpstan_bin() {
    local root
    root="$(extensions_root)"
    local candidates=(
        "$root/vendor/bin/phpstan"
        "$(command -v phpstan 2>/dev/null || true)"
    )

    for candidate in "${candidates[@]}"; do
        if [[ -n "$candidate" && -x "$candidate" ]]; then
            echo "$candidate"
            return 0
        fi
    done

    echo "phpstan not found. Run composer install in the skeleton project first." >&2
    exit 1
}

extensions_normalize_name() {
    local name="${1#extension-}"
    echo "$name"
}

extensions_resolve_dir() {
    local name
    name="$(extensions_normalize_name "$1")"
    local dir
    dir="$(extensions_dir)/extension-$name"

    if [[ ! -d "$dir" ]]; then
        echo "Unknown extension: $1" >&2
        echo "Expected directory: $dir" >&2
        exit 1
    fi

    echo "$dir"
}

extensions_list_dirs() {
    local dir
    dir="$(extensions_dir)"
    find "$dir" -maxdepth 1 -mindepth 1 -type d -name 'extension-*' | sort
}

extensions_basename() {
    basename "$1"
}
