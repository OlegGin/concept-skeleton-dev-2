#!/usr/bin/env bash
#
# Release extension repos: push branch, create next tag, push tag.
# Assumes changes are already committed locally.
#
# Usage:
#   ./release-extensions.sh                          # preview all repos
#   ./release-extensions.sh extension-view           # release one
#   ./release-extensions.sh --all-ahead              # release all with unpushed commits
#   ./release-extensions.sh extension-view --tag v1.1.0
#   ./release-extensions.sh --dry-run extension-view
#
# Environment:
#   EXTENSIONS_ROOT  — root directory (default: script directory)
#   BRANCH           — branch to push (default: main)
#
set -euo pipefail

ROOT="${EXTENSIONS_ROOT:-$(cd "$(dirname "$0")" && pwd)}"
BRANCH="${BRANCH:-main}"

EXPLICIT_TAG=""
DRY_RUN=0
ALL_AHEAD=0
PREVIEW=0
EXTENSIONS=()

usage() {
    cat <<'EOF'
Usage: release-extensions.sh [options] [extension-name>...]

Without arguments: show preview of pending releases (push + tag).

Options:
  -t, --tag <tag>   Explicit tag (default: bump latest v* patch per repo)
  --all-ahead       Release every extension repo with unpushed commits
  --dry-run         Show actions without executing git push/tag
  -h, --help        Show this help

Examples:
  ./release-extensions.sh
  ./release-extensions.sh extension-view extension-view-twig extension-view-plates
  ./release-extensions.sh --all-ahead
  ./release-extensions.sh extension-config --tag v1.1.0
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        -t|--tag)
            EXPLICIT_TAG="${2:-}"
            shift 2
            ;;
        --all-ahead)
            ALL_AHEAD=1
            shift
            ;;
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        --)
            shift
            EXTENSIONS+=("$@")
            break
            ;;
        -*)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
        *)
            EXTENSIONS+=("$1")
            shift
            ;;
    esac
done

