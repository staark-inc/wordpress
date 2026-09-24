<?php
/**
 * Staark Hub admin accessibility and responsive cleanup assets.
 *
 * Kept outside staark-core.php so RC-stage presentation fixes remain isolated
 * from the business modules (Security, SEO and Performance).
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Load the final cleanup layer on every Staark Hub screen.
 */
function staark_hub_enqueue_accessibility_assets(): void
{
    if (! staark_hub_is_admin_page()) {
        return;
    }

    $base_url = plugin_dir_url(STAARK_HUB_PLUGIN_FILE);

    wp_enqueue_style(
        'staark-hub-cleanup',
        $base_url . 'assets/cleanup.css',
        ['staark-hub-admin'],
        STAARK_HUB_VERSION
    );

    wp_enqueue_script(
        'staark-hub-accessibility',
        $base_url . 'assets/accessibility.js',
        [],
        STAARK_HUB_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'staark_hub_enqueue_accessibility_assets', 100);
