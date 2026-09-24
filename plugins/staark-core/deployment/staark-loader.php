<?php
/**
 * Plugin Name: Staark Locked Loader
 * Description: Boots Staark Hub as a must-use runtime when Managed Mode is Locked.
 * Version: 0.6.0.9
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

$staark_managed_core = WP_CONTENT_DIR . '/staark-managed/current/staark-core.php';
$staark_plugin_core = WP_PLUGIN_DIR . '/staark-core/staark-core.php';
$staark_require_managed = defined('STAARK_HUB_REQUIRE_MANAGED_RUNTIME')
    && STAARK_HUB_REQUIRE_MANAGED_RUNTIME === true;

if (defined('STAARK_HUB_LOCKED_CORE_FILE')) {
    $staark_candidates = [
        ['file' => (string) STAARK_HUB_LOCKED_CORE_FILE, 'source' => 'override'],
    ];
} else {
    $staark_candidates = [
        ['file' => $staark_managed_core, 'source' => 'managed'],
    ];

    if (! $staark_require_managed) {
        $staark_candidates[] = ['file' => $staark_plugin_core, 'source' => 'plugin-fallback'];
    }
}

$staark_core_file = '';
$staark_runtime_source = '';
foreach ($staark_candidates as $staark_candidate) {
    $staark_file = (string) ($staark_candidate['file'] ?? '');
    if ($staark_file !== '' && is_file($staark_file) && is_readable($staark_file)) {
        $staark_core_file = $staark_file;
        $staark_runtime_source = (string) ($staark_candidate['source'] ?? 'unknown');
        break;
    }
}

if ($staark_core_file === '') {
    if (! defined('STAARK_HUB_MU_LOADER_ERROR')) {
        define('STAARK_HUB_MU_LOADER_ERROR', 'Staark Locked Loader could not read an allowed Staark Core entry file.');
    }

    error_log('[Staark Hub] Locked mode requested but no allowed Staark Core runtime is readable. Managed candidate: ' . $staark_managed_core);

    add_action('admin_notices', static function () use ($staark_require_managed): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="notice notice-error"><p><strong>Staark Locked Loader:</strong> Locked mode is configured, but Staark Core could not be loaded. <?php echo $staark_require_managed ? 'Managed-runtime fallback is disabled by server policy.' : 'Use server/WP-CLI recovery and verify the managed core path.'; ?></p></div>
        <?php
    });

    return;
}

if (! defined('STAARK_HUB_BOOTSTRAPPED_BY_MU')) {
    define('STAARK_HUB_BOOTSTRAPPED_BY_MU', true);
}
if (! defined('STAARK_HUB_MU_LOADER_VERSION')) {
    define('STAARK_HUB_MU_LOADER_VERSION', '0.6.0.9');
}
if (! defined('STAARK_HUB_MU_LOADER_FILE')) {
    define('STAARK_HUB_MU_LOADER_FILE', __FILE__);
}
if (! defined('STAARK_HUB_LOCKED_CORE_FILE_RESOLVED')) {
    define('STAARK_HUB_LOCKED_CORE_FILE_RESOLVED', $staark_core_file);
}
if (! defined('STAARK_HUB_RUNTIME_SOURCE')) {
    define('STAARK_HUB_RUNTIME_SOURCE', $staark_runtime_source);
}

// When Locked boots a managed/override runtime from a different filesystem
// path, WordPress must not compile the regular staark-core plugin afterwards.
// A top-level `return` guard inside staark-core.php cannot prevent duplicate
// declarations because unconditional PHP functions are registered while the
// file is compiled, before that guard executes.
//
// Keep the database activation state untouched. We only hide Staark Core from
// WordPress's active-plugin list for this bootstrap request, then remove the
// filters at plugins_loaded so diagnostics/recovery can still see the real
// activation state.
if ($staark_runtime_source !== 'plugin-fallback') {
    $staark_standard_plugin = 'staark-core/staark-core.php';

    $staark_filter_active_plugins = static function ($plugins) use ($staark_standard_plugin) {
        if (! is_array($plugins)) {
            return $plugins;
        }

        return array_values(
            array_filter(
                $plugins,
                static fn ($plugin): bool => (string) $plugin !== $staark_standard_plugin
            )
        );
    };

    $staark_filter_network_plugins = static function ($plugins) use ($staark_standard_plugin) {
        if (! is_array($plugins)) {
            return $plugins;
        }

        unset($plugins[$staark_standard_plugin]);
        return $plugins;
    };

    add_filter('option_active_plugins', $staark_filter_active_plugins, PHP_INT_MAX);
    add_filter('site_option_active_sitewide_plugins', $staark_filter_network_plugins, PHP_INT_MAX);

    add_action(
        'plugins_loaded',
        static function () use ($staark_filter_active_plugins, $staark_filter_network_plugins): void {
            remove_filter('option_active_plugins', $staark_filter_active_plugins, PHP_INT_MAX);
            remove_filter('site_option_active_sitewide_plugins', $staark_filter_network_plugins, PHP_INT_MAX);
        },
        PHP_INT_MIN
    );
}

require_once $staark_core_file;
