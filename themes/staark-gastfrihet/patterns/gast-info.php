<?php
/**
 * Title: Gästfrihet — Öppettider & hitta hit
 * Slug: staark/gast-info
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: öppettider, adress, hitta hit, parkering, reception
 * Viewport Width: 1400
 * Description: Opening hours (restaurant) or reception times (hotel), address with map link and travel notes. Contact details come from Staark Hub.
 */

$staark_gast_hotel = staark_gast_is_hotel();

$staark_gast_hours = $staark_gast_hotel
    ? [
        ['Reception', 'Dygnet runt'],
        ['Incheckning', 'Från 15:00'],
        ['Utcheckning', 'Senast 11:00'],
        ['Frukost', '07:00–10:00 (helg till 11:00)'],
        ['Restaurang', 'Mån–lör 17:00–22:00'],
    ]
    : [
        ['Måndag', 'Lunch 11:00–14:00'],
        ['Tisdag–torsdag', 'Lunch 11–14 · Middag 17–22'],
        ['Fredag', 'Lunch 11–14 · Middag 17–23'],
        ['Lördag', 'Middag 17:00–23:00'],
        ['Söndag', 'Stängt'],
    ];

$staark_gast_rows = '';
foreach ($staark_gast_hours as $staark_gast_row) {
    $staark_gast_rows .= staark_gast_p('<span>' . esc_html($staark_gast_row[0]) . '</span><span>' . esc_html($staark_gast_row[1]) . '</span>', 'gast-hours-row');
}

$staark_gast_left = staark_gast_group(
    staark_gast_h($staark_gast_hotel ? 'Tider' : 'Öppettider', 3, 'gast-panel-title')
    . $staark_gast_rows
    . staark_gast_p($staark_gast_hotel ? 'Sen ankomst? Hör av dig så lämnar vi nyckeln i receptionen.' : 'Helgdagar kan avvika – se våra sociala medier eller ring oss.', 'gast-panel-note'),
    'gast-panel gast-hours'
);

$staark_gast_right = staark_gast_group(
    staark_gast_h('Hitta hit', 3, 'gast-panel-title')
    . staark_gast_p('<span>Adress</span> [gast_contact field="address" link="1" hint="Lägg till adress i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
    . staark_gast_p('<span>Telefon</span> [gast_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
    . staark_gast_p('<span>E-post</span> [gast_contact field="email" link="1" hint="Lägg till e-post i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
    . staark_gast_p('<span>Resa</span> ' . ($staark_gast_hotel ? 'Fem minuters promenad från stationen. Egen parkering med laddplatser.' : 'Tio minuters promenad från stationen. Gatuparkering och p-hus i kvarteret.'), 'gast-contact-row')
    . staark_gast_p('[gast_contact field="address" link="1" label="Öppna vägbeskrivning ↗"]', 'gast-map-link'),
    'gast-panel gast-find'
);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'info',
    staark_gast_head(
        $staark_gast_hotel ? 'Praktiskt' : 'Besök oss',
        $staark_gast_hotel ? 'Bra att veta före ankomst.' : 'Öppettider &amp; hitta hit.',
        '',
        false
    )
    . staark_gast_columns($staark_gast_left, $staark_gast_right, 'gast-info-grid', '50%', false, 'gast-info-hours', 'gast-info-find'),
    'hitta-hit'
);
