#!/usr/bin/env bash
# Publish a tagged release of this private repo to the public repo.
#
# Copies the committed state of a release (default: main) into the public
# repo, leaving out private files (CLAUDE.md / CLAUDE.local.md in any folder,
# docs/, .claude/), then commits it there as "Release v<version>" with that
# version's CHANGELOG section as the message, and tags it v<version>.
# It never pushes: review the commit, then push by hand.
#
# Usage (from the repo root):
#   scripts/export-public.sh [--ref <ref>] [--dest <dir>] [--no-commit]
#
#   --ref        branch or tag to export (default: main). Its commit must
#                carry the tag v<version>, version read from its
#                base-template/templateDetails.xml.
#   --dest       public repo folder (default: ../puffermedia-bsbasic-public;
#                created and git-initialised on main if missing)
#   --no-commit  dry run: list what would change in the public repo,
#                without touching it

set -euo pipefail

repo_root=$(git rev-parse --show-toplevel)
ref=main
dest="$repo_root/../puffermedia-bsbasic-public"
commit=1

while [ $# -gt 0 ]; do
    case "$1" in
        --ref) ref=${2:?--ref needs a value}; shift 2 ;;
        --dest) dest=${2:?--dest needs a value}; shift 2 ;;
        --no-commit) commit=0; shift ;;
        -h|--help) sed -n '2,20p' "$0"; exit 0 ;;
        *) echo "Unknown option: $1" >&2; exit 2 ;;
    esac
done

die() { echo "Error: $*" >&2; exit 1; }

cd "$repo_root"

# --- Release checks (private repo) -----------------------------------------
commit_id=$(git rev-parse --verify --quiet "$ref^{commit}") \
    || die "'$ref' is not a branch, tag or commit."

version=$(git show "$ref:base-template/templateDetails.xml" \
    | sed -n 's:.*<version>\(.*\)</version>.*:\1:p' | head -n 1)
[ -n "$version" ] || die "No <version> in $ref:base-template/templateDetails.xml."
tag="v$version"

tag_commit=$(git rev-parse --verify --quiet "$tag^{commit}") \
    || die "Tag $tag does not exist. Release first: merge dev into main, then 'git tag $tag main'."
[ "$tag_commit" = "$commit_id" ] \
    || die "$tag points at $(git rev-parse --short "$tag_commit"), not at $ref ($(git rev-parse --short "$commit_id")). Is $ref the release?"

# --- Build the export in a temp folder -------------------------------------
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

git archive "$commit_id" | tar -x -C "$tmp"
find "$tmp" \( -name CLAUDE.md -o -name CLAUDE.local.md \) -type f -delete
rm -rf "$tmp/docs" "$tmp/.claude"

# Stop before touching the public repo if anything local slipped through:
# home or web-root paths and *.local hostnames. The bracketed letters keep
# the pattern from matching this file's own text; CLAUDE.local.md is a
# filename, not a host.
leak_pattern='/home/[a-z]|/var/[w]ww|[A-Za-z0-9-]+\.[l]ocal\b'
if leaks=$(grep -rnIE "$leak_pattern" "$tmp" | grep -v 'CLAUDE\.local\.md'); then
    echo "${leaks//$tmp\//}" >&2
    die "Local site names or paths found above. Fix them in the private repo, make a new release, and run again."
fi

# --- Public repo -----------------------------------------------------------
mkdir -p "$dest"
dest=$(cd "$dest" && pwd)
if [ ! -d "$dest/.git" ]; then
    git -C "$dest" init -q -b main
    echo "Initialised new git repo in $dest"
fi

# A repo with no commits yet has nothing to protect; otherwise refuse to
# overwrite uncommitted work.
if git -C "$dest" rev-parse --verify --quiet HEAD >/dev/null; then
    [ -z "$(git -C "$dest" status --porcelain)" ] \
        || die "The public repo has uncommitted changes; commit or discard them first."
fi
if git -C "$dest" rev-parse --verify --quiet "refs/tags/$tag" >/dev/null; then
    die "The public repo already has tag $tag."
fi

if [ "$commit" -eq 0 ]; then
    echo "Dry run for $ref ($tag): changes the export would make in $dest"
    rsync -a --delete --dry-run --itemize-changes --exclude=/.git/ "$tmp"/ "$dest"/
    echo "--no-commit: nothing was changed."
    exit 0
fi

rsync -a --delete --exclude=/.git/ "$tmp"/ "$dest"/
git -C "$dest" add -A

echo "Exported $ref ($tag, $(git rev-parse --short "$commit_id")) to $dest"

if git -C "$dest" diff --cached --quiet; then
    echo "No changes since the last export; nothing to commit."
    exit 0
fi

# Commit message: "Release vX.Y.Z" plus that version's CHANGELOG section.
notes=$(awk -v v="$version" '
    $0 ~ "^## " && found { exit }
    index($0, "## " v " ") == 1 || $0 == "## " v { found = 1; next }
    found { print }
' "$tmp/CHANGELOG.md" | sed -e '/./,$!d' -e '/^---$/d')

{
    echo "Release $tag"
    if [ -n "$notes" ]; then
        echo
        echo "$notes"
    fi
} > "$tmp/.commit-msg"

git -C "$dest" commit -q -F "$tmp/.commit-msg"
git -C "$dest" tag -a "$tag" -m "$tag"

echo "Committed and tagged $tag in the public repo:"
git -C "$dest" log --oneline -1
echo
echo "Review:  git -C \"$dest\" show --stat"
echo "Publish: git -C \"$dest\" push origin main \"$tag\""
