<?php
/**
 * S-Hub Salong — child theme of S-Hub Light (staark).
 *
 * The parent theme keeps the Staark Hub integration, performance pipeline and
 * theme system. This child adds the salon design preset, salon patterns,
 * header/footer and booking-request enhancement.
 *
 * @package StaarkSalong
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_SALONG_PATTERN_PREFIX = 'staark/salong-';

function staark_salong_version(): string
{
    $version = wp_get_theme(get_stylesheet())->get('Version');

    return is_string($version) && $version !== '' ? $version : '0.1.0';
}

function staark_salong_asset_version(string $relative): string
{
    $file = get_stylesheet_directory() . '/' . ltrim($relative, '/');

    return is_file($file) ? staark_salong_version() . '.' . filemtime($file) : staark_salong_version();
}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('staark-salong', get_stylesheet_directory() . '/languages');
    add_editor_style('assets/css/salong.css');
}, 20);

/*
 * Styles. The parent loads its own theme.css; salong.css is layered on top and
 * is intentionally render-blocking because it styles the first viewport.
 */
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'staark-salong',
        get_stylesheet_directory_uri() . '/assets/css/salong.css',
        ['staark-theme'],
        staark_salong_asset_version('assets/css/salong.css')
    );

    wp_enqueue_script(
        'staark-salong',
        get_stylesheet_directory_uri() . '/assets/js/salong.js',
        [],
        staark_salong_asset_version('assets/js/salong.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );

    wp_register_script(
        'staark-salong-booking',
        get_stylesheet_directory_uri() . '/assets/js/salong-booking.js',
        [],
        staark_salong_asset_version('assets/js/salong-booking.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );
}, 30);

/*
 * Preload the two fonts used above the fold.
 */
add_action('wp_head', static function (): void {
    $base = get_stylesheet_directory_uri() . '/assets/fonts/';

    foreach (['fraunces-latin-wght-normal.woff2', 'inter-latin-wght-normal.woff2'] as $font) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url($base . $font)
        );
    }
}, 1);

/*
 * Accent color. Staark Hub Branding always prints its primary color (with a
 * blue default), which would hide the preset's clay accent. Follow Branding
 * only when a custom color has actually been saved.
 */
function staark_salong_accent_css(): string
{
    if (! function_exists('staark_theme_system_active_preset') || ! function_exists('staark_theme_system_color')) {
        return '';
    }

    $preset = staark_theme_system_active_preset();
    $accent = staark_theme_system_color($preset, 'primary', '#9a4733');
    $accent_dark = staark_theme_system_color($preset, 'primaryDark', '#7a3726');

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
        'html:root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;--salong-accent:%1$s;--salong-accent-dark:%2$s;}',
        esc_html($accent),
        esc_html($accent_dark)
    );
}

add_action('wp_enqueue_scripts', static function (): void {
    $css = staark_salong_accent_css();

    if ($css !== '') {
        wp_add_inline_style('staark-salong', $css);
    }
}, 31);

add_action('enqueue_block_editor_assets', static function (): void {
    $css = staark_salong_accent_css();

    if ($css !== '') {
        wp_add_inline_style('wp-edit-blocks', $css);
    }
}, 30);

/*
 * Shortcodes inside salon sections. Block templates run do_shortcode() before
 * their pattern blocks are expanded, so the booking form and contact details
 * would otherwise stay as raw [shortcode] text on the front-page template.
 * Every salon pattern is wrapped in a `.salong-section` group, so resolve
 * shortcodes once at that level.
 */
add_filter('render_block_core/group', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if (! preg_match('/(^|\s)salong-(section|hero)(\s|$)/', $class) || ! str_contains($content, '[')) {
        return $content;
    }

    return do_shortcode($content);
}, 10, 2);

/*
 * Enqueue the booking enhancer only when a booking section is rendered.
 */
add_filter('render_block', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if ($class !== '' && str_contains($class, 'salong-booking')) {
        wp_enqueue_script('staark-salong-booking');
    }

    return $content;
}, 10, 2);

/*
 * Staark Hub Forms CSS. Older Hub builds only detect the form shortcode in
 * post_content, so load it for any page that renders salon patterns.
 */
function staark_salong_page_uses_patterns(?WP_Post $post = null): bool
{
    $post = $post ?? get_post();

    return $post instanceof WP_Post
        && str_contains((string) $post->post_content, '"slug":"' . STAARK_SALONG_PATTERN_PREFIX);
}

add_filter('staark_hub_forms_should_enqueue_assets', static function (bool $enqueue): bool {
    return $enqueue || is_front_page() || (is_page() && staark_salong_page_uses_patterns());
});

add_action('wp_enqueue_scripts', static function (): void {
    if (! shortcode_exists('staark_contact_form') || ! function_exists('staark_hub_runtime_url')) {
        return;
    }

    if (! is_front_page() && ! (is_page() && staark_salong_page_uses_patterns())) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-forms',
        staark_hub_runtime_url('assets/forms.css'),
        [],
        defined('STAARK_HUB_VERSION') ? STAARK_HUB_VERSION : staark_salong_version()
    );
}, 25);

/*
 * Pattern category.
 */
add_action('init', static function (): void {
    register_block_pattern_category('staark-salong', [
        'label' => __('Staark — Salong', 'staark-salong'),
    ]);
});

/*
 * Template routing.
 *
 * - A static front page (for example the one Staark First Install creates)
 *   renders its own content through the page-canvas template instead of the
 *   fixed front-page template.
 * - Pages built from salon patterns get the full-width canvas automatically,
 *   so section headings are not duplicated by a page title.
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

    if (staark_salong_page_uses_patterns($post)) {
        array_unshift($templates, 'page-canvas');
    }

    return $templates;
});

$staark_salong_includes = [
    'inc/shortcodes.php',
    'inc/first-install.php',
    'inc/starter-content.php',
];

foreach ($staark_salong_includes as $staark_salong_include) {
    $staark_salong_file = get_stylesheet_directory() . '/' . $staark_salong_include;

    if (is_file($staark_salong_file)) {
        require_once $staark_salong_file;
    }
}
