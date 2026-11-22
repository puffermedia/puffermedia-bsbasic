#!/usr/bin/env bash
# Builds SCSS and zips a template folder into an installable Joomla package.
#
# Usage:
#   scripts/package-template.sh [template-dir]
#
# template-dir defaults to base-template. Pass a path under clients/ to
# package a bespoke client copy, e.g.:
#   scripts/package-template.sh clients/acme
#
# Output: dist/<extension-name>-<version>.zip (name/version read straight
# out of the folder's own templateDetails.xml, so it always matches what's
# actually inside the zip).
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="${1:-base-template}"
TEMPLATE_DIR="$REPO_ROOT/$TARGET"
MANIFEST="$TEMPLATE_DIR/templateDetails.xml"

if [ ! -f "$MANIFEST" ]; then
	echo "No templateDetails.xml found in $TEMPLATE_DIR" >&2
	exit 1
fi

"$REPO_ROOT/scripts/build-scss.sh" "$TARGET"

NAME=$(grep -m1 -oP '(?<=<name>).*(?=</name>)' "$MANIFEST")
VERSION=$(grep -m1 -oP '(?<=<version>).*(?=</version>)' "$MANIFEST")

if [ -z "$NAME" ] || [ -z "$VERSION" ]; then
	echo "Could not read <name>/<version> from $MANIFEST" >&2
	exit 1
fi

DIST_DIR="$REPO_ROOT/dist"
mkdir -p "$DIST_DIR"
ZIP_PATH="$DIST_DIR/${NAME}-${VERSION}.zip"

rm -f "$ZIP_PATH"

# scss/ is source only — Joomla never needs it at runtime. css/user.css is a
# site-side override file and must never ship (a stray copy from dev testing
# would overwrite a client's own on upgrade). Everything else in the template
# folder ships (component.php, error.php, index.php, templateDetails.xml, css,
# html, js, language, plus media/ if present).
(
	cd "$TEMPLATE_DIR"
	zip -rq "$ZIP_PATH" . -x "scss/*" -x "*.DS_Store" -x "*.css.map" -x "css/user.css"
)

echo "Packaged $TARGET -> $ZIP_PATH"
