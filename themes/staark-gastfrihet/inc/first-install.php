<?php
/**
 * Staark Hub First Install: restaurant and hotel starter pages.
 *
 * Requires Staark Hub with the `staark_hub_first_install_page_blueprints`
 * filter. On older Hub builds the default starter pages are used and the
 * front-page template still renders the full homepage for the active preset.
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Starter pages per mode: slug => title + sections.
 *
 * @return array<string,array{title:string,sections:list<string>}>
 */
function staark_gast_page_sections(string $mode = ''): array
{
    $mode = $mode !== '' ? $mode : staark_gast_mode();

    if ($mode === 'hotell') {
        return [
            'home' => ['title' => 'Hem', 'sections' => staark_gast_home_sections('hotell')],
            'rum' => ['title' => 'Rum', 'sections' => ['rooms', 'amenities', 'faq', 'cta']],
            'erbjudanden' => ['title' => 'Erbjudanden', 'sections' => ['offers', 'gallery', 'cta']],
            'om-oss' => ['title' => 'Om oss', 'sections' => ['about', 'gallery', 'reviews']],
            'boka' => ['title' => 'Boka rum', 'sections' => ['booking', 'faq']],
            'kontakt' => ['title' => 'Kontakt', 'sections' => ['contact', 'info']],
        ];
    }

    return [
        'home' => ['title' => 'Hem', 'sections' => staark_gast_home_sections('restaurang')],
        'meny' => ['title' => 'Meny', 'sections' => ['menu', 'lunch', 'cta']],
        'sallskap' => ['title' => 'Sällskap & event', 'sections' => ['events', 'faq', 'cta']],
        'om-oss' => ['title' => 'Om oss', 'sections' => ['about', 'gallery', 'reviews']],
        'boka' => ['title' => 'Boka bord', 'sections' => ['booking', 'faq']],
        'kontakt' => ['title' => 'Kontakt', 'sections' => ['contact', 'info']],
    ];
}

/**
 * Homepage section order, shared by the front-page template and First Install.
 *
 * @return list<string>
 */
function staark_gast_home_sections(string $mode = ''): array
{
    $mode = $mode !== '' ? $mode : staark_gast_mode();

    return $mode === 'hotell'
        ? ['hero', 'rooms', 'amenities', 'about', 'offers', 'reviews', 'info', 'cta']
        : ['hero', 'highlights', 'lunch', 'about', 'reviews', 'info', 'cta'];
}

/**
 * Sections that only exist for one mode; used to spot pages built for the
 * other preset.
 *
 * @return array<string,list<string>>
 */
function staark_gast_mode_only_sections(): array
{
    return [
        'restaurang' => ['highlights', 'menu', 'lunch', 'events'],
        'hotell' => ['rooms', 'amenities', 'offers'],
    ];
}

/**
 * @return array<string,array{title:string,content:string}>
 */
function staark_gast_page_blueprints(string $mode): array
{
    $pages = [];

    foreach (staark_gast_page_sections($mode) as $slug => $page) {
        $pages[$slug] = [
            'title' => $page['title'],
            'content' => staark_gast_pattern_blocks($page['sections']),
        ];
    }

    return $pages;
}

add_filter('staark_hub_first_install_page_blueprints', static function ($blueprints, $preset_id) {
    if (! in_array($preset_id, ['restaurang', 'hotell'], true) || ! is_array($blueprints)) {
        return $blueprints;
    }

    $privacy = $blueprints['integritetspolicy'] ?? null;
    $pages = staark_gast_page_blueprints((string) $preset_id);

    if (is_array($privacy)) {
        $pages['integritetspolicy'] = $privacy;
    }

    return $pages;
}, 10, 2);
