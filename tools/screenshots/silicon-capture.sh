#!/usr/bin/env bash
#
# Generate the CLI / code screenshots used by the docs walkthrough.
#
# Requirements:
#   silicon (https://github.com/Aloxaf/silicon) installed and on PATH
#
# Usage:
#   bash tools/screenshots/silicon-capture.sh
#
# Output: PNGs under docs-site/public/walkthrough/

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUT_DIR="${REPO_ROOT}/docs-site/public/walkthrough"
SNIPPET_DIR="${REPO_ROOT}/tools/screenshots/snippets"

mkdir -p "${OUT_DIR}"

# Shared silicon flags. Light formal theme, restrained palette to match the
# docs site's German-legal styling. Window controls dropped; padding tightened.
SHARED=(
  --theme "OneHalfLight"
  --background "#e8e0d0"
  --font "Menlo=14"
  --no-window-controls
  --pad-horiz 28
  --pad-vert 28
  --shadow-blur-radius 8
  --shadow-color "#00000022"
  --shadow-offset-x 0
  --shadow-offset-y 4
)

capture() {
  local lang="$1"
  local input="$2"
  local output="$3"
  echo "  -> ${output}"
  silicon "${SHARED[@]}" --language "${lang}" --output "${output}" "${input}"
}

echo "Generating walkthrough screenshots into ${OUT_DIR}..."

capture php       "${SNIPPET_DIR}/01-mapping-data.php"      "${OUT_DIR}/01-mapping-data.png"
capture bash      "${SNIPPET_DIR}/02-run-generate.txt"      "${OUT_DIR}/02-run-generate.png"
capture xml       "${SNIPPET_DIR}/03-xml-output.xml"        "${OUT_DIR}/03-xml-output.png"
capture php       "${SNIPPET_DIR}/04-validator.php"         "${OUT_DIR}/04-validator.png"
capture bash      "${SNIPPET_DIR}/05-quarantine.txt"        "${OUT_DIR}/05-quarantine.png"
capture php       "${SNIPPET_DIR}/06-laravel-job.php"       "${OUT_DIR}/06-laravel-job.png"

echo "Done. ${OUT_DIR} now holds:"
ls -1 "${OUT_DIR}"
