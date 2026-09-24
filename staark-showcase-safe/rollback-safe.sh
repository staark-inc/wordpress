#!/usr/bin/env bash
set -euo pipefail
ROOT="${1:-$(pwd)}"
MARKER="$ROOT/.staark-backups/last-showcase-backup"
[[ -f "$MARKER" ]] || { echo "No showcase backup marker found."; exit 1; }
BACKUP="$(cat "$MARKER")"
[[ -d "$BACKUP" ]] || { echo "Backup directory missing: $BACKUP"; exit 1; }

# Restore only files that were replaced. This script intentionally deletes NOTHING.
for rel in \
  "themes/staark/style.css" \
  "themes/staark/assets/css/theme.css" \
  "themes/staark/parts/header.html" \
  "themes/staark/parts/footer.html" \
  "themes/staark/templates/front-page.html"; do
  [[ -f "$BACKUP/$rel" ]] && cp -a "$BACKUP/$rel" "$ROOT/$rel"
done

echo "Previous files restored."
echo "New showcase files were left in place intentionally; nothing was deleted."
