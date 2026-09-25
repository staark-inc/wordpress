#!/usr/bin/env bash
set -euo pipefail

PACKAGE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET="${1:-$PWD}"

cd "$TARGET"

CORE="plugins/staark-core/staark-core.php"

if [[ ! -f "$CORE" ]]; then
  echo "ERROR: Staark WordPress repository not found at: $TARGET"
  exit 1
fi

mkdir -p plugins/staark-core/includes plugins/staark-core/admin plugins/staark-core/assets
cp "$PACKAGE_DIR/files/plugins/staark-core/includes/forms.php" plugins/staark-core/includes/forms.php
cp "$PACKAGE_DIR/files/plugins/staark-core/admin/forms-page.php" plugins/staark-core/admin/forms-page.php
cp "$PACKAGE_DIR/files/plugins/staark-core/assets/forms.css" plugins/staark-core/assets/forms.css

python3 - <<'PY'
from pathlib import Path
import re

p = Path("plugins/staark-core/staark-core.php")
s = p.read_text()


def replace_once(text: str, old: str, new: str, label: str) -> str:
    if new in text:
        print(f"SKIP: {label} already applied")
        return text
    if old not in text:
        raise SystemExit(f"ERROR: could not find insertion point for {label}")
    print(f"OK: {label}")
    return text.replace(old, new, 1)

# Version bump.
s = re.sub(r'(\* Version:\s*)0\.6\.2\.0', r'\g<1>0.6.3.0', s, count=1)
s = s.replace("const STAARK_HUB_VERSION = '0.6.2.0';", "const STAARK_HUB_VERSION = '0.6.3.0';", 1)

s = replace_once(
    s,
    "require_once STAARK_HUB_PLUGIN_DIR . 'includes/performance-media.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'includes/lifecycle.php';",
    "require_once STAARK_HUB_PLUGIN_DIR . 'includes/performance-media.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'includes/forms.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'includes/lifecycle.php';",
    "forms runtime include",
)

s = replace_once(
    s,
    "require_once STAARK_HUB_PLUGIN_DIR . 'admin/performance-page.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'admin/managed-page.php';",
    "require_once STAARK_HUB_PLUGIN_DIR . 'admin/performance-page.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'admin/forms-page.php';\nrequire_once STAARK_HUB_PLUGIN_DIR . 'admin/managed-page.php';",
    "forms admin include",
)

s = replace_once(
    s,
    "    add_submenu_page(STAARK_HUB_SLUG, __('Performance', 'staark-core'), __('Performance', 'staark-core'), 'manage_options', 'staark-hub-performance', 'staark_hub_render_performance');\n    add_submenu_page(STAARK_HUB_SLUG, __('Support', 'staark-core'), __('Support', 'staark-core'), 'manage_options', 'staark-hub-support', 'staark_hub_render_support');",
    "    add_submenu_page(STAARK_HUB_SLUG, __('Performance', 'staark-core'), __('Performance', 'staark-core'), 'manage_options', 'staark-hub-performance', 'staark_hub_render_performance');\n    add_submenu_page(STAARK_HUB_SLUG, __('Forms & Submissions', 'staark-core'), __('Forms', 'staark-core'), 'manage_options', 'staark-hub-forms', 'staark_hub_render_forms');\n    add_submenu_page(STAARK_HUB_SLUG, __('Support', 'staark-core'), __('Support', 'staark-core'), 'manage_options', 'staark-hub-support', 'staark_hub_render_support');",
    "Forms admin menu",
)

forms_enqueue = """
    if (staark_hub_current_page() === 'staark-hub-forms') {
        wp_enqueue_style(
            'staark-hub-forms',
            staark_hub_runtime_url('assets/forms.css'),
            ['staark-hub-admin'],
            is_file(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                : STAARK_HUB_VERSION
        );
    }

"""
needle = "    if (staark_hub_current_page() === 'staark-hub-managed') {"
if forms_enqueue.strip() not in s:
    if needle not in s:
        raise SystemExit("ERROR: could not find admin enqueue insertion point")
    s = s.replace(needle, forms_enqueue + needle, 1)
    print("OK: Forms admin stylesheet")
else:
    print("SKIP: Forms admin stylesheet already applied")

s = replace_once(
    s,
    "            'performance' => staark_hub_module_enabled('performance', true),\n            'managed' => true,",
    "            'performance' => staark_hub_module_enabled('performance', true),\n            'forms' => true,\n            'managed' => true,",
    "connector forms capability",
)

s = replace_once(
    s,
    "        'performance' => function_exists('staark_hub_performance_summary') ? staark_hub_performance_summary() : null,\n        'managed' => function_exists('staark_hub_managed_summary') ? staark_hub_managed_summary() : null,",
    "        'performance' => function_exists('staark_hub_performance_summary') ? staark_hub_performance_summary() : null,\n        'forms' => function_exists('staark_hub_forms_summary') ? staark_hub_forms_summary() : null,\n        'managed' => function_exists('staark_hub_managed_summary') ? staark_hub_managed_summary() : null,",
    "connector forms summary",
)

p.write_text(s.rstrip() + "\n")
print("OK: Core version 0.6.3.0")
PY

echo
echo "== PHP lint =="
php -l plugins/staark-core/includes/forms.php
php -l plugins/staark-core/admin/forms-page.php
php -l plugins/staark-core/staark-core.php

echo
echo "== Patch hygiene =="
git diff --check

echo
echo "WP-6.3 Forms & Submissions applied successfully."
echo "Use shortcode: [staark_contact_form]"
echo "Admin: Staark Hub -> Forms"
