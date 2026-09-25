<?php
/**
 * S-Hub Light design layer (Light 2).
 *
 * When S-Hub Light itself is the active theme, the site uses the new sl-*
 * design system (assets/css/light.css) instead of the legacy theme.css /
 * critical.css pair. Child themes (S-Hub Salong, S-Hub Bygg) keep the legacy
 * stylesheet they were built on, and only get light.css on screens that
 * render S-Hub Light sections.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * True when S-Hub Light is the active theme (not a child theme).
 */
function staark_light_design_active(): bool
{
    return get_stylesheet() === get_template();
}

function staark_light_asset_version(string $relative): string
{
    $file = get_template_directory() . '/' . ltrim($relative, '/');
    $version = (string) (wp_get_theme(get_template())->get('Version') ?: '0.7.0');

    return is_file($file) ? $version . '.' . filemtime($file) : $version;
}

function staark_light_register_style(): void
{
    if (wp_style_is('staark-light', 'registered')) {
        return;
    }

    wp_register_style(
        'staark-light',
        get_template_directory_uri() . '/assets/css/light.css',
        [],
        staark_light_asset_version('assets/css/light.css')
    );
}

/*
 * Stylesheets.
 *
 * Active S-Hub Light: "staark-theme" becomes a source-less handle that only
 * carries the Theme System and pack inline CSS (tokens), and light.css is the
 * real stylesheet.
 */
add_action('wp_enqueue_scripts', static function (): void {
    staark_light_register_style();

    if (! staark_light_design_active()) {
        if (is_404()) {
            wp_enqueue_style('staark-light');
        }
        return;
    }

    wp_deregister_style('staark-theme');
    wp_register_style('staark-theme', false, [], staark_light_asset_version('style.css'));
    wp_enqueue_style('staark-theme');
    wp_enqueue_style('staark-light');
}, 5);

/*
 * Child themes: load light.css only when an S-Hub Light section is rendered.
 */
add_filter('render_block_core/group', static function (string $content, array $block): string {
    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

    if (! preg_match('/(^|\s)sl-section(\s|$)/', $class)) {
        return $content;
    }

    if (! staark_light_design_active()) {
        staark_light_register_style();
        wp_enqueue_style('staark-light');
    }

    // Block templates run do_shortcode() before patterns are expanded.
    return str_contains($content, '[') ? do_shortcode($content) : $content;
}, 10, 2);

add_action('after_setup_theme', static function (): void {
    if (staark_light_design_active()) {
        add_editor_style('assets/css/light.css');
    }
}, 20);

/*
 * Body font. Presets name "Inter"; ship it locally instead of relying on the
 * visitor having it installed.
 */
add_action('wp_head', static function (): void {
    if (! staark_light_design_active()) {
        return;
    }

    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url(get_template_directory_uri() . '/assets/fonts/inter-latin-wght-normal.woff2')
    );
}, 1);

/*
 * Accent: follow Staark Hub Branding only when a custom primary color was
 * saved; otherwise keep the preset's own primary (Hub's default is blue).
 */
function staark_light_accent_css(): string
{
    if (! function_exists('staark_theme_system_active_preset') || ! function_exists('staark_theme_system_color')) {
        return '';
    }

    $preset = staark_theme_system_active_preset();
    $accent = staark_theme_system_color($preset, 'primary', '#2457f5');
    $accent_dark = staark_theme_system_color($preset, 'primaryDark', '#173bb7');

    if (function_exists('staark_hub_branding') && function_exists('staark_hub_branding_defaults')) {
        $branding = staark_hub_branding();
        if (strtolower($branding['primary_color']) !== strtolower(staark_hub_branding_defaults()['primary_color'])) {
            $accent = $branding['primary_color'];
            $accent_dark = function_exists('staark_hub_darken_hex') ? staark_hub_darken_hex($accent) : $accent_dark;
        }
    }

    return sprintf(
        'html:root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;}',
        esc_html($accent),
        esc_html($accent_dark)
    );
}

add_action('wp_enqueue_scripts', static function (): void {
    if (staark_light_design_active()) {
        wp_add_inline_style('staark-light', staark_light_accent_css());
    }
}, 30);

add_action('enqueue_block_editor_assets', static function (): void {
    if (staark_light_design_active()) {
        wp_add_inline_style('wp-edit-blocks', staark_light_accent_css());
    }
}, 30);

add_filter('body_class', static function (array $classes): array {
    if (staark_light_design_active()) {
        $classes[] = 'staark-light';
    }

    return $classes;
});

/*
 * Pages built from Staark patterns render full width without a page title
 * (their first section has its own heading).
 */
add_filter('page_template_hierarchy', static function (array $templates): array {
    if (! staark_light_design_active()) {
        return $templates;
    }

    $post = get_queried_object();
    if (! $post instanceof WP_Post || get_page_template_slug($post) !== '') {
        return $templates;
    }

    if (str_contains((string) $post->post_content, '"slug":"staark/')) {
        array_unshift($templates, 'page-canvas');
    }

    return $templates;
});

/*
 * [staark_contact field="phone|email|address|city|name" link="1" label="" fallback=""]
 * Contact details from Staark Hub SEO settings / First Install.
 */
add_shortcode('staark_contact', static function ($atts = []): string {
    $atts = shortcode_atts(
        ['field' => 'phone', 'link' => '0', 'label' => '', 'fallback' => ''],
        is_array($atts) ? $atts : [],
        'staark_contact'
    );

    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $install = function_exists('staark_hub_first_install_state') ? staark_hub_first_install_state() : [];
    $pick = static function (string $key) use ($seo, $install): string {
        foreach ([$seo, $install] as $source) {
            if (isset($source[$key]) && is_scalar($source[$key]) && trim((string) $source[$key]) !== '') {
                return trim((string) $source[$key]);
            }
        }

        return '';
    };

    $field = sanitize_key((string) $atts['field']);
    switch ($field) {
        case 'email':
            $value = $pick('email');
            $href = is_email($value) ? 'mailto:' . $value : '';
            break;
        case 'address':
            $value = implode(', ', array_filter([$pick('street_address'), trim($pick('postal_code') . ' ' . $pick('locality'))]));
            $href = $value !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($value) : '';
            break;
        case 'city':
            $value = $pick('locality');
            $href = '';
            break;
        case 'name':
            $value = $pick('organization_name') !== '' ? $pick('organization_name') : (string) get_bloginfo('name');
            $href = '';
            break;
        case 'phone':
        default:
            $field = 'phone';
            $value = $pick('phone');
            $href = $value !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $value) : '';
            break;
    }

    if ($value === '') {
        $fallback = (string) $atts['fallback'];

        return $fallback !== '' ? '<span class="sl-contact sl-contact--fallback">' . esc_html($fallback) . '</span>' : '';
    }

    $text = (string) $atts['label'] !== '' ? (string) $atts['label'] : $value;
    $link = in_array(strtolower((string) $atts['link']), ['1', 'true', 'yes'], true) && $href !== '';

    if (! $link) {
        return '<span class="sl-contact sl-contact--' . esc_attr($field) . '">' . esc_html($text) . '</span>';
    }

    return sprintf(
        '<a class="sl-contact sl-contact--%1$s" href="%2$s"%3$s>%4$s</a>',
        esc_attr($field),
        esc_url($href, ['tel', 'mailto', 'https']),
        str_starts_with($href, 'https://') ? ' target="_blank" rel="noopener"' : '',
        esc_html($text)
    );
});
