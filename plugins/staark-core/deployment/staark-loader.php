<?php
/**
 * Plugin Name: Staark Locked Loader
 * Description: Boots Staark Hub as a must-use runtime when Managed Mode is Locked.
 * Version: 0.6.0.5
 * Author: Staark Inc.
 */

if (! defined('ABSPATH')) {
    exit;
}

// Emergency server recovery. This deliberately leaves the saved policy alone,
// so diagnostics will expose that Locked mode is not currently MU-enforced.
if (defined('STAARK_HUB_DISABLE_MU_LOADER') && STAARK_HUB_DISABLE_MU_LOADER === true) {
    return;
}

$staark_mode = defined('STAARK_HUB_MANAGED_MODE')
    ? strtolower(trim((string) STAARK_HUB_MANAGED_MODE))
    : strtolower(trim((string) get_option('staark_hub_managed_mode', 'normal')));

if (! in_array($staark_mode, ['normal', 'managed', 'locked'], true)) {
    $staark_mode = 'normal';
}

// Normal and Managed continue to use the regular WordPress plugin lifecycle.
// Only Locked mode is made independent of active_plugins.
if ($staark_mode !== 'locked') {
    return;
}

$staark_core_file = defined('STAARK_HUB_LOCKED_CORE_FILE')
    ? (string) STAARK_HUB_LOCKED_CORE_FILE
    : WP_PLUGIN_DIR . '/staark-core/staark-core.php';

if (! is_file($staark_core_file) || ! is_readable($staark_core_file)) {
    if (! defined('STAARK_HUB_MU_LOADER_ERROR')) {
        define('STAARK_HUB_MU_LOADER_ERROR', 'Staark Locked Loader could not read the Staark Core entry file.');
    }

    error_log('[Staark Hub] Locked mode requested but Staark Core is not readable at: ' . $staark_core_file);

    add_action('admin_notices', static function (): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="notice notice-error"><p><strong>Staark Locked Loader:</strong> Locked mode is configured, but Staark Core could not be loaded. Use server/WP-CLI recovery and verify the managed core path.</p></div>
        <?php
    });

    return;
}

if (! defined('STAARK_HUB_BOOTSTRAPPED_BY_MU')) {
    define('STAARK_HUB_BOOTSTRAPPED_BY_MU', true);
}
if (! defined('STAARK_HUB_MU_LOADER_VERSION')) {
    define('STAARK_HUB_MU_LOADER_VERSION', '0.6.0.5');
}
if (! defined('STAARK_HUB_MU_LOADER_FILE')) {
    define('STAARK_HUB_MU_LOADER_FILE', __FILE__);
}

require_once $staark_core_file;
