<?php
/**
 * Staark Hub uninstall policy.
 *
 * Default: preserve client settings, connector identity, reports, form
 * submissions and support history. This makes accidental uninstall/reinstall
 * recoverable.
 *
 * Full data removal is intentionally opt-in:
 *   define('STAARK_HUB_REMOVE_DATA_ON_UNINSTALL', true);
 *
 * On multisite every site is cleaned.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function staark_hub_uninstall_site(bool $remove_data): void
{
    foreach ([
        'staark_hub_security_daily_scan',
        'staark_hub_performance_daily_audit',
        'staark_hub_update_check_twicedaily',
        'staark_hub_starter_reconcile',
        'staark_hub_support_refresh',
    ] as $hook) {
        wp_clear_scheduled_hook($hook);
    }
    delete_transient('staark_hub_update_manifest');
    delete_transient('staark_hub_fb_counts');
    delete_transient('staark_hub_starter_broken');

    if (! $remove_data) {
        return;
    }

    $options = [
        'staark_hub_connection',
        'staark_hub_branding',
        'staark_hub_modules',
        'staark_hub_security_settings',
        'staark_hub_security_last_scan',
        'staark_hub_seo_settings',
        'staark_hub_performance_settings',
        'staark_hub_performance_report',
        'staark_hub_installed_version',
        'staark_hub_installed_at',
        'staark_hub_managed_mode',
        'staark_hub_update_state',
        'staark_hub_update_finalize_lock',
        'staark_hub_autoload_schema',
        // Forms & S-Hub Inbox (the forms settings hold the SMTP password).
        'staark_hub_forms_settings',
        'staark_hub_form_registry',
        'staark_hub_forms_notifications',
        'staark_hub_fb_schema',
        // First Install and managed starter pages.
        'staark_hub_first_install_state',
        'staark_hub_starter_managed_context',
        'staark_hub_starter_managed_last_run',
        'staark_hub_starter_managed_gate',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Per-content metadata owned by Staark.
    foreach (['title', 'description', 'canonical', 'noindex'] as $key) {
        delete_metadata('post', 0, '_staark_seo_' . $key, '', true);
    }
    delete_metadata('post', 0, '_staark_webp_state', '', true);

    // Support tickets and form submissions (personal data) are client
    // history and only deleted with the explicit opt-in above.
    foreach (['staark_ticket', 'staark_submission'] as $post_type) {
        do {
            $ids = get_posts(
                [
                    'post_type' => $post_type,
                    'post_status' => 'any',
                    'posts_per_page' => 200,
                    'fields' => 'ids',
                    'suppress_filters' => true,
                ]
            );
            $deleted = 0;
            foreach ($ids as $id) {
                if (wp_delete_post((int) $id, true)) {
                    ++$deleted;
                }
            }
        } while ($ids !== [] && $deleted > 0);
    }

    // Hashed rate-limit / throttle transients.
    global $wpdb;
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        foreach (['staark_login_', 'staark_form_rate_', 'staark_fb_ar_', 'staark_hub_'] as $prefix) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_' . $prefix) . '%',
                    $wpdb->esc_like('_transient_timeout_' . $prefix) . '%'
                )
            );
        }
    }
}

$staark_hub_remove_data = defined('STAARK_HUB_REMOVE_DATA_ON_UNINSTALL') && STAARK_HUB_REMOVE_DATA_ON_UNINSTALL === true;

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $staark_hub_site_id) {
        switch_to_blog((int) $staark_hub_site_id);
        staark_hub_uninstall_site($staark_hub_remove_data);
        restore_current_blog();
    }
} else {
    staark_hub_uninstall_site($staark_hub_remove_data);
}

// Staark operator markers are plugin-owned authority metadata (user meta is
// network-wide). Kept on normal uninstall, removed on explicit cleanup.
if ($staark_hub_remove_data) {
    delete_metadata('user', 0, '_staark_hub_operator', '', true);
}
