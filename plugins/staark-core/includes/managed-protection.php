<?php
/**
 * Staark Managed Mode — WordPress-level plugin protection.
 *
 * 6.0B protects Staark Core from client-side mutation while deliberately
 * keeping two recovery paths:
 * - explicit Staark operator accounts keep normal wp-admin control;
 * - WP-CLI/server access bypasses the UI protection.
 *
 * This is not filesystem DRM. Anyone with direct server/filesystem access can
 * still replace PHP files. Locked/MU-loader deployment is handled separately.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Emergency server-side bypass for a broken deployment.
 */
function staark_hub_managed_protection_bypassed(): bool
{
    return defined('STAARK_HUB_DISABLE_PROTECTION') && STAARK_HUB_DISABLE_PROTECTION === true;
}

/**
 * Whether the Managed/Locked policy requests WordPress-level protection.
 */
function staark_hub_managed_protection_enabled(): bool
{
    return staark_hub_is_managed() && ! staark_hub_managed_protection_bypassed();
}

/**
 * WP-CLI and cron must remain usable for recovery and managed maintenance.
 */
function staark_hub_managed_is_system_context(): bool
{
    if (defined('WP_CLI') && WP_CLI) {
        return true;
    }

    return function_exists('wp_doing_cron') && wp_doing_cron();
}

/**
 * Pure user-policy check used by diagnostics. Unlike request enforcement this
 * does not exempt WP-CLI, so an operator can inspect a client account safely.
 */
function staark_hub_managed_user_is_restricted(int $user_id): bool
{
    return staark_hub_managed_protection_enabled()
        && $user_id > 0
        && ! staark_hub_user_is_operator($user_id);
}

/**
 * Return true when the current/user-specific request should be restricted.
 */
function staark_hub_managed_client_restrictions_apply(int $user_id = 0): bool
{
    if (! staark_hub_managed_protection_enabled() || staark_hub_managed_is_system_context()) {
        return false;
    }

    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }

    return staark_hub_managed_user_is_restricted($user_id);
}

function staark_hub_managed_plugin_file(): string
{
    return staark_hub_standard_plugin_basename();
}

function staark_hub_managed_plugin_slug(): string
{
    return dirname(staark_hub_managed_plugin_file());
}

/**
 * Normalize plugin references from wp-admin/REST request parameters.
 */
function staark_hub_managed_normalize_plugin_ref(string $value): string
{
    $value = rawurldecode($value);
    $value = str_replace('\\', '/', $value);

    return ltrim(trim($value), '/');
}

function staark_hub_managed_is_core_plugin_ref(string $value): bool
{
    $value = staark_hub_managed_normalize_plugin_ref($value);
    $plugin = staark_hub_managed_plugin_file();
    $rest_plugin = preg_replace('/\.php$/', '', $plugin);

    return $value === $plugin || $value === $rest_plugin;
}

/**
 * Determine whether common plugin mutation request parameters target Staark.
 */
function staark_hub_managed_request_targets_core(): bool
{
    $candidates = [];

    foreach (['plugin', 'checked', 'plugins'] as $key) {
        if (! isset($_REQUEST[$key])) {
            continue;
        }

        $value = wp_unslash($_REQUEST[$key]);
        foreach ((array) $value as $candidate) {
            if (is_scalar($candidate)) {
                $candidates[] = (string) $candidate;
            }
        }
    }

    foreach ($candidates as $candidate) {
        if (staark_hub_managed_is_core_plugin_ref($candidate)) {
            return true;
        }
    }

    return false;
}

/**
 * Redirect a blocked wp-admin mutation back to a safe screen.
 */
function staark_hub_managed_block_admin_mutation(): void
{
    if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        wp_send_json_error(
            ['message' => 'Staark Hub is protected by Managed Mode.'],
            403
        );
    }

    $url = is_network_admin()
        ? network_admin_url('plugins.php')
        : admin_url('plugins.php');

    wp_safe_redirect(add_query_arg('staark_managed', 'protected', $url));
    exit;
}

/**
 * Hide the Staark plugin row from non-operator administrators.
 */
