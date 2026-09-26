<?php
/**
 * Starter blueprint integration for S-Hub Salong.
 *
 * Page lifecycle is managed centrally by Staark Core.
 *
 * @package StaarkSalong
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Salon sections per starter page slug.
 *
 * @return array<string,list<string>>
 */
function staark_salong_starter_page_map(): array
{
    return [
        'home' => staark_salong_home_sections(),
        'tjanster' => ['services'],
        'priser' => ['prices'],
        'galleri' => ['gallery', 'reviews'],
        'om-oss' => ['about', 'reviews'],
        'kontakt' => ['contact'],
        'boka' => ['booking'],
    ];
}

/**
 * Expose every Salong starter route to Staark Core.
 */
add_filter('staark_hub_starter_repair_blueprints', static function ($blueprints, $preset) {
    if ($preset !== 'salong' || ! is_array($blueprints)) {
        return $blueprints;
    }

    foreach (staark_salong_starter_page_map() as $slug => $sections) {
        if (! isset($blueprints[$slug])) {
            $blueprints[$slug] = [
                'title' => ucfirst(str_replace('-', ' ', $slug)),
                'content' => staark_salong_pattern_blocks($sections),
            ];
        }
    }

    return $blueprints;
}, 10, 2);
