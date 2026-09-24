<?php
/**
 * Staark Hub uninstall policy.
 *
 * Default: preserve client settings, connector identity, reports and support
 * history. This makes accidental uninstall/reinstall recoverable.
 *
 * Full data removal is intentionally opt-in:
 *   define('STAARK_HUB_REMOVE_DATA_ON_UNINSTALL', true);
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('staark_hub_security_daily_scan');
wp_clear_scheduled_hook('staark_hub_performance_daily_audit');
wp_clear_scheduled_hook('staark_hub_update_check_twicedaily');
delete_transient('staark_hub_update_manifest');

if (! defined('STAARK_HUB_REMOVE_DATA_ON_UNINSTALL') || STAARK_HUB_REMOVE_DATA_ON_UNINSTALL !== true) {
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
];

foreach ($options as $option) {
    delete_option($option);
}

// Staark operator markers are plugin-owned authority metadata. They are kept on
// normal uninstall, but removed during explicit destructive cleanup.
delete_metadata('user', 0, '_staark_hub_operator', '', true);

// Per-content SEO metadata belongs to Staark SEO and is removed only in the
// explicit destructive-uninstall mode.
foreach (['title', 'description', 'canonical', 'noindex'] as $key) {
    delete_metadata('post', 0, '_staark_seo_' . $key, '', true);
}

// Support tickets are client history, so they are also deleted only after the
// explicit full-removal opt-in above.
$ticket_ids = get_posts(
    [
        'post_type' => 'staark_ticket',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]
);

foreach ($ticket_ids as $ticket_id) {
    wp_delete_post((int) $ticket_id, true);
}

// Login-protection transients are deliberately short-lived and hashed. Remove
// any still present when full cleanup is explicitly requested.
global $wpdb;
if (isset($wpdb) && $wpdb instanceof wpdb) {
    $like_transient = $wpdb->esc_like('_transient_staark_login_') . '%';
    $like_timeout = $wpdb->esc_like('_transient_timeout_staark_login_') . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like_transient,
            $like_timeout
        )
    );
}
