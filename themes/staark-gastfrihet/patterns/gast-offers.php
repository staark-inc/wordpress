<?php
/**
 * Title: Hotell — Erbjudanden & paket
 * Slug: staark/gast-offers
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: hotell, erbjudanden, paket, spa, weekend
 * Viewport Width: 1400
 * Description: Three packages with image, description and price.
 */

$staark_gast_offers = [
    ['assets/images/hotel-spa.webp', 'Paket', 'Spaweekend', 'Två nätter, frukost, tillgång till spa och en 50 minuters behandling per person.', 'Från 2 890 kr / person'],
    ['assets/images/hotel-breakfast.webp', 'Paket', 'Bed &amp; breakfast', 'Övernattning med vår frukostbuffé och sen utcheckning kl. 13:00.', 'Från 1 195 kr / rum'],
    ['assets/images/room-3.webp', 'Paket', 'Romantisk natt', 'Svit, bubbel på rummet, trerätters middag och frukost på sängen.', 'Från 3 450 kr / rum'],
];

$staark_gast_cards = '';
foreach ($staark_gast_offers as $staark_gast_offer) {
    $staark_gast_cards .= staark_gast_group(
        staark_gast_image($staark_gast_offer[0], 'Illustration för paketet – ersätt med en egen bild', 'gast-card-media')
        . staark_gast_group(
            staark_gast_p($staark_gast_offer[1], 'gast-card-tag')
            . staark_gast_h($staark_gast_offer[2], 3, 'gast-card-title')
            . staark_gast_p(esc_html($staark_gast_offer[3]), 'gast-card-text')
            . staark_gast_p(esc_html($staark_gast_offer[4]), 'gast-card-price')
            . staark_gast_p('<a href="/boka/">Boka paketet →</a>', 'gast-card-link'),
            'gast-card-body'
        ),
        'gast-card gast-card--media'
    );
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'offers',
    staark_gast_head('Erbjudanden', 'Lite extra för din vistelse.', 'Paketen bokas direkt hos oss. Ange paketets namn i din bokningsförfrågan så ordnar vi resten.')
    . staark_gast_grid($staark_gast_cards, 3, 'gast-card-grid'),
    'erbjudanden'
);
