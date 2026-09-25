<?php
/**
 * Staark theme bootstrap.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('custom-logo', [
        'height' => 96,
        'width' => 320,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_editor_style('assets/css/theme.css');
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme = wp_get_theme();

    wp_enqueue_style(
        'staark-theme',
        get_theme_file_uri('assets/css/theme.css'),
        [],
        $theme->get('Version') ?: '0.1.0'
    );
});

// The homepage form lives in a block pattern, outside post_content.
add_filter('staark_hub_forms_should_enqueue_assets', static function (bool $enqueue): bool {
    return $enqueue || is_front_page();
});

// Older installed Hub builds only detect forms in post_content. Load their
// existing form CSS for the template pattern until the Hub update is installed.
add_action('wp_enqueue_scripts', static function (): void {
    if (! is_front_page() || ! shortcode_exists('staark_contact_form')
        || ! function_exists('staark_hub_runtime_url')) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-forms',
        staark_hub_runtime_url('assets/forms.css'),
        [],
        defined('STAARK_HUB_VERSION') ? STAARK_HUB_VERSION : wp_get_theme()->get('Version')
    );
}, 20);

add_action('init', static function (): void {
    $categories = [
        'staark' => __('Staark', 'staark'),
        'staark-heroes' => __('Staark — Heroes', 'staark'),
        'staark-sections' => __('Staark — Sections', 'staark'),
        'staark-social-proof' => __('Staark — Social proof', 'staark'),
        'staark-conversion' => __('Staark — Conversion', 'staark'),
    ];

    foreach ($categories as $slug => $label) {
        register_block_pattern_category(
            $slug,
            [
                'label' => $label,
            ]
        );
    }
});

/*
 * Frontend critical rendering path.
 */
$staark_performance_render = get_theme_file_path(
    'inc/performance-render.php'
);

if (is_file($staark_performance_render)) {
    require_once $staark_performance_render;
}

/*
 * Staark Theme System v2.
 */
$staark_theme_system = get_theme_file_path(
    'inc/theme-system.php'
);

if (is_file($staark_theme_system)) {
    require_once $staark_theme_system;
}
