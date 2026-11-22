#!/usr/bin/env bash
# Builds one installable Joomla package holding the template and the
# System - BS Basic Open Graph plugin.
#
# Usage (from the repo root):
#   scripts/package-bundle.sh
#
# Runs scripts/package-template.sh first (builds the CSS and the template zip),
# zips plugins/plg_system_bsbasicog, then wraps both with
# package/pkg_puffermedia_bsbasic.xml.
#
# Output: dist/pkg_puffermedia_bsbasic-<version>.zip. The version is the
# template's, read from base-template/templateDetails.xml.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$REPO_ROOT/plugins/plg_system_bsbasicog"
PKG_MANIFEST="$REPO_ROOT/package/pkg_puffermedia_bsbasic.xml"
TEMPLATE_MANIFEST="$REPO_ROOT/base-template/templateDetails.xml"
DIST_DIR="$REPO_ROOT/dist"

for f in "$PKG_MANIFEST" "$TEMPLATE_MANIFEST" "$PLUGIN_DIR/bsbasicog.xml"; do
	if [ ! -f "$f" ]; then
		echo "Missing $f" >&2
		exit 1
	fi
done

"$REPO_ROOT/scripts/package-template.sh"

VERSION=$(grep -m1 -oP '(?<=<version>).*(?=</version>)' "$TEMPLATE_MANIFEST")
TEMPLATE_ZIP="$DIST_DIR/puffermedia-bsbasic-${VERSION}.zip"
ZIP_PATH="$DIST_DIR/pkg_puffermedia_bsbasic-${VERSION}.zip"

if [ ! -f "$TEMPLATE_ZIP" ]; then
	echo "Template zip not found: $TEMPLATE_ZIP" >&2
	exit 1
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$STAGE/packages"
cp "$TEMPLATE_ZIP" "$STAGE/packages/tpl_puffermedia-bsbasic.zip"
(
	cd "$PLUGIN_DIR"
	zip -rq "$STAGE/packages/plg_system_bsbasicog.zip" . -x "*.DS_Store"
)
sed "s|<version>.*</version>|<version>${VERSION}</version>|" "$PKG_MANIFEST" > "$STAGE/pkg_puffermedia_bsbasic.xml"

mkdir -p "$DIST_DIR"
rm -f "$ZIP_PATH"
(
	cd "$STAGE"
	zip -rq "$ZIP_PATH" .
)

echo "Packaged bundle -> $ZIP_PATH"
