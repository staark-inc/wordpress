#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PLUGIN="plugins/staark-core"

echo "== Staark WP 6.0 managed smoke =="
echo

echo "[1/5] PHP syntax"
if command -v php >/dev/null 2>&1; then
  while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
  done < <(find "$PLUGIN" -type f -name '*.php' -print0)
  echo "  OK: host PHP"
elif command -v npx >/dev/null 2>&1 && [[ -f package.json ]]; then
  if ! npx wp-env run cli php -v >/dev/null 2>&1; then
    echo "  FAIL: PHP is unavailable on the host and wp-env CLI PHP is not reachable" >&2
    exit 1
  fi

  while IFS= read -r -d '' file; do
    relative="${file#${PLUGIN}/}"
    npx wp-env run cli php -l "/var/www/html/wp-content/plugins/staark-core/${relative}" >/dev/null
  done < <(find "$PLUGIN" -type f -name '*.php' -print0)
  echo "  OK: wp-env PHP"
else
  echo "  FAIL: PHP is not installed and wp-env is unavailable" >&2
  exit 1
fi

echo "[2/5] JavaScript syntax"
if command -v node >/dev/null 2>&1; then
  node --check "$PLUGIN/assets/admin.js" >/dev/null
  node --check "$PLUGIN/assets/accessibility.js" >/dev/null
  echo "  OK"
else
  echo "  SKIP: node is not installed"
fi

echo "[3/5] Version header / constant"
HEADER_VERSION="$(sed -n 's/^ \* Version: //p' "$PLUGIN/staark-core.php" | head -n1)"
CONST_VERSION="$(sed -n "s/^const STAARK_HUB_VERSION = '\([^']*\)';/\1/p" "$PLUGIN/staark-core.php" | head -n1)"
if [[ -z "$HEADER_VERSION" || "$HEADER_VERSION" != "$CONST_VERSION" ]]; then
  echo "  FAIL: header=$HEADER_VERSION constant=$CONST_VERSION" >&2
  exit 1
fi
echo "  OK: $HEADER_VERSION"

echo "[4/5] Patch hygiene"
git diff --check
echo "  OK"

echo "[5/5] wp-env runtime"
if command -v npx >/dev/null 2>&1 && [[ -f package.json ]]; then
  npx wp-env run cli wp plugin is-active staark-core >/dev/null
  npx wp-env run cli wp staark rc-check
else
  echo "  SKIP: npx/package.json unavailable"
fi

cat <<'EOF'

Automated smoke checks complete.

Manual RC gates still required:
  - Overview / Website / Security / SEO / Performance / Support / Branding / Managed / Connect
  - browser console: zero new JS errors
  - PHP debug.log: zero new warnings/notices from Staark
  - responsive: 1440 / 1024 / 782 / 600 / 390 px
  - keyboard-only navigation and visible focus
  - deactivate -> verify cron removed and data preserved
  - reactivate -> verify cron restored and settings preserved
  - fresh install in a clean wp-env
  - upgrade from the previous checkpoint
  - uninstall default preserves data; destructive cleanup only with explicit constant
EOF
