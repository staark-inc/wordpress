<?php
/**
 * Local Business design pack runtime.
 *
 * Keeps the pack CSS inline so activating a preset does not reintroduce a
 * render-blocking stylesheet request after WP-6.2 performance work.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_local_business_pack_is_active(): bool
{
    return function_exists('staark_theme_system_active_preset_id')
        && staark_theme_system_active_preset_id() === 'local-business';
}

function staark_local_business_pack_css(): string
{
    $file = get_theme_file_path('assets/css/presets/local-business.css');

    if (! is_file($file) || ! is_readable($file)) {
        return '';
    }

    $css = file_get_contents($file);

    return is_string($css) ? trim($css) : '';
}

add_action('wp_enqueue_scripts', static function (): void {
    if (! staark_local_business_pack_is_active()) {
        return;
    }

    $css = staark_local_business_pack_css();

    if ($css !== '') {
        wp_add_inline_style('staark-theme', $css);
    }
}, 25);

add_action('enqueue_block_editor_assets', static function (): void {
    if (! staark_local_business_pack_is_active()) {
        return;
    }

    $css = staark_local_business_pack_css();

    if ($css !== '') {
        wp_add_inline_style('wp-edit-blocks', $css);
    }
}, 25);
