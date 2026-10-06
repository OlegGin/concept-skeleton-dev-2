#!/usr/bin/env bash
#
# Run extension PHPUnit suites (see concept-extensions/test-all.sh).
#
# Docker:
#   docker exec concept_skeleton_dev_2 composer test-extensions
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
EXTENSIONS_ROOT="$(cd "$ROOT/../../../concept-extensions" && pwd)"

exec "$EXTENSIONS_ROOT/test-all.sh" "$@"
