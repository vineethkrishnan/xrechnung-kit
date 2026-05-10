#!/usr/bin/env bash
#
# Build a Shopware Store-ready zip of the xrechnung-kit-shopware
# plugin from the monorepo source.
#
# The Store does NOT run composer on submitted plugins; the zip must
# include a production vendor/ directory. This script builds that
# vendor/, stages the source minus tooling files, and produces a
# versioned zip under .build/.
#
# Usage:
#   bash tools/store/build-shopware-zip.sh [VERSION]
#
# VERSION defaults to the version field in adapters/shopware/composer.json
# when present, else "0.0.0-dev".
#
# Pre-requisites:
#   - composer 2.x on PATH
#   - php 8.2+ matching the Shopware target
#   - rsync, zip
#
# After running, .build/XrechnungKitShopware-${VERSION}.zip is the file
# uploaded to the Shopware partner area.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PLUGIN_SOURCE="${REPO_ROOT}/adapters/shopware"
BUILD_DIR="${REPO_ROOT}/.build"
PLUGIN_NAME="XrechnungKitShopware"
STAGE_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

VERSION="${1:-}"
if [[ -z "${VERSION}" ]]; then
  if command -v jq >/dev/null 2>&1; then
    VERSION="$(jq -r '.version // "0.0.0-dev"' "${PLUGIN_SOURCE}/composer.json")"
  else
    VERSION="0.0.0-dev"
  fi
fi

echo "Building Shopware Store zip for ${PLUGIN_NAME} ${VERSION}"
echo "  source:  ${PLUGIN_SOURCE}"
echo "  output:  ${BUILD_DIR}/${PLUGIN_NAME}-${VERSION}.zip"

# Clean any previous build artefacts
rm -rf "${BUILD_DIR}"
mkdir -p "${STAGE_DIR}"

# Production vendor/. Side-effect free: composer install in the
# plugin's own directory, then we copy the result into the stage.
echo "Installing production composer dependencies..."
composer install \
  --working-dir="${PLUGIN_SOURCE}" \
  --no-dev \
  --optimize-autoloader \
  --no-progress \
  --no-interaction \
  --quiet

echo "Staging plugin files..."
rsync -a --delete \
  --exclude='tests/' \
  --exclude='cypress/' \
  --exclude='cypress.config.js' \
  --exclude='phpunit.xml.dist' \
  --exclude='STORE.md' \
  --exclude='composer.lock' \
  --exclude='.gitignore' \
  --exclude='.git*/' \
  --exclude='.idea/' \
  --exclude='.vscode/' \
  --exclude='*.swp' \
  "${PLUGIN_SOURCE}/" "${STAGE_DIR}/"

# Reset the development vendor/ now that we have a copy in the stage.
echo "Restoring dev dependencies in source tree..."
composer install \
  --working-dir="${PLUGIN_SOURCE}" \
  --no-progress \
  --no-interaction \
  --quiet

# Zip
ZIP_PATH="${BUILD_DIR}/${PLUGIN_NAME}-${VERSION}.zip"
echo "Zipping ${ZIP_PATH}..."
( cd "${BUILD_DIR}" && zip -rq "${PLUGIN_NAME}-${VERSION}.zip" "${PLUGIN_NAME}" )

ZIP_BYTES="$(wc -c < "${ZIP_PATH}" | tr -d ' ')"
echo ""
echo "Done."
echo "  ${ZIP_PATH}"
echo "  size: ${ZIP_BYTES} bytes"
echo ""
echo "Next steps:"
echo "  - Run the Shopware Cooperative Tool against this zip locally."
echo "  - Upload via https://account.shopware.com/ in the partner area."
echo "  - Add icon + screenshots in the partner area before publishing."
