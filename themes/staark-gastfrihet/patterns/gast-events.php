<?php
/**
 * Title: Restaurang — Sällskap & event
 * Slug: staark/gast-events
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, sällskap, event, privat matsal, företag, fest
 * Viewport Width: 1400
 * Description: Private dining and events: intro with image and three package cards.
 */

$staark_gast_packages = [
    ['Upp till 24 gäster', 'Privat matsal', 'Eget rum för middag, födelsedag eller kundmiddag. Vi sätter ihop menyn med dig.', 'Från 595 kr / person'],
    ['Upp till 60 gäster', 'Mingel i baren', 'Plockmat, bubbel och drinkar – perfekt för afterwork, release eller jubileum.', 'Från 345 kr / person'],
    ['Upp till 90 gäster', 'Hela restaurangen', 'Stäng dörrarna för bröllop, julbord eller företagsfest med egen meny och dryck.', 'Offert efter önskemål'],
];

$staark_gast_cards = '';
foreach ($staark_gast_packages as $staark_gast_index => $staark_gast_package) {
    $staark_gast_cards .= staark_gast_group(
        staark_gast_p(esc_html($staark_gast_package[0]), 'gast-card-tag')
        . staark_gast_h(esc_html($staark_gast_package[1]), 3, 'gast-card-title')
        . staark_gast_p(esc_html($staark_gast_package[2]), 'gast-card-text')
        . staark_gast_p(esc_html($staark_gast_package[3]), 'gast-card-price'),
        'gast-card'
    );
}

$staark_gast_copy = staark_gast_p('Sällskap &amp; event', 'gast-eyebrow')
    . staark_gast_h('Samla alla runt samma bord.', 2, 'gast-title')
    . staark_gast_p('Från tolv vänner till hela företaget. Vi hjälper dig med meny, dryck, placering och tider – du behöver bara bjuda in.', 'gast-intro')
    . staark_gast_list([
        'Anpassad meny efter säsong, budget och allergier',
        'Dryckespaket med vin, öl och alkoholfritt',
        'Tal, musik och projektor går att ordna',
        'En kontaktperson från förfrågan till kvällen',
    ], 'gast-checks')
    . staark_gast_buttons([['Skicka förfrågan', '/boka/'], ['Ring oss', '/kontakt/', 'outline']]);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'events',
    staark_gast_columns(
        staark_gast_image('assets/images/rest-events.webp', 'Illustration av ett långbord dukat för sällskap – ersätt med en bild från er matsal', 'gast-media'),
        $staark_gast_copy,
        'gast-events-grid',
        '50%',
        true,
        'gast-events-media',
        'gast-events-copy'
    )
    . staark_gast_grid($staark_gast_cards, 3, 'gast-card-grid gast-events-cards'),
    'sallskap'
);