if ((${#EXTENSIONS[@]} == 0)) && ((ALL_AHEAD == 0)); then
    PREVIEW=1
fi

run_git() {
    local dir="$1"
    shift

    if ((DRY_RUN)); then
        echo "  [dry-run] git -C $dir $*"
        return 0
    fi

    git -C "$dir" "$@"
}

latest_version_tag() {
    local dir="$1"
    git -C "$dir" tag --sort=-v:refname | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -1 || true
}

bump_patch_tag() {
    local tag="$1"

    if [[ ! "$tag" =~ ^v([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
        echo "Cannot bump tag '$tag': expected format vMAJOR.MINOR.PATCH" >&2
        return 1
    fi

    local major="${BASH_REMATCH[1]}"
    local minor="${BASH_REMATCH[2]}"
    local patch="${BASH_REMATCH[3]}"
    echo "v${major}.${minor}.$((patch + 1))"
}

resolve_tag() {
    local dir="$1"
    local latest

    if [[ -n "$EXPLICIT_TAG" ]]; then
        echo "$EXPLICIT_TAG"
        return 0
    fi

    latest="$(latest_version_tag "$dir")"
    if [[ -z "$latest" ]]; then
        echo "v1.0.0"
        return 0
    fi

    bump_patch_tag "$latest"
}

extension_dir() {
    local name="$1"
    echo "$ROOT/$name"
}

is_extension_repo() {
    local dir="$1"
    [[ -d "$dir/.git" ]]
}

has_origin() {
    local dir="$1"
    git -C "$dir" remote get-url origin >/dev/null 2>&1
}

is_dirty() {
    local dir="$1"
    ! git -C "$dir" diff --quiet || ! git -C "$dir" diff --cached --quiet
}

ahead_count() {
    local dir="$1"

    if git -C "$dir" rev-parse "@{u}" >/dev/null 2>&1; then
        git -C "$dir" rev-list --count "@{u}..HEAD"
    else
        git -C "$dir" rev-list --count HEAD
    fi
}

collect_all_extension_names() {
    local names=()

    for dir in "$ROOT"/extension-*/; do
        [[ -d "$dir" ]] || continue
        names+=("$(basename "$dir")")
    done

    EXTENSIONS+=("${names[@]}")
}

collect_ahead_extensions() {
    for dir in "$ROOT"/extension-*/; do
        [[ -d "$dir/.git" ]] || continue

        if git -C "$dir" rev-parse "@{u}" >/dev/null 2>&1; then
            local ahead
            ahead="$(git -C "$dir" rev-list --count "@{u}..HEAD")"
            if ((ahead > 0)); then
                EXTENSIONS+=("$(basename "$dir")")
            fi
        fi
    done
}

inspect_extension() {
    local name="$1"
    local dir
    local ahead=0
    local latest_tag=""
    local next_tag=""
    local status="ready"

    dir="$(extension_dir "$name")"

    if ! is_extension_repo "$dir"; then
        printf '%s\n' "$name" "not-a-repo" "0" "" "" ""
        return 0
    fi

    if ! has_origin "$dir"; then
        printf '%s\n' "$name" "no-origin" "0" "" "" ""
        return 0
    fi

    if is_dirty "$dir"; then
        printf '%s\n' "$name" "dirty" "0" "" "" ""
        return 0
    fi

    ahead="$(ahead_count "$dir")"
    latest_tag="$(latest_version_tag "$dir")"

    if ((ahead == 0)); then
        printf '%s\n' "$name" "up-to-date" "0" "$latest_tag" "" ""
        return 0
    fi

    next_tag="$(resolve_tag "$dir")"
    if [[ -z "$next_tag" ]]; then
        printf '%s\n' "$name" "tag-error" "$ahead" "$latest_tag" "" ""
        return 0
    fi

    if git -C "$dir" rev-parse "$next_tag" >/dev/null 2>&1; then
        printf '%s\n' "$name" "tag-exists" "$ahead" "$latest_tag" "$next_tag" ""
        return 0
    fi

    printf '%s\n' "$name" "ready" "$ahead" "$latest_tag" "$next_tag"
}

print_preview() {
    local ready=0
    local blocked_names=()
    local idle_names=()
    local ready_rows=()

    for name in "${EXTENSIONS[@]}"; do
        local status ahead latest next
        mapfile -t _inspect < <(inspect_extension "$name")
        status="${_inspect[1]:-}"
        ahead="${_inspect[2]:-0}"
        latest="${_inspect[3]:-}"
        next="${_inspect[4]:-}"

        case "$status" in
            ready)
                ((ready++)) || true
                ready_rows+=("$name|$ahead|$next|$latest")
                ;;
            dirty) blocked_names+=("$name: uncommitted changes") ;;
            tag-exists) blocked_names+=("$name: tag already exists") ;;
            tag-error) blocked_names+=("$name: cannot resolve next tag") ;;
            no-origin) blocked_names+=("$name: no origin remote") ;;
            not-a-repo) blocked_names+=("$name: not a git repository") ;;
            up-to-date) idle_names+=("$name") ;;
        esac
    done

    echo "Release preview (branch: $BRANCH)"
    echo

    if ((ready > 0)); then
        echo "Ready to release:"
        printf '  %-28s %8s  %-12s %s\n' "EXTENSION" "COMMITS" "TAG" "LATEST"
        echo "  ----------------------------------------------------------------"

        local row name ahead next latest commits latest_label line dir
        for row in "${ready_rows[@]}"; do
            IFS='|' read -r name ahead next latest <<< "$row"
            dir="$(extension_dir "$name")"
            latest_label="${latest:-none}"
            printf '  %-28s push %-3s  -> %-10s (latest %s)\n' "$name" "$ahead" "$next" "$latest_label"
            while IFS= read -r line; do
                [[ -n "$line" ]] && echo "    $line"
            done < <(git -C "$dir" log --oneline "@{u}..HEAD" 2>/dev/null || git -C "$dir" log --oneline "-$ahead")
        done
        echo
    fi

    if ((${#blocked_names[@]} > 0)); then
        echo "Blocked:"
        for item in "${blocked_names[@]}"; do
            echo "  $item"
        done
        echo
    fi

    if ((${#idle_names[@]} > 0)); then
        echo "Up to date (nothing to push):"
        for name in "${idle_names[@]}"; do
            echo "  $name"
        done
        echo
    fi

    if ((ready == 0)); then
        echo "Nothing ready to release."
        echo
    fi

    echo "Run with extension names or --all-ahead to release."
}

release_extension() {
    local name="$1"
    local dir
    local tag
    local ahead=0

    dir="$(extension_dir "$name")"

    echo "========================================"
    echo "=> $name"
    echo "========================================"

    if ! is_extension_repo "$dir"; then
        echo "SKIP: not a git repository: $dir" >&2
        return 1
    fi

    if ! has_origin "$dir"; then
        echo "SKIP: remote 'origin' is not configured" >&2
        return 1
    fi

    if is_dirty "$dir"; then
        echo "SKIP: uncommitted changes present — commit first" >&2
        return 1
    fi

    ahead="$(ahead_count "$dir")"

    if ((ahead == 0)); then
        echo "SKIP: no unpushed commits on $BRANCH"
        return 2
    fi

    tag="$(resolve_tag "$dir")"
    if [[ -z "$tag" ]]; then
        return 1
    fi

    if git -C "$dir" rev-parse "$tag" >/dev/null 2>&1; then
        echo "SKIP: tag $tag already exists locally" >&2
        return 1
    fi

    echo "Pushing $ahead commit(s) to origin/$BRANCH, tag $tag"
    run_git "$dir" push origin "$BRANCH"
    run_git "$dir" tag "$tag"
    run_git "$dir" push origin "$tag"

    echo "Released $name @ $tag"
}

if ((PREVIEW)); then
    collect_all_extension_names
    print_preview
    exit 0
fi

if ((ALL_AHEAD)); then
    collect_ahead_extensions
fi

if ((${#EXTENSIONS[@]} == 0)); then
    echo "No extensions to release." >&2
    exit 1
fi

failed=()
skipped=()

for name in "${EXTENSIONS[@]}"; do
    release_extension "$name" || {
        case $? in
            2) skipped+=("$name") ;;
            *) failed+=("$name") ;;
        esac
    }
    echo
done

if ((${#skipped[@]} > 0)); then
    echo "Skipped (nothing to push):" "${skipped[*]}"
fi

if ((${#failed[@]} > 0)); then
    echo "Failed:" "${failed[*]}" >&2
    exit 1
fi

echo "All requested extensions released."
