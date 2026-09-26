<?php
/**
 * S-Hub Gästfrihet — child theme of S-Hub Light (staark) for restaurants and
 * hotels.
 *
 * One theme, two design presets chosen in Staark First Install:
 * - "restaurang": menu, lunch, private dining and table booking;
 * - "hotell": rooms, amenities, offers and room booking.
 *
 * Sections are shared components; copy, pages, header and footer follow the
 * active preset. Bookings go through an external booking link (Appearance →
 * Bokning) and/or a booking request sent through Staark Hub Forms.
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_GAST_PATTERN_PREFIX = 'staark/gast-';

/*
 * Restaurang and Hotell both provide booking requests.
 */
add_filter('staark_hub_booking_enabled', '__return_true');

function staark_gast_version(): string
{
    $version = wp_get_theme(get_stylesheet())->get('Version');

    return is_string($version) && $version !== '' ? $version : '0.1.0';
}

function staark_gast_asset_version(string $relative): string
{
    $file = get_stylesheet_directory() . '/' . ltrim($relative, '/');

    return is_file($file) ? staark_gast_version() . '.' . filemtime($file) : staark_gast_version();
}

/**
 * Active mode: "hotell" when the Hotell preset is active, otherwise
 * "restaurang".
 */
function staark_gast_mode(): string
{
    $preset = function_exists('staark_theme_system_active_preset_id') ? staark_theme_system_active_preset_id() : '';

    return $preset === 'hotell' ? 'hotell' : 'restaurang';
}

function staark_gast_is_hotel(): bool
{
    return staark_gast_mode() === 'hotell';
}

/**
 * Pick the copy for the active mode.
 *
 * @template T
 * @param T $restaurant
 * @param T $hotel
 * @return T
 */
function staark_gast_pick($restaurant, $hotel)
{
    return staark_gast_is_hotel() ? $hotel : $restaurant;
}

/*
 * A fresh activation starts on the Restaurang preset (the Theme System would
 * otherwise fall back to the first preset alphabetically).
 */
add_action('after_switch_theme', static function (): void {
    $saved = (string) get_theme_mod('staark_theme_preset', '');

    if (! in_array($saved, ['restaurang', 'hotell'], true)) {
        set_theme_mod('staark_theme_preset', 'restaurang');
    }
});

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('staark-gastfrihet', get_stylesheet_directory() . '/languages');
    add_editor_style('assets/css/gast.css');
}, 20);

/*
 * Styles and scripts.
 */
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'staark-gast',
        get_stylesheet_directory_uri() . '/assets/css/gast.css',
        ['staark-theme'],
        staark_gast_asset_version('assets/css/gast.css')
    );

    wp_enqueue_script(
        'staark-gast',
        get_stylesheet_directory_uri() . '/assets/js/gast.js',
        [],
        staark_gast_asset_version('assets/js/gast.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );

    wp_register_script(
        'staark-gast-booking',
        get_stylesheet_directory_uri() . '/assets/js/gast-booking.js',
        [],
        staark_gast_asset_version('assets/js/gast-booking.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );
}, 30);

add_action('wp_head', static function (): void {
    $base = get_stylesheet_directory_uri() . '/assets/fonts/';

    foreach (['inter-latin-wght-normal.woff2', 'fraunces-latin-wght-normal.woff2'] as $font) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url($base . $font)
        );
    }
}, 1);

/*
 * Accent color: follow Staark Hub Branding only when a custom color has been
 * saved (the Hub prints a blue default that would hide the preset accent).
 */
function staark_gast_accent_css(): string
{
    if (! function_exists('staark_theme_system_active_preset') || ! function_exists('staark_theme_system_color')) {
        return '';
    }

    $preset = staark_theme_system_active_preset();
    $accent = staark_theme_system_color($preset, 'primary', '#a8452b');
    $accent_dark = staark_theme_system_color($preset, 'primaryDark', '#7e311d');

    if (function_exists('staark_hub_branding') && function_exists('staark_hub_branding_defaults')) {
        $branding = staark_hub_branding();
        $default = staark_hub_branding_defaults()['primary_color'];

        if (strtolower($branding['primary_color']) !== strtolower($default)) {
            $accent = $branding['primary_color'];
            $accent_dark = function_exists('staark_hub_darken_hex')
                ? staark_hub_darken_hex($accent)
                : $accent_dark;
        }
    }

    return sprintf(
        'html:root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;--gast-accent:%1$s;--gast-accent-dark:%2$s;}',
        esc_html($accent),
        esc_html($accent_dark)
    );
}

