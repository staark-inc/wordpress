<?php
/**
 * Root-relative links on subdirectory installs.
 *
 * Patterns, headers and footers link to pages with root-relative URLs such as
 * "/kontakt/" or "/boka/". On a site installed in a subdirectory
 * (example.com/site/) those would point outside the site. When the home URL
 * has a path, this prefixes it to root-relative hrefs in rendered blocks.
 * Sites installed at the domain root are not touched.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_home_path_prefix(): string
{
    static $prefix = null;

    if ($prefix === null) {
        $path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $prefix = untrailingslashit($path);
    }

    return $prefix;
}

add_filter('render_block', static function ($content) {
    $prefix = staark_home_path_prefix();
    if (! is_string($content) || $prefix === '' || ! str_contains($content, 'href="/')) {
        return $content;
    }

    return (string) preg_replace_callback(
        '/href="(\/(?!\/)[^"]*)"/',
        static function (array $match) use ($prefix): string {
            $url = $match[1];
            if ($url === $prefix || str_starts_with($url, $prefix . '/')) {
                return $match[0];
            }

            return 'href="' . esc_attr($prefix . $url) . '"';
        },
        $content
    );
}, 20);
