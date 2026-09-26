<?php
/**
 * Width/height for theme images in patterns.
 *
 * Pattern images point at files inside the theme (assets/images/...) and have
 * no width/height attributes. Without them WordPress core cannot add
 * loading="lazy", fetchpriority="high" or decoding, and the browser cannot
 * reserve space (layout shift). This adds the intrinsic size of theme files
 * at render time; WordPress then applies its normal loading optimizations.
 * Only the rendered HTML changes; saved blocks stay untouched.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Local path for a URL inside the themes directory, or '' for other URLs.
 */
function staark_theme_image_path(string $url): string
{
    $url = (string) strtok(html_entity_decode($url), '?#');
    $roots = [
        untrailingslashit(set_url_scheme(get_theme_root_uri(), 'https')) => get_theme_root(),
        untrailingslashit(set_url_scheme(get_theme_root_uri(), 'http')) => get_theme_root(),
    ];

    foreach ($roots as $root_url => $root_path) {
        if (str_starts_with($url, $root_url . '/')) {
            $relative = substr($url, strlen($root_url));
            if (str_contains($relative, '..')) {
                return '';
            }

            $path = $root_path . $relative;

            return is_file($path) ? $path : '';
        }
    }

    return '';
}

/**
 * @return array{0:int,1:int}|null
 */
function staark_theme_image_size(string $path): ?array
{
    static $cache = [];

    if (! array_key_exists($path, $cache)) {
        $size = function_exists('wp_getimagesize') ? wp_getimagesize($path) : @getimagesize($path);
        $cache[$path] = is_array($size) && $size[0] > 0 && $size[1] > 0 ? [(int) $size[0], (int) $size[1]] : null;
    }

    return $cache[$path];
}

add_filter('render_block_core/image', static function (string $content): string {
    if (is_admin() || ! str_contains($content, '<img')) {
        return $content;
    }

    return (string) preg_replace_callback(
        '/<img\b[^>]*>/i',
        static function (array $match): string {
            $tag = $match[0];
            if (preg_match('/\swidth=/i', $tag) || ! preg_match('/\ssrc="([^"]+)"/i', $tag, $src)) {
                return $tag;
            }

            $path = staark_theme_image_path($src[1]);
            $size = $path !== '' ? staark_theme_image_size($path) : null;
            if ($size === null) {
                return $tag;
            }

            return (string) preg_replace('/^<img\b/i', sprintf('<img width="%d" height="%d"', $size[0], $size[1]), $tag, 1);
        },
        $content
    );
}, 5);
