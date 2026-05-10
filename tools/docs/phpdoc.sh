#!/usr/bin/env bash
#
# Generate the PHP API reference using phpDocumentor.
#
# Output lands at docs-site/public/api/, which VitePress serves verbatim
# under the /api/ route. The generated tree is intentionally gitignored.
#
# Usage:
#   bash tools/docs/phpdoc.sh
#
# In CI, the docs-deploy workflow runs the same steps inline so PHP and
# the .phar are not assumed to be present anywhere except the runner.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHPDOC_VERSION="v3.9.1"
PHAR_PATH="${REPO_ROOT}/.phpdoc-cache/phpDocumentor-${PHPDOC_VERSION}.phar"
PHAR_URL="https://github.com/phpDocumentor/phpDocumentor/releases/download/${PHPDOC_VERSION}/phpDocumentor.phar"

mkdir -p "$(dirname "${PHAR_PATH}")"

if [[ ! -f "${PHAR_PATH}" ]]; then
  echo "Downloading phpDocumentor ${PHPDOC_VERSION}..."
  curl -fsSL -o "${PHAR_PATH}" "${PHAR_URL}"
fi

cd "${REPO_ROOT}"
php "${PHAR_PATH}" --config "${REPO_ROOT}/phpdoc.dist.xml" "$@"

echo "Generated API reference at: docs-site/public/api/"
