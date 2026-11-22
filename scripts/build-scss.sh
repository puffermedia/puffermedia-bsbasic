#!/usr/bin/env bash
# Compiles scss/template.scss -> css/template.css for a template folder.
#
# Usage:
#   scripts/build-scss.sh [template-dir]           one-off compile
#   scripts/build-scss.sh [template-dir] --watch   recompile on save
#
# template-dir defaults to base-template. Pass a path under clients/ to
# build a bespoke client copy instead, e.g.:
#   scripts/build-scss.sh clients/acme
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SASS_BIN="$REPO_ROOT/tools/dart-sass/sass"

if [ ! -x "$SASS_BIN" ]; then
	echo "Dart Sass not found — running install-dart-sass.sh first..." >&2
	"$REPO_ROOT/scripts/install-dart-sass.sh"
fi

TARGET="${1:-base-template}"
WATCH=""
if [ "${2:-}" = "--watch" ] || [ "${1:-}" = "--watch" ]; then
	WATCH="--watch"
	[ "${1:-}" = "--watch" ] && TARGET="base-template"
fi

TEMPLATE_DIR="$REPO_ROOT/$TARGET"

if [ ! -f "$TEMPLATE_DIR/scss/template.scss" ]; then
	echo "No scss/template.scss found in $TEMPLATE_DIR" >&2
	exit 1
fi

mkdir -p "$TEMPLATE_DIR/css"

echo "Compiling $TARGET/scss/template.scss -> $TARGET/css/template.css"
"$SASS_BIN" \
	--style=expanded \
	--source-map \
	--no-error-css \
	$WATCH \
	"$TEMPLATE_DIR/scss/template.scss:$TEMPLATE_DIR/css/template.css"
