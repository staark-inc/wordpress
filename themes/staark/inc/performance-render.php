<?php
/**
 * Frontend render-path optimizations for the Staark theme.
 *
 * - Inline small critical CSS.
 * - Inline WordPress Navigation block CSS.
 * - Load the full theme stylesheet without blocking first render.
 *
 * Logged-in users keep the normal stylesheet path.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_theme_render_optimizations_enabled(): bool
{
    if (is_admin()) {
        return false;
    }

    if (is_user_logged_in()) {
        return false;
    }

    if (wp_doing_ajax()) {
        return false;
    }

    return true;
}

function staark_theme_navigation_css_file(): string
{
    return ABSPATH
        . WPINC
        . '/blocks/navigation/style.css';
}

function staark_theme_navigation_css_available(): bool
{
    $file = staark_theme_navigation_css_file();

    return is_file($file)
        && is_readable($file)
        && filesize($file) > 0;
}

/**
 * Critical theme CSS + WordPress navigation CSS.
 */
add_action(
    'wp_head',
    static function (): void {
        if (! staark_theme_render_optimizations_enabled()) {
            return;
        }

        $critical_file = get_theme_file_path(
            'assets/css/critical.css'
        );

        if (
            is_file($critical_file)
            && is_readable($critical_file)
        ) {
            $critical_css = file_get_contents(
                $critical_file
            );

            if (
                is_string($critical_css)
                && trim($critical_css) !== ''
            ) {
                echo "\n<style id=\"staark-critical-css\">\n";
                echo $critical_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo "\n</style>\n";
            }
        }

        /*
         * Lighthouse reports this small WordPress core stylesheet
         * as render-blocking. Inline it instead of creating another
         * critical-path request.
         */
        if (staark_theme_navigation_css_available()) {
            $navigation_css = file_get_contents(
                staark_theme_navigation_css_file()
            );

            if (
                is_string($navigation_css)
                && trim($navigation_css) !== ''
            ) {
                echo "\n<style id=\"staark-navigation-inline-css\">\n";
                echo $navigation_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo "\n</style>\n";
            }
        }
    },
    2
);

/**
 * Make the large theme stylesheet non-render-blocking and suppress
 * the external Navigation stylesheet after its contents were inlined.
 */
add_filter(
    'style_loader_tag',
    static function (
        string $html,
        string $handle,
        string $href,
        string $media
    ): string {
        if (! staark_theme_render_optimizations_enabled()) {
            return $html;
        }

        /*
         * Navigation CSS has already been inlined above.
         * Match by handle and URL to remain compatible with core
         * handle changes.
         */
        $is_navigation_style =
            $handle === 'wp-block-navigation'
            || str_contains(
                $href,
                '/wp-includes/blocks/navigation/style'
            );

        if (
            $is_navigation_style
            && staark_theme_navigation_css_available()
        ) {
            return '';
        }

        if ($handle !== 'staark-theme') {
            return $html;
        }

        $href = esc_url($href);

        if ($href === '') {
            return $html;
        }

        $media = $media !== ''
            ? esc_attr($media)
            : 'all';

        return sprintf(
            '<link rel="preload" id="staark-theme-preload" href="%1$s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" media="%2$s" />'
            . '<noscript><link rel="stylesheet" id="staark-theme-css-noscript" href="%1$s" media="%2$s" /></noscript>'
            . "\n",
            $href,
            $media
        );
    },
    20,
    4
);
