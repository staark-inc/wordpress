<?php
/**
 * One h1 per page.
 *
 * Pages built from Staark patterns are routed to the full-width canvas
 * template, which prints no page title. Their first section usually starts
 * with an h2 (the hero pattern is the only one with an h1), so such pages had
 * no h1 at all — bad for accessibility (WCAG 1.3.1 / 2.4.6) and SEO.
 *
 * When a page renders and no h1 has been output yet, the first section title
 * (sl-title, salong-title, bygg-title, gast-title) is promoted from h2 to h1.
 * Only the rendered HTML changes; the saved blocks stay untouched.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_heading_state(?bool $set = null): bool
{
    static $done = false;

    if ($set !== null) {
        $done = $set;
    }

    return $done;
}

/**
 * @param array<string,mixed> $block
 */
function staark_promote_first_section_title(string $content, array $block): string
{
    if (staark_heading_state() || ! is_singular('page') || is_admin()) {
        return $content;
    }

    if (preg_match('/^\s*<h1\b/i', $content)) {
        staark_heading_state(true);
        return $content;
    }

    $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
    if (! preg_match('/(^|\s)(sl|salong|bygg|gast)-title(\s|$)/', $class) || ! preg_match('/^\s*<h2\b/i', $content)) {
        return $content;
    }

    staark_heading_state(true);

    return (string) preg_replace(['/^(\s*)<h2\b/i', '/<\/h2>(\s*)$/i'], ['$1<h1', '</h1>$1'], $content);
}

add_filter('render_block_core/heading', 'staark_promote_first_section_title', 20, 2);

// A printed page title (page.html) is the page's h1.
add_filter('render_block_core/post-title', static function (string $content): string {
    if (is_singular('page') && preg_match('/<h1\b/i', $content)) {
        staark_heading_state(true);
    }

    return $content;
}, 20);
