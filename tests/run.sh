#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_dir"

while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done < <(find . -type f -name '*.php' -not -path './.git/*' -print0)

php tests/gala_security_test.php
php tests/security_helpers_test.php
php tests/source_contract_test.php

node --check gala_scoresheet.js
node --check workbook_validation.js
node --check sw.js

php -r 'foreach (["manifest.json", "workbook_schema.json"] as $file) { json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR); } echo "JSON validation passed.\n";'
git diff --check

echo "All quality checks passed."
