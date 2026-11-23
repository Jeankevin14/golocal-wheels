#!/usr/bin/env bash
# Check every PHP file for syntax errors.   Usage: bash scripts/lint.sh
set -euo pipefail
cd "$(dirname "$0")/.."
status=0
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || { echo "Syntax error in $f"; status=1; }
done < <(find src scripts -name '*.php' -print0)
[ $status -eq 0 ] && echo "PHP lint: all files OK"
exit $status
