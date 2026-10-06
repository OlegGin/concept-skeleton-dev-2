#!/usr/bin/env bash
#
# Show git status for every extension repo under this directory.
#
# Usage:
#   ./git-status-all.sh
#   EXTENSIONS_ROOT=/var/www/concept-extensions ./git-status-all.sh
#
set -euo pipefail

ROOT="${EXTENSIONS_ROOT:-$(cd "$(dirname "$0")" && pwd)}"
failed=0

for dir in "$ROOT"/extension-*/; do
    [[ -d "$dir" ]] || continue

    name="$(basename "$dir")"
    echo "========================================"
    echo "=> $name"
    echo "========================================"

    if [[ ! -d "$dir/.git" ]]; then
        echo "SKIP: not a git repository"
        echo
        failed=1
        continue
    fi

    git -C "$dir" status -sb
    echo
done

if ((failed > 0)); then
    exit 1
fi
