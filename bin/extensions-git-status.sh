#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib/extensions.sh"

filter="all"
if [[ "${1:-}" == "--dirty" ]]; then
    filter="dirty"
elif [[ "${1:-}" == "--push" ]]; then
    filter="push"
elif [[ "${1:-}" == "--pending" ]]; then
    filter="pending"
elif [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    cat <<'EOF'
Usage:
  bin/extensions-git-status.sh            Show all extensions
  bin/extensions-git-status.sh --dirty    Uncommitted changes only
  bin/extensions-git-status.sh --push     Unpushed commits only
  bin/extensions-git-status.sh --pending  Dirty and/or unpushed
EOF
    exit 0
elif [[ -n "${1:-}" ]]; then
    echo "Unknown option: $1" >&2
    exit 1
fi

PUSH_NEEDED=()

repo_ahead_count() {
    local dir="$1"

    if ! git -C "$dir" rev-parse --abbrev-ref '@{upstream}' >/dev/null 2>&1; then
        echo "0"
        return 0
    fi

    git -C "$dir" rev-list --count '@{upstream}..HEAD' 2>/dev/null || echo "0"
}

repo_behind_count() {
    local dir="$1"

    if ! git -C "$dir" rev-parse --abbrev-ref '@{upstream}' >/dev/null 2>&1; then
        echo "0"
        return 0
    fi

    git -C "$dir" rev-list --count 'HEAD..@{upstream}' 2>/dev/null || echo "0"
}

repo_upstream_label() {
    local dir="$1"

    if ! git -C "$dir" rev-parse --abbrev-ref '@{upstream}' >/dev/null 2>&1; then
        echo "no upstream"
        return 0
    fi

    git -C "$dir" rev-parse --abbrev-ref '@{upstream}' 2>/dev/null
}

should_print_repo() {
    local dirty="$1"
    local ahead="$2"
    local filter_mode="$3"

    case "$filter_mode" in
        all) return 0 ;;
        dirty) [[ "$dirty" -eq 1 ]] ;;
        push) [[ "$ahead" -gt 0 ]] ;;
        pending) [[ "$dirty" -eq 1 || "$ahead" -gt 0 ]] ;;
        *) return 1 ;;
    esac
}

print_repo_status() {
    local dir="$1"
    local name
    name="$(extensions_basename "$dir")"

    if ! git -C "$dir" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        if [[ "$filter" == "all" ]]; then
            printf '[skip]  %-32s not a git repository\n' "$name"
        fi
        return 0
    fi

    local branch
    branch="$(git -C "$dir" branch --show-current 2>/dev/null || echo 'detached')"
    local porcelain
    porcelain="$(git -C "$dir" status --porcelain)"
    local dirty=0
    if [[ -n "$porcelain" ]]; then
        dirty=1
    fi

    local ahead
    ahead="$(repo_ahead_count "$dir")"
    local behind
    behind="$(repo_behind_count "$dir")"
    local upstream
    upstream="$(repo_upstream_label "$dir")"

    if ! should_print_repo "$dirty" "$ahead" "$filter"; then
        if [[ "$ahead" -gt 0 ]]; then
            PUSH_NEEDED+=("$name|$ahead|$branch|$upstream")
        fi
        return 0
    fi

    local tags=()
    if [[ "$dirty" -eq 1 ]]; then
        tags+=('dirty')
    fi
    if [[ "$ahead" -gt 0 ]]; then
        tags+=("ahead $ahead")
        PUSH_NEEDED+=("$name|$ahead|$branch|$upstream")
    fi
    if [[ "$behind" -gt 0 ]]; then
        tags+=("behind $behind")
    fi
    if [[ "$upstream" == "no upstream" ]]; then
        tags+=('no-upstream')
    fi
    if [[ "$dirty" -eq 0 && "$ahead" -eq 0 && "$behind" -eq 0 && "$upstream" != "no upstream" ]]; then
        tags+=('clean')
    fi

    local tag_line
    tag_line="$(IFS=', '; echo "${tags[*]}")"
    printf '[%-20s] %-32s %s (%s)\n' "$tag_line" "$name" "$branch" "$upstream"

    if [[ "$dirty" -eq 1 ]]; then
        git -C "$dir" status --short | sed 's/^/         /'
        echo
    fi
}

print_push_summary() {
    if [[ "${#PUSH_NEEDED[@]}" -eq 0 ]]; then
        if [[ "$filter" == "push" || "$filter" == "pending" ]]; then
            echo "Nothing to push."
        elif [[ "$filter" == "all" ]]; then
            echo "Push needed: none"
        fi
        return 0
    fi

    echo
    echo "Push needed:"
    local entry name ahead branch upstream
    for entry in "${PUSH_NEEDED[@]}"; do
        IFS='|' read -r name ahead branch upstream <<< "$entry"
        printf '  %-32s %s commit(s) on %s (%s)\n' "$name" "$ahead" "$branch" "$upstream"
    done
}

echo "Extensions git status"
echo "Directory: $(extensions_dir)"
echo

while IFS= read -r dir; do
    print_repo_status "$dir"
done < <(extensions_list_dirs)

print_push_summary

exit 0