<?php
/**
 * Title: Restaurang — Veckans lunch
 * Slug: staark/gast-lunch
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, lunch, dagens, veckans lunch
 * Viewport Width: 1400
 * Description: Weekly lunch menu (Monday–Friday) with price and what is included. Update the dishes every week.
 */

$staark_gast_days = [
    ['Måndag', 'Pannbiff med lök, gräddsås, potatis och lingon'],
    ['Tisdag', 'Ugnsbakad lax, dillstuvad potatis och citron'],
    ['Onsdag', 'Kycklinggryta med citrongräs, jasminris och koriander'],
    ['Torsdag', 'Ärtsoppa med fläsk – och pannkakor med sylt'],
    ['Fredag', 'Fish &amp; chips med remouladsås och ärtpuré'],
];

$staark_gast_rows = '';
foreach ($staark_gast_days as $staark_gast_day) {
    $staark_gast_rows .= staark_gast_group(
        staark_gast_p(esc_html($staark_gast_day[0]), 'gast-lunch-day')
        . staark_gast_p($staark_gast_day[1], 'gast-lunch-dish'),
        'gast-lunch-row'
    );
}

$staark_gast_left = staark_gast_p('Veckans lunch', 'gast-eyebrow')
    . staark_gast_h('Lunch som gör eftermiddagen bättre.', 2, 'gast-title')
    . staark_gast_p('Måndag–fredag 11:00–14:00. Alltid ett vegetariskt alternativ – fråga personalen.', 'gast-intro')
    . staark_gast_group(
        staark_gast_p('135 kr', 'gast-lunch-price')
        . staark_gast_p('Inklusive salladsbord, nybakat bröd, lättöl eller vatten och kaffe.', 'gast-lunch-incl'),
        'gast-lunch-price-card'
    );

$staark_gast_right = staark_gast_group(
    staark_gast_p('Den här veckan', 'gast-lunch-week')
    . $staark_gast_rows
    . staark_gast_p('Vegetariskt varje dag: <strong>Veckans gröna</strong> – säsongens grönsaker, baljväxter och örtolja.', 'gast-lunch-veg'),
    'gast-lunch-board'
);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'lunch',
    staark_gast_columns($staark_gast_left, $staark_gast_right, 'gast-lunch-grid', '42%', false, 'gast-lunch-info', 'gast-lunch-list'),
    'lunch'
);
