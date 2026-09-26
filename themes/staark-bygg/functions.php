<?php
/**
 * S-Hub Bygg — child theme of S-Hub Light (staark) for builders and craftsmen
 * (bygg / hantverkare).
 *
 * The parent theme keeps the Staark Hub integration, performance pipeline and
 * theme system. This child adds the "bygg" design preset (built on the Local
 * Business direction), construction patterns, header/footer and the quote
 * request enhancement.
 *
 * @package StaarkBygg
 */

if (! defined('ABSPATH')) {
    exit;
}

/*
 * Use the small parent base.css instead of the legacy parent theme.css.
 * Declared before the parent's after_setup_theme callback (priority 10),
 * which picks the editor stylesheet from it.
 */
add_action('after_setup_theme', static function (): void {
    add_theme_support('staark-lean-parent');
}, 5);

const STAARK_BYGG_PATTERN_PREFIX = 'staark/bygg-';

function staark_bygg_version(): string
{
    $version = wp_get_theme(get_stylesheet())->get('Version');

    return is_string($version) && $version !== '' ? $version : '0.1.0';
}

function staark_bygg_asset_version(string $relative): string
{
    $file = get_stylesheet_directory() . '/' . ltrim($relative, '/');

    return is_file($file) ? staark_bygg_version() . '.' . filemtime($file) : staark_bygg_version();
}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('staark-bygg', get_stylesheet_directory() . '/languages');
    add_editor_style('assets/css/bygg.css');
}, 20);

/*
 * Styles. The parent loads its own theme.css; bygg.css is layered on top and
 * is intentionally render-blocking because it styles the first viewport.
 */
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'staark-bygg',
        get_stylesheet_directory_uri() . '/assets/css/bygg.css',
        ['staark-theme'],
        staark_bygg_asset_version('assets/css/bygg.css')
    );

    wp_enqueue_script(
        'staark-bygg',
        get_stylesheet_directory_uri() . '/assets/js/bygg.js',
        [],
        staark_bygg_asset_version('assets/js/bygg.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );

    wp_register_script(
        'staark-bygg-quote',
        get_stylesheet_directory_uri() . '/assets/js/bygg-quote.js',
        [],
        staark_bygg_asset_version('assets/js/bygg-quote.js'),
        [
            'in_footer' => true,
            'strategy' => 'defer',
        ]
    );
}, 30);

/*
 * Preload the body font used above the fold.
 */
add_action('wp_head', static function (): void {
    $base = get_stylesheet_directory_uri() . '/assets/fonts/';

    foreach (['inter-latin-wght-normal.woff2'] as $font) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url($base . $font)
        );
    }
}, 1);

/*
 * Accent color. Staark Hub Branding always prints its primary color (with a
 * blue default), which would hide the preset's brick accent. Follow Branding
 * only when a custom color has actually been saved.
 */
function staark_bygg_accent_css(): string
{
    if (! function_exists('staark_theme_system_active_preset') || ! function_exists('staark_theme_system_color')) {
        return '';
    }

    $preset = staark_theme_system_active_preset();
    $accent = staark_theme_system_color($preset, 'primary', '#b64728');
    $accent_dark = staark_theme_system_color($preset, 'primaryDark', '#92361f');

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
        'html:root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;--bygg-accent:%1$s;--bygg-accent-dark:%2$s;}',
        esc_html($accent),
        esc_html($accent_dark)
    );
}

add_action('wp_enqueue_scripts', static function (): void {
    $css = staark_bygg_accent_css();

    if ($css !== '') {
        wp_add_inline_style('staark-bygg', $css);
    }
}, 31);

add_action('enqueue_block_editor_assets', static function (): void {
    $css = staark_bygg_accent_css();

    if ($css !== '') {
        wp_add_inline_style('wp-edit-blocks', $css);
    }
}, 30);

/*
 * Shortcodes inside Bygg sections. Block templates run do_shortcode() before
 * their pattern blocks are expanded, so the quote form and contact details
 * would otherwise stay as raw [shortcode] text on the front-page template.
 * Every Bygg pattern is wrapped in a `.bygg-section` group, so resolve
 * shortcodes once at that level.
 */
add_filter('render_block_core/group', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if (! preg_match('/(^|\s)bygg-(section|hero)(\s|$)/', $class) || ! str_contains($content, '[')) {
        return $content;
    }

    // Inside the_content, WordPress runs do_shortcode() itself after the
    // blocks (priority 11); running it here too would execute shortcodes twice
    // and turn escaped [[shortcode]] into a live one.
    if (doing_filter('the_content')) {
        return $content;
    }

    return do_shortcode($content);
}, 10, 2);

/*
 * Enqueue the quote enhancer only when a quote section is rendered.
 */
add_filter('render_block', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if ($class !== '' && str_contains($class, 'bygg-quote')) {
        wp_enqueue_script('staark-bygg-quote');
    }

    return $content;
}, 10, 2);

/*
 * Staark Hub Forms CSS. Older Hub builds only detect the form shortcode in
 * post_content, so load it for any page that renders Bygg patterns.
 */
function staark_bygg_page_uses_patterns(?WP_Post $post = null): bool
{
    $post = $post ?? get_post();

    return $post instanceof WP_Post
        && str_contains((string) $post->post_content, '"slug":"' . STAARK_BYGG_PATTERN_PREFIX);
}

add_filter('staark_hub_forms_should_enqueue_assets', static function (bool $enqueue): bool {
    return $enqueue || is_front_page() || (is_page() && staark_bygg_page_uses_patterns());
});

add_action('wp_enqueue_scripts', static function (): void {
    if (! shortcode_exists('staark_contact_form') || ! function_exists('staark_hub_runtime_url')) {
        return;
    }

    if (! is_front_page() && ! (is_page() && staark_bygg_page_uses_patterns())) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-forms',
        staark_hub_runtime_url('assets/forms.css'),
        [],
        defined('STAARK_HUB_VERSION') ? STAARK_HUB_VERSION : staark_bygg_version()
    );
}, 25);

/*
 * Pattern category.
 */
add_action('init', static function (): void {
    register_block_pattern_category('staark-bygg', [
        'label' => __('Staark — Bygg', 'staark-bygg'),
    ]);
});

/*
 * Template routing.
 *
 * - A static front page (for example the one Staark First Install creates)
 *   renders its own content through the page-canvas template instead of the
 *   fixed front-page template.
 * - Pages built from Bygg patterns get the full-width canvas automatically,
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

    if (staark_bygg_page_uses_patterns($post)) {
        array_unshift($templates, 'page-canvas');
    }

    return $templates;
});

$staark_bygg_includes = [
    'inc/shortcodes.php',
    'inc/first-install.php',
    'inc/starter-content.php',
];

foreach ($staark_bygg_includes as $staark_bygg_include) {
    $staark_bygg_file = get_stylesheet_directory() . '/' . $staark_bygg_include;

    if (is_file($staark_bygg_file)) {
        require_once $staark_bygg_file;
    }
}
