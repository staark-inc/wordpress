<?php
/**
 * Title: Gästfrihet — Boka (uppmaning)
 * Slug: staark/gast-cta
 * Categories: staark-gastfrihet, staark-conversion
 * Keywords: boka, boka bord, boka rum, cta
 * Viewport Width: 1400
 * Description: Closing call to action with the booking button.
 */

$staark_gast_hotel = staark_gast_is_hotel();

$staark_gast_copy = staark_gast_p($staark_gast_hotel ? 'Välkommen' : 'Vi ses vid bordet', 'gast-eyebrow')
    . staark_gast_h($staark_gast_hotel ? 'Ditt rum väntar.' : 'Ett bord väntar på dig.', 2, 'gast-title')
    . staark_gast_p($staark_gast_hotel ? 'Boka direkt hos oss för bästa pris, frukost och flexibel avbokning.' : 'Boka bord för lunch eller middag – eller hör av dig om sällskap och event.', 'gast-intro');

$staark_gast_actions = staark_gast_buttons([
    [$staark_gast_hotel ? 'Boka rum' : 'Boka bord', '/boka/'],
    ['Hitta hit', '/kontakt/', 'outline'],
]);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'cta',
    staark_gast_group(
        staark_gast_group($staark_gast_copy, 'gast-cta-copy') . $staark_gast_actions,
        'gast-cta-inner',
        ['type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'center'],
        'wide'
    )
);
