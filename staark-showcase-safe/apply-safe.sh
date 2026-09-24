#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PAYLOAD="$HERE/payload"
THEME="$ROOT/themes/staark"

die() { echo "ERROR: $*" >&2; exit 1; }

[[ -d "$THEME" ]] || die "Theme directory not found: $THEME"

for protected in \
  "$THEME/functions.php" \
  "$THEME/theme.json" \
  "$THEME/templates/index.html" \
  "$THEME/templates/page.html" \
  "$THEME/templates/single.html"; do
  [[ -f "$protected" ]] || die "Protected required file missing: $protected"
done

git_blob_sha() {
  python3 - "$1" <<'PY'
from pathlib import Path
import hashlib, sys
p=Path(sys.argv[1])
d=p.read_bytes()
print(hashlib.sha1(b"blob "+str(len(d)).encode()+b"\0"+d).hexdigest())
PY
}

check_baseline() {
  local rel="$1"
  local expected="$2"
  local path="$ROOT/$rel"
  [[ -f "$path" ]] || die "Baseline file missing: $rel"
  local actual
  actual="$(git_blob_sha "$path")"
  [[ "$actual" == "$expected" ]] || die "Baseline changed: $rel ($actual != $expected). Pull/review before applying."
}

check_baseline "themes/staark/style.css" "c26cc491a6e27a3b2b44938e543c7a2264d9dcc2"
check_baseline "themes/staark/assets/css/theme.css" "dfa9d3087ad71283ae9135f713110c09425bf38c"
check_baseline "themes/staark/parts/header.html" "d8e0f815113ff8bfd902bc68ae0d53b82333c4db"
check_baseline "themes/staark/parts/footer.html" "8f591d31c842a73d074baa113dbee09c5dabcbea"
check_baseline "themes/staark/templates/front-page.html" "7749dc941e88db75fea452bc83bf7d69f16e9c26"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="$ROOT/.staark-backups/showcase-$STAMP"
mkdir -p "$BACKUP"

replace_files=(
  "themes/staark/style.css"
  "themes/staark/assets/css/theme.css"
  "themes/staark/parts/header.html"
  "themes/staark/parts/footer.html"
  "themes/staark/templates/front-page.html"
)

echo "Backup -> $BACKUP"
for rel in "${replace_files[@]}"; do
  mkdir -p "$BACKUP/$(dirname "$rel")"
  cp -a "$ROOT/$rel" "$BACKUP/$rel"
done

echo "Applying Staark Showcase patch (NO deletes)..."
while IFS= read -r -d '' src; do
  rel="${src#"$PAYLOAD/"}"
  dst="$ROOT/$rel"
  mkdir -p "$(dirname "$dst")"
  cp -a "$src" "$dst"
done < <(find "$PAYLOAD" -type f -print0)

echo "$BACKUP" > "$ROOT/.staark-backups/last-showcase-backup"

# Guard protected files after copy.
for protected in \
  "$THEME/functions.php" \
  "$THEME/theme.json" \
  "$THEME/templates/index.html" \
  "$THEME/templates/page.html" \
  "$THEME/templates/single.html"; do
  [[ -f "$protected" ]] || die "Protected file vanished unexpectedly: $protected"
done

python3 - <<PY
from pathlib import Path
theme = Path(r"$THEME")
assert "Theme Name: Staark" in (theme/"style.css").read_text()
assert (theme/"templates/index.html").exists()
assert (theme/"functions.php").exists()
print("Theme structure guard: OK")
PY

if command -v php >/dev/null 2>&1; then
  for f in "$THEME"/patterns/showcase-*.php; do
    php -l "$f" >/dev/null
  done
  echo "PHP pattern syntax: OK"
fi

if command -v git >/dev/null 2>&1 && git -C "$ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git -C "$ROOT" diff --check
fi

echo
echo "Staark Showcase applied safely."
echo "No files or directories were deleted."
echo "Backup: $BACKUP"
echo
echo "Recommended checks:"
echo "  npx wp-env run cli wp cache flush"
echo "  npx wp-env run cli wp theme list"
echo "  npx wp-env run cli wp theme status staark"
