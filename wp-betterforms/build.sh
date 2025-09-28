#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

pushd "$ROOT_DIR" >/dev/null
npm install
npm run build
composer install --no-dev --prefer-dist --no-progress
ZIP_NAME="wp-betterforms.zip"
rm -f "$ZIP_NAME"
zip -r "$ZIP_NAME" . -x "*.git*" "node_modules/*" "tests/*" "src/*" "package-lock.json" "composer.lock" "build.sh" "*.zip"
popd >/dev/null

echo "Created $ROOT_DIR/$ZIP_NAME"
