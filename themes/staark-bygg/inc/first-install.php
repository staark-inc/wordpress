<?php
/**
 * Staark Hub First Install: builder / craftsman starter pages.
 *
 * Requires Staark Hub with the `staark_hub_first_install_page_blueprints`
 * filter. On older Hub builds the default starter pages are used and the
 * front-page template still renders the full Bygg homepage.
 *
 * @package StaarkBygg
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param list<string> $patterns Pattern names without the staark/bygg- prefix.
 */
function staark_bygg_pattern_blocks(array $patterns): string
{
    $blocks = [];

    foreach ($patterns as $pattern) {
        $blocks[] = '<!-- wp:pattern {"slug":"' . STAARK_BYGG_PATTERN_PREFIX . sanitize_key($pattern) . '"} /-->';
    }

    return implode("\n", $blocks);
}

/**
 * Homepage section order, shared by templates/front-page.html and First Install.
 *
 * @return list<string>
 */
function staark_bygg_home_sections(): array
{
    return ['hero', 'services', 'projects', 'process', 'reviews', 'cta'];
}

/**
 * Starter pages per slug (First Install and the starter-page switch).
 *
 * @return array<string,array{title:string,sections:list<string>}>
 */
function staark_bygg_page_sections(): array
{
    return [
        'home' => ['title' => 'Hem', 'sections' => staark_bygg_home_sections()],
        'tjanster' => ['title' => 'Tjänster', 'sections' => ['services', 'process', 'faq', 'cta']],
        'projekt' => ['title' => 'Projekt', 'sections' => ['projects', 'reviews', 'cta']],
        'omrade' => ['title' => 'Arbetsområde', 'sections' => ['area', 'cta']],
        'om-oss' => ['title' => 'Om oss', 'sections' => ['about', 'process', 'reviews', 'cta']],
        'offert' => ['title' => 'Begär offert', 'sections' => ['quote', 'faq']],
    ];
}

add_filter('staark_hub_first_install_page_blueprints', static function ($blueprints, $preset_id) {
    if ($preset_id !== 'bygg' || ! is_array($blueprints)) {
        return $blueprints;
    }

    $privacy = $blueprints['integritetspolicy'] ?? null;
    $pages = [];

    foreach (staark_bygg_page_sections() as $slug => $page) {
        $pages[$slug] = [
            'title' => $page['title'],
            'content' => staark_bygg_pattern_blocks($page['sections']),
        ];
    }

    if (is_array($privacy)) {
        $pages['integritetspolicy'] = $privacy;
    }

    return $pages;
}, 10, 2);
