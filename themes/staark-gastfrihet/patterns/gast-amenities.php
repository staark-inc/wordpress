<?php
/**
 * Title: Hotell — Faciliteter
 * Slug: staark/gast-amenities
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: hotell, faciliteter, frukost, spa, parkering, wifi
 * Viewport Width: 1400
 * Description: Hotel amenities as a grid of short facts.
 */

$staark_gast_items = [
    ['Frukostbuffé', 'Varje dag 07:00–10:00, helger till 11:00.'],
    ['Gratis wifi', 'Snabbt i alla rum och allmänna ytor.'],
    ['Parkering', 'Egen parkering och laddplatser för elbil.'],
    ['Spa &amp; bastu', 'Bastu, relaxavdelning och behandlingar.'],
    ['Restaurang &amp; bar', 'Middag och lokala drycker i huset.'],
    ['Reception dygnet runt', 'Vi finns här när du behöver oss.'],
    ['Cyklar att låna', 'Upptäck omgivningarna i egen takt.'],
    ['Husdjur välkomna', 'I utvalda rum – meddela vid bokning.'],
];

$staark_gast_facts = '';
foreach ($staark_gast_items as $staark_gast_item) {
    $staark_gast_facts .= staark_gast_group(
        staark_gast_p($staark_gast_item[0], 'gast-fact-title') . staark_gast_p($staark_gast_item[1], 'gast-fact-text'),
        'gast-fact'
    );
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'amenities',
    staark_gast_head('Faciliteter', 'Allt du behöver under samma tak.', 'Ingår i vistelsen om inget annat anges. Spabehandlingar och parkering bokas i receptionen.')
    . staark_gast_grid($staark_gast_facts, 4, 'gast-amenity-grid'),
    'faciliteter'
);
