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
