#!/usr/bin/env bash
set -euo pipefail

# Filesystem-only recovery for Staark managed runtime.
# It intentionally does not bootstrap WordPress, so it remains useful when PHP
# or a bad managed release prevents wp-admin/WP-CLI from loading.

COMMAND="${1:-status}"
WP_ROOT="${WP_ROOT:-/var/www/html}"
CONTENT_DIR="${WP_CONTENT_DIR:-$WP_ROOT/wp-content}"
PLUGIN_DIR="${WP_PLUGIN_DIR:-$CONTENT_DIR/plugins}"
ROOT="$CONTENT_DIR/staark-managed"
RELEASES="$ROOT/releases"
CURRENT="$ROOT/current"
PREVIOUS="$ROOT/previous"
MU_LOADER="$CONTENT_DIR/mu-plugins/staark-loader.php"
STANDARD_CORE="$PLUGIN_DIR/staark-core/staark-core.php"

release_id() {
  local link="$1"
  if [[ ! -e "$link" && ! -L "$link" ]]; then
    printf 'none'
    return
  fi
  local resolved
  resolved="$(readlink -f "$link" 2>/dev/null || true)"
  if [[ -n "$resolved" && "$resolved" == "$RELEASES"/* ]]; then
    basename "$resolved"
  else
    printf 'invalid'
  fi
}

atomic_link() {
  local link="$1"
  local target="$2"
  local tmp="${link}.next.$$"
  ln -s "$target" "$tmp"
  mv -Tf "$tmp" "$link"
}

status() {
  echo "Staark managed recovery status"
  echo "  wp-root:          $WP_ROOT"
  echo "  current:          $(release_id "$CURRENT")"
  echo "  previous:         $(release_id "$PREVIOUS")"
  echo "  mu-loader:        $([[ -r "$MU_LOADER" ]] && echo readable || echo missing)"
  echo "  standard plugin:  $([[ -r "$STANDARD_CORE" ]] && echo readable || echo missing)"
}

case "$COMMAND" in
  status)
    status
    ;;

  rollback)
    prev="$(release_id "$PREVIOUS")"
    cur="$(release_id "$CURRENT")"
    if [[ "$prev" == "none" || "$prev" == "invalid" ]]; then
      echo "ERROR: no valid previous release available" >&2
      exit 1
    fi
    [[ -r "$RELEASES/$prev/staark-core.php" ]] || { echo "ERROR: previous release core is unreadable" >&2; exit 1; }
    [[ -r "$RELEASES/$prev/deployment/staark-loader.php" ]] || { echo "ERROR: previous release loader is unreadable" >&2; exit 1; }

    mkdir -p "$(dirname "$MU_LOADER")"
    cp "$RELEASES/$prev/deployment/staark-loader.php" "${MU_LOADER}.next.$$"
    chmod 0644 "${MU_LOADER}.next.$$"
    mv -f "${MU_LOADER}.next.$$" "$MU_LOADER"
    atomic_link "$CURRENT" "releases/$prev"
    if [[ "$cur" != "none" && "$cur" != "invalid" ]]; then
      atomic_link "$PREVIOUS" "releases/$cur"
    fi
    echo "Rolled back current to $prev"
    status
    ;;

  fallback-plugin)
    [[ -r "$STANDARD_CORE" ]] || { echo "ERROR: standard plugin fallback is not readable at $STANDARD_CORE" >&2; exit 1; }
    if [[ -L "$CURRENT" ]]; then
      failed="$ROOT/current.failed.$(date -u +%Y%m%d%H%M%S)"
      mv "$CURRENT" "$failed"
      echo "Moved managed current aside to $failed"
    elif [[ -e "$CURRENT" ]]; then
      echo "ERROR: current exists but is not a symlink; refusing destructive recovery" >&2
      exit 1
    fi
    echo "Managed current is disabled. The MU-loader can now use the standard plugin fallback."
    status
    ;;

  restore-loader)
    cur="$(release_id "$CURRENT")"
    if [[ "$cur" == "none" || "$cur" == "invalid" ]]; then
      echo "ERROR: no valid current managed release" >&2
      exit 1
    fi
    source_loader="$RELEASES/$cur/deployment/staark-loader.php"
    [[ -r "$source_loader" ]] || { echo "ERROR: current release loader is unreadable" >&2; exit 1; }
    mkdir -p "$(dirname "$MU_LOADER")"
    cp "$source_loader" "${MU_LOADER}.next.$$"
    chmod 0644 "${MU_LOADER}.next.$$"
    mv -f "${MU_LOADER}.next.$$" "$MU_LOADER"
    echo "Restored MU-loader from $cur"
    ;;

  *)
    echo "Usage: WP_ROOT=/path/to/wordpress $0 <status|rollback|fallback-plugin|restore-loader>" >&2
    exit 2
    ;;
esac
