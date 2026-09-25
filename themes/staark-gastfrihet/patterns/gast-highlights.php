<?php
/**
 * Title: Restaurang — Från köket
 * Slug: staark/gast-highlights
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, rätter, meny, favoriter
 * Viewport Width: 1400
 * Description: Three signature dishes with image, short description and price.
 */

$staark_gast_dishes = [
    ['assets/images/dish-1.webp', 'Varmrätt', 'Långkokt högrev', 'Rödvinssky, rostad jordärtskocka och syltad lök.', '295 kr'],
    ['assets/images/dish-2.webp', 'Fisk', 'Smörstekt torsk', 'Beurre blanc på lokal cider, dill och krispig potatis.', '285 kr'],
    ['assets/images/dish-3.webp', 'Dessert', 'Mörk choklad &amp; körsbär', 'Chokladmousse, syrlig körsbärskompott och vaniljgrädde.', '135 kr'],
];

$staark_gast_cards = '';
foreach ($staark_gast_dishes as $staark_gast_dish) {
    $staark_gast_cards .= staark_gast_group(
        staark_gast_image($staark_gast_dish[0], 'Illustration av ' . wp_strip_all_tags(html_entity_decode($staark_gast_dish[2])) . ' – ersätt med ett foto av rätten', 'gast-card-media')
        . staark_gast_group(
            staark_gast_p($staark_gast_dish[1], 'gast-card-tag')
            . staark_gast_h($staark_gast_dish[2], 3, 'gast-card-title')
            . staark_gast_p($staark_gast_dish[3], 'gast-card-text')
            . staark_gast_p($staark_gast_dish[4], 'gast-card-price'),
            'gast-card-body'
        ),
        'gast-card gast-card--media'
    );
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'highlights',
    staark_gast_head('Från köket', 'Säsongens favoriter.', 'Ett urval ur kvällens meny. Menyn följer säsongen, så rätterna kan skifta från vecka till vecka.')
    . staark_gast_grid($staark_gast_cards, 3, 'gast-card-grid')
    . staark_gast_buttons([['Se hela menyn', '/meny/', 'outline']], 'gast-actions gast-actions--center')
);
