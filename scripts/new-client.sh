#!/usr/bin/env bash
# Scaffolds a bespoke client template by copying base-template into clients/<slug>
# and renaming every "puffermedia-bsbasic" (the base extension name) and
# "bsbasic" / "BSBASIC" occurrence (extension name, CSS
# classes, JS ids, language string prefixes, language filenames) to the new
# slug. The result is a fully independent copy — editing it never touches
# base-template, and pulling base updates is a manual re-diff, not a merge.
#
# Only fork a client this way for customization that goes beyond what the
# template's admin params already cover (logo, accent color, fonts, layout
# width, sticky header, back-to-top, custom head/body code) — most clients
# won't need this at all.
#
# Usage:
#   scripts/new-client.sh <slug>
#
# <slug> must be lowercase letters/numbers only (Joomla template folder +
# extension name rules), e.g. acme, riverside, oakstreet. It becomes the
# extension's technical name everywhere (manifest <name>, folder, CSS
# classes, language prefixes) — same one-name convention base-template
# itself uses, so there's no separate "display name" to keep in sync.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_DIR="$REPO_ROOT/base-template"

SLUG="${1:-}"

if [ -z "$SLUG" ]; then
	echo "Usage: scripts/new-client.sh <slug>" >&2
	exit 1
fi

if ! [[ "$SLUG" =~ ^[a-z][a-z0-9]*$ ]]; then
	echo "Error: <slug> must be lowercase letters/numbers only, starting with a letter (got: $SLUG)" >&2
	exit 1
fi

DEST_DIR="$REPO_ROOT/clients/$SLUG"

if [ -e "$DEST_DIR" ]; then
	echo "Error: $DEST_DIR already exists" >&2
	exit 1
fi

UPPER_SLUG=$(echo "$SLUG" | tr '[:lower:]' '[:upper:]')

echo "Scaffolding client '$SLUG' from base-template..."
cp -r "$BASE_DIR" "$DEST_DIR"

# Rename the two language files first (bsbasic -> slug in the filename itself)
mv "$DEST_DIR/language/en-GB/en-GB.tpl_puffermedia-bsbasic.ini" "$DEST_DIR/language/en-GB/en-GB.tpl_${SLUG}.ini"
mv "$DEST_DIR/language/en-GB/en-GB.tpl_puffermedia-bsbasic.sys.ini" "$DEST_DIR/language/en-GB/en-GB.tpl_${SLUG}.sys.ini"

# Replace the full extension name first (manifest <name>, language file
# paths), then any remaining bsbasic/BSBASIC (CSS classes, ids, language
# keys). Order matters: otherwise puffermedia-bsbasic becomes
# puffermedia-<slug>.
FILES=$(grep -rlI -e "bsbasic" -e "BSBASIC" "$DEST_DIR" || true)
for f in $FILES; do
	sed -i "s/puffermedia-bsbasic/${SLUG}/g; s/BSBASIC/${UPPER_SLUG}/g; s/bsbasic/${SLUG}/g" "$f"
done

# Reset version to 1.0.0 for the new bespoke line.
sed -i "0,/<version>.*<\/version>/{s//<version>1.0.0<\/version>/}" "$DEST_DIR/templateDetails.xml"

# Regenerate compiled CSS for the renamed copy
"$REPO_ROOT/scripts/build-scss.sh" "clients/$SLUG"

echo
echo "Done. New client template at: $DEST_DIR"
echo "  - Extension/template name: $SLUG"
echo "  - Edit clients/$SLUG/scss/*.scss, then: scripts/build-scss.sh clients/$SLUG"
echo "  - Package for install:            scripts/package-template.sh clients/$SLUG"