add_filter('all_plugins', static function (array $plugins): array {
    if (! staark_hub_managed_client_restrictions_apply()) {
        return $plugins;
    }

    unset($plugins[staark_hub_managed_plugin_file()]);

    return $plugins;
});

/**
 * Defense in depth if another plugin rebuilds or injects the action links.
 */
add_filter('plugin_action_links_' . plugin_basename(STAARK_HUB_PLUGIN_FILE), static function (array $actions): array {
    if (! staark_hub_managed_client_restrictions_apply()) {
        return $actions;
    }

    foreach (['deactivate', 'delete', 'edit'] as $action) {
        unset($actions[$action]);
    }

    return $actions;
});

/**
 * File editors are intentionally unavailable to client administrators on a
 * managed site. Other plugin/theme administration remains untouched.
 *
 * @param string[] $caps
 * @param mixed[]  $args
 * @return string[]
 */
add_filter('map_meta_cap', static function (array $caps, string $cap, int $user_id, array $args): array {
    if (! staark_hub_managed_client_restrictions_apply($user_id)) {
        return $caps;
    }

    if (in_array($cap, ['edit_plugins', 'edit_themes'], true)) {
        return ['do_not_allow'];
    }

    // Client administrators cannot delete, remove, demote or edit a Staark
    // operator account; otherwise they could take over operator authority.
    if (in_array($cap, ['delete_user', 'remove_user', 'promote_user', 'edit_user'], true)) {
        $target = isset($args[0]) ? (int) $args[0] : 0;
        if ($target > 0 && $target !== $user_id && (string) get_user_meta($target, STAARK_HUB_OPERATOR_META, true) === '1') {
            return ['do_not_allow'];
        }
    }

    return $caps;
}, 20, 4);

/**
 * Remove mutation/editor entry points after WordPress has built its menus.
 */
add_action('admin_menu', static function (): void {
    if (! staark_hub_managed_client_restrictions_apply()) {
        return;
    }

    remove_submenu_page('plugins.php', 'plugin-editor.php');
    remove_submenu_page('themes.php', 'theme-editor.php');
    remove_submenu_page(STAARK_HUB_SLUG, 'staark-hub-managed');
}, 999);

/**
 * Block direct/bulk wp-admin mutation URLs targeting Staark Core. This covers
 * Plugins and Update Core screens even if their UI is reached via a bookmark.
 */
add_action('admin_init', static function (): void {
    if (! staark_hub_managed_client_restrictions_apply()) {
        return;
    }

    if (staark_hub_current_page() === 'staark-hub-managed') {
        wp_safe_redirect(add_query_arg('staark_managed', 'restricted', admin_url('admin.php?page=' . STAARK_HUB_SLUG)));
        exit;
    }

    $actions = [];
    foreach (['action', 'action2'] as $key) {
        if (isset($_REQUEST[$key]) && is_scalar($_REQUEST[$key])) {
            $actions[] = sanitize_key((string) wp_unslash($_REQUEST[$key]));
        }
    }

    $blocked_actions = [
        'deactivate',
        'deactivate-selected',
        'delete',
        'delete-plugin',
        'delete-selected',
        'update-selected',
        'upgrade-plugin',
        'do-plugin-upgrade',
    ];

    if (array_intersect($actions, $blocked_actions) !== [] && staark_hub_managed_request_targets_core()) {
        staark_hub_managed_block_admin_mutation();
    }
}, 1);

add_action('admin_notices', static function (): void {
    if (! current_user_can('manage_options')) {
        return;
    }

    $state = isset($_GET['staark_managed']) ? sanitize_key(wp_unslash($_GET['staark_managed'])) : '';
    if ($state !== 'protected') {
        return;
    }
    ?>
    <div class="notice notice-warning is-dismissible"><p><strong>Staark Hub is protected by Managed Mode.</strong> Plugin deactivation, deletion and manual replacement are reserved for a Staark operator or server recovery.</p></div>
    <?php
});

/**
 * Do not advertise a Staark Core update to a client administrator. Cron and
 * operator requests still receive the untouched update transient.
 */
