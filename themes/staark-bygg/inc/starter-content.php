<?php
/**
 * Starter blueprint integration for S-Hub Bygg.
 *
 * Page lifecycle is managed centrally by Staark Core.
 *
 * @package StaarkBygg
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Bygg sections per starter page slug.
 *
 * @return array<string,list<string>>
 */
function staark_bygg_starter_page_map(): array
{
    $map = array_map(
        static fn (array $page): array => $page['sections'],
        staark_bygg_page_sections()
    );

    $map['kontakt'] = ['contact', 'area'];

    return $map;
}

/**
 * Expose every Bygg starter route to Staark Core.
 */
add_filter('staark_hub_starter_repair_blueprints', static function ($blueprints, $preset) {
    if ($preset !== 'bygg' || ! is_array($blueprints)) {
        return $blueprints;
    }

    foreach (staark_bygg_starter_page_map() as $slug => $sections) {
        if (! isset($blueprints[$slug])) {
            $blueprints[$slug] = [
                'title' => ucfirst(str_replace('-', ' ', $slug)),
                'content' => staark_bygg_pattern_blocks($sections),
            ];
        }
    }

    return $blueprints;
}, 10, 2);