add_action('wp_enqueue_scripts', static function (): void {
    $css = staark_gast_accent_css();

    if ($css !== '') {
        wp_add_inline_style('staark-gast', $css);
    }
}, 31);

add_action('enqueue_block_editor_assets', static function (): void {
    $css = staark_gast_accent_css();

    if ($css !== '') {
        wp_add_inline_style('wp-edit-blocks', $css);
    }
}, 30);

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'gast-mode-' . staark_gast_mode();

    if (function_exists('staark_gast_booking_settings') && staark_gast_booking_settings()['mode'] === 'external') {
        $classes[] = 'gast-booking-external-only';
    }

    return $classes;
});

add_filter('admin_body_class', static function (string $classes): string {
    return $classes . ' gast-mode-' . staark_gast_mode();
});

/*
 * Header and footer follow the preset: the Hotell preset renders the
 * "header-hotell" / "footer-hotell" template parts.
 */
add_filter('render_block_data', static function (array $block): array {
    if (($block['blockName'] ?? '') !== 'core/template-part' || ! staark_gast_is_hotel()) {
        return $block;
    }

    $slug = isset($block['attrs']['slug']) ? (string) $block['attrs']['slug'] : '';

    if ($slug === 'header' || $slug === 'footer') {
        $block['attrs']['slug'] = $slug . '-hotell';
    }

    return $block;
});

/*
 * Shortcodes inside sections. Block templates run do_shortcode() before their
 * pattern blocks are expanded, so resolve shortcodes at section level.
 */
add_filter('render_block_core/group', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if (! preg_match('/(^|\s)gast-(section|hero)(\s|$)/', $class) || ! str_contains($content, '[')) {
        return $content;
    }

    return do_shortcode($content);
}, 10, 2);

/*
 * Booking enhancer only where a booking section is rendered.
 */
add_filter('render_block', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if ($class !== '' && preg_match('/(^|\s)gast-booking(\s|$)/', $class)) {
        wp_enqueue_script('staark-gast-booking');
    }

    return $content;
}, 10, 2);

/*
 * Staark Hub Forms CSS for pages built from these patterns.
 */
function staark_gast_page_uses_patterns(?WP_Post $post = null): bool
{
    $post = $post ?? get_post();

    return $post instanceof WP_Post
        && str_contains((string) $post->post_content, '"slug":"' . STAARK_GAST_PATTERN_PREFIX);
}

add_filter('staark_hub_forms_should_enqueue_assets', static function (bool $enqueue): bool {
    return $enqueue || is_front_page() || (is_page() && staark_gast_page_uses_patterns());
});

add_action('wp_enqueue_scripts', static function (): void {
    if (! shortcode_exists('staark_contact_form') || ! function_exists('staark_hub_runtime_url')) {
        return;
    }

    if (! is_front_page() && ! (is_page() && staark_gast_page_uses_patterns())) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-forms',
        staark_hub_runtime_url('assets/forms.css'),
        [],
        defined('STAARK_HUB_VERSION') ? STAARK_HUB_VERSION : staark_gast_version()
    );
}, 25);

add_action('init', static function (): void {
    register_block_pattern_category('staark-gastfrihet', [
        'label' => __('Staark — Restaurang & Hotell', 'staark-gastfrihet'),
    ]);
});

/*
 * Template routing: a static front page renders its own content full width;
 * pages built from these patterns get the canvas template (no page title).
 */
add_filter('frontpage_template_hierarchy', static function (array $templates): array {
    if (get_option('show_on_front') === 'page' && (int) get_option('page_on_front') > 0) {
        return ['page-canvas'];
    }

    return $templates;
});

add_filter('page_template_hierarchy', static function (array $templates): array {
    $post = get_queried_object();

    if (! $post instanceof WP_Post || get_page_template_slug($post) !== '') {
        return $templates;
    }

    if (staark_gast_page_uses_patterns($post)) {
        array_unshift($templates, 'page-canvas');
    }

    return $templates;
});

$staark_gast_includes = [
    'inc/blocks.php',
    'inc/shortcodes.php',
    'inc/booking.php',
    'inc/first-install.php',
    'inc/starter-content.php',
];

foreach ($staark_gast_includes as $staark_gast_include) {
    $staark_gast_file = get_stylesheet_directory() . '/' . $staark_gast_include;

    if (is_file($staark_gast_file)) {
        require_once $staark_gast_file;
    }
}