add_filter('site_transient_update_plugins', static function ($transient) {
    if (! staark_hub_managed_client_restrictions_apply() || ! is_object($transient)) {
        return $transient;
    }

    $plugin = staark_hub_managed_plugin_file();
    if (isset($transient->response) && is_array($transient->response)) {
        unset($transient->response[$plugin]);
    }

    return $transient;
});

/**
 * Block WordPress upgrader operations that explicitly target Staark Core.
 * Automatic cron maintenance and WP-CLI are intentionally exempt.
 */
add_filter('upgrader_pre_install', static function ($response, array $hook_extra) {
    if (is_wp_error($response) || ! staark_hub_managed_client_restrictions_apply()) {
        return $response;
    }

    $refs = [];
    if (isset($hook_extra['plugin']) && is_scalar($hook_extra['plugin'])) {
        $refs[] = (string) $hook_extra['plugin'];
    }
    if (isset($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
        foreach ($hook_extra['plugins'] as $plugin) {
            if (is_scalar($plugin)) {
                $refs[] = (string) $plugin;
            }
        }
    }

    foreach ($refs as $ref) {
        if (staark_hub_managed_is_core_plugin_ref($ref)) {
            return new WP_Error(
                'staark_managed_protected',
                'Staark Hub cannot be manually updated, replaced or removed by a client administrator while Managed Mode is active.'
            );
        }
    }

    return $response;
}, 20, 2);

/**
 * Uploaded ZIP replacement can reach the upgrader without an existing plugin
 * reference. Catch a package whose extracted top-level directory is staark-core.
 */
add_filter('upgrader_source_selection', static function ($source, string $remote_source, $upgrader, array $hook_extra) {
    unset($remote_source, $upgrader);

    if (is_wp_error($source) || ! is_string($source) || ! staark_hub_managed_client_restrictions_apply()) {
        return $source;
    }

    if (($hook_extra['type'] ?? '') !== 'plugin') {
        return $source;
    }

    $folder = basename(untrailingslashit(wp_normalize_path($source)));
    if ($folder !== staark_hub_managed_plugin_slug()) {
        return $source;
    }

    return new WP_Error(
        'staark_managed_protected',
        'A client administrator cannot overwrite the managed Staark Hub plugin package.'
    );
}, 20, 4);

/**
 * WordPress exposes plugin activation/deactivation/deletion through REST too.
 * Read-only REST requests remain available; mutations targeting Staark do not.
 */
add_filter('rest_pre_dispatch', static function ($result, $server, WP_REST_Request $request) {
    unset($server);

    if (! staark_hub_managed_client_restrictions_apply()) {
        return $result;
    }

    $method = strtoupper($request->get_method());
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return $result;
    }

    $plugin = $request->get_param('plugin');
    $route = staark_hub_managed_normalize_plugin_ref($request->get_route());
    $targeted = is_scalar($plugin) && staark_hub_managed_is_core_plugin_ref((string) $plugin);

    if (! $targeted) {
        $rest_plugin = preg_replace('/\.php$/', '', staark_hub_managed_plugin_file());
        $targeted = is_string($rest_plugin) && $rest_plugin !== '' && str_contains($route, $rest_plugin);
    }

    if (! $targeted) {
        return $result;
    }

    return new WP_Error(
        'staark_managed_protected',
        'Staark Hub is protected by Managed Mode.',
        ['status' => 403]
    );
}, 20, 3);

/**
 * Safe diagnostics for RC/Hub telemetry. No user identifiers are exposed.
 *
 * @return array{enabled:bool,bypassedByConfig:bool,plugin:string,hidePluginRow:bool,blockManualMutation:bool,blockFileEditors:bool,operatorBypass:bool,cliBypass:bool}
 */
function staark_hub_managed_protection_summary(): array
{
    return [
        'enabled' => staark_hub_managed_protection_enabled(),
        'bypassedByConfig' => staark_hub_managed_protection_bypassed(),
        'plugin' => staark_hub_managed_plugin_file(),
        'hidePluginRow' => true,
        'blockManualMutation' => true,
        'blockFileEditors' => true,
        'operatorBypass' => true,
        'cliBypass' => true,
    ];
}
