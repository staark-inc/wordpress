<?php
/**
 * Starter blueprint integration for S-Hub Gästfrihet.
 *
 * Page lifecycle and Restaurang/Hotell switching are managed centrally
 * by Staark Core.
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Sections per starter page slug for the active mode.
 *
 * @return array<string,list<string>>
 */
function staark_gast_starter_page_map(): array
{
    return array_map(
        static fn (array $page): array => $page['sections'],
        staark_gast_page_sections()
    );
}

/**
 * Expose all Gästfrihet routes for the active preset to Staark Core.
 */
add_filter('staark_hub_starter_repair_blueprints', static function ($blueprints, $preset) {
    if (
        ! in_array($preset, ['restaurang', 'hotell'], true)
        || ! is_array($blueprints)
    ) {
        return $blueprints;
    }

    foreach (staark_gast_page_blueprints((string) $preset) as $slug => $page) {
        $blueprints[$slug] = $page;
    }

    return $blueprints;
}, 10, 2);
