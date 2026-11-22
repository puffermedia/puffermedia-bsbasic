#!/usr/bin/env bash
# Downloads the standalone Dart Sass CLI into tools/dart-sass.
# No Node, npm, or root access required — just a single self-contained binary.
# Re-run this after cloning the repo fresh, or to bump the pinned version below.
set -euo pipefail

VERSION="1.102.0"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLS_DIR="$REPO_ROOT/tools"

case "$(uname -s)" in
	Linux) OS="linux" ;;
	Darwin) OS="macos" ;;
	*) echo "Unsupported OS: $(uname -s)" >&2; exit 1 ;;
esac

case "$(uname -m)" in
	x86_64) ARCH="x64" ;;
	aarch64|arm64) ARCH="arm64" ;;
	*) echo "Unsupported architecture: $(uname -m)" >&2; exit 1 ;;
esac

URL="https://github.com/sass/dart-sass/releases/download/${VERSION}/dart-sass-${VERSION}-${OS}-${ARCH}.tar.gz"

echo "Downloading Dart Sass ${VERSION} for ${OS}-${ARCH}..."
mkdir -p "$TOOLS_DIR"
curl -sL -o "$TOOLS_DIR/dart-sass.tar.gz" "$URL"
tar -xzf "$TOOLS_DIR/dart-sass.tar.gz" -C "$TOOLS_DIR"
rm "$TOOLS_DIR/dart-sass.tar.gz"

"$TOOLS_DIR/dart-sass/sass" --version
echo "Installed to $TOOLS_DIR/dart-sass/sass"
