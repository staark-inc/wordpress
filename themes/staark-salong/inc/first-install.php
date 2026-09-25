<?php
/**
 * Staark Hub First Install: salon starter pages.
 *
 * Requires Staark Hub with the `staark_hub_first_install_page_blueprints`
 * filter. On older Hub builds the default starter pages are used and the
 * front-page template still renders the full salon homepage.
 *
 * @package StaarkSalong
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param list<string> $patterns Pattern names without the staark/salong- prefix.
 */
function staark_salong_pattern_blocks(array $patterns): string
{
    $blocks = [];

    foreach ($patterns as $pattern) {
        $blocks[] = '<!-- wp:pattern {"slug":"' . STAARK_SALONG_PATTERN_PREFIX . sanitize_key($pattern) . '"} /-->';
    }

    return implode("\n", $blocks);
}

/**
 * Homepage section order, shared by templates/front-page.html and First Install.
 *
 * @return list<string>
 */
function staark_salong_home_sections(): array
{
    return ['hero', 'services', 'gallery', 'about', 'reviews'];
}

add_filter('staark_hub_first_install_page_blueprints', static function ($blueprints, $preset_id) {
    if ($preset_id !== 'salong' || ! is_array($blueprints)) {
        return $blueprints;
    }

    $privacy = $blueprints['integritetspolicy'] ?? null;

    $pages = [
        'home' => [
            'title' => 'Hem',
            'content' => staark_salong_pattern_blocks(staark_salong_home_sections()),
        ],
        'tjanster' => [
            'title' => 'Tjänster',
            'content' => staark_salong_pattern_blocks(['services']),
        ],
        'priser' => [
            'title' => 'Priser',
            'content' => staark_salong_pattern_blocks(['prices']),
        ],
        'galleri' => [
            'title' => 'Galleri',
            'content' => staark_salong_pattern_blocks(['gallery', 'reviews']),
        ],
        'om-oss' => [
            'title' => 'Om oss',
            'content' => staark_salong_pattern_blocks(['about', 'reviews']),
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'content' => staark_salong_pattern_blocks(['contact']),
        ],
        'boka' => [
            'title' => 'Boka tid',
            'content' => staark_salong_pattern_blocks(['booking']),
        ],
    ];

    if (is_array($privacy)) {
        $pages['integritetspolicy'] = $privacy;
    }

    return $pages;
}, 10, 2);
