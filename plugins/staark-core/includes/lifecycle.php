<?php
/**
 * Staark Hub lifecycle management.
 *
 * RC rule: deactivation must stop background jobs without deleting client data.
 * Uninstall remains conservative by default; see uninstall.php for explicit
 * destructive cleanup opt-in.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Store the installed plugin version and first-install timestamp.
 */
function staark_hub_activate(): void
{
    if (get_option('staark_hub_installed_at', '') === '') {
        update_option('staark_hub_installed_at', current_time('mysql'), false);
    }

    update_option('staark_hub_installed_version', STAARK_HUB_VERSION, false);

    // Existing module schedulers remain the source of truth. Activation only
    // asks them to restore their jobs immediately instead of waiting for a
    // later frontend/admin request.
    if (function_exists('staark_hub_security_schedule_scan')) {
        staark_hub_security_schedule_scan();
    }
    if (function_exists('staark_hub_performance_schedule')) {
        staark_hub_performance_schedule();
    }
    if (function_exists('staark_hub_update_schedule')) {
        staark_hub_update_schedule();
    }
}

/**
 * Lightweight upgrade marker. Schema/content migrations can be added here in
 * later releases without scattering version checks across feature modules.
 */
function staark_hub_maybe_upgrade(): void
{
    $installed = (string) get_option('staark_hub_installed_version', '');

    if ($installed === STAARK_HUB_VERSION) {
        return;
    }

    if (get_option('staark_hub_installed_at', '') === '') {
        update_option('staark_hub_installed_at', current_time('mysql'), false);
    }

    update_option('staark_hub_installed_version', STAARK_HUB_VERSION, false);
}

/**
 * Stop Staark-owned scheduled work while keeping all settings, reports,
 * support tickets and connector identity intact for a safe reactivation.
 */
function staark_hub_deactivate(): void
{
    wp_clear_scheduled_hook('staark_hub_security_daily_scan');
    wp_clear_scheduled_hook('staark_hub_performance_daily_audit');
    wp_clear_scheduled_hook('staark_hub_update_check_twicedaily');
    wp_clear_scheduled_hook('staark_hub_starter_reconcile');
    wp_clear_scheduled_hook('staark_hub_support_refresh');
}

register_activation_hook(STAARK_HUB_PLUGIN_FILE, 'staark_hub_activate');
register_deactivation_hook(STAARK_HUB_PLUGIN_FILE, 'staark_hub_deactivate');
add_action('plugins_loaded', 'staark_hub_maybe_upgrade', 60);

/**
 * Return lifecycle information used by RC diagnostics and future Hub health
 * telemetry. No secrets are included.
 *
 * @return array<string,mixed>
 */
function staark_hub_lifecycle_status(): array
{
    return [
        'version' => STAARK_HUB_VERSION,
        'installedVersion' => (string) get_option('staark_hub_installed_version', ''),
        'installedAt' => (string) get_option('staark_hub_installed_at', ''),
        'securityCron' => (int) (wp_next_scheduled('staark_hub_security_daily_scan') ?: 0),
        'performanceCron' => (int) (wp_next_scheduled('staark_hub_performance_daily_audit') ?: 0),
        'updateCron' => (int) (wp_next_scheduled('staark_hub_update_check_twicedaily') ?: 0),
        'managed' => function_exists('staark_hub_managed_summary') ? staark_hub_managed_summary() : null,
    ];
}
