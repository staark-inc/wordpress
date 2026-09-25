<?php
/**
 * Title: Gästfrihet — Kontakt
 * Slug: staark/gast-contact
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: kontakt, telefon, e-post, adress
 * Viewport Width: 1400
 * Description: Direct contact details from Staark Hub and a booking call to action.
 */

$staark_gast_hotel = staark_gast_is_hotel();

$staark_gast_left = staark_gast_p('Kontakt', 'gast-eyebrow')
    . staark_gast_h($staark_gast_hotel ? 'Vi hjälper dig gärna.' : 'Hör av dig till oss.', 2, 'gast-title')
    . staark_gast_p(
        $staark_gast_hotel
            ? 'Frågor om rum, grupper, konferens eller tillgänglighet? Receptionen svarar dygnet runt.'
            : 'Frågor om menyn, allergier, presentkort eller sällskap? Ring eller mejla oss så svarar vi så snart vi kan.',
        'gast-intro'
    )
    . staark_gast_group(
        staark_gast_p('<span>Telefon</span> [gast_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
        . staark_gast_p('<span>E-post</span> [gast_contact field="email" link="1" hint="Lägg till e-post i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
        . staark_gast_p('<span>Adress</span> [gast_contact field="address" link="1" hint="Lägg till adress i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data'),
        'gast-contact-card'
    );

$staark_gast_right = staark_gast_group(
    staark_gast_p($staark_gast_hotel ? 'Planerar du en resa?' : 'Sugen på middag?', 'gast-eyebrow')
    . staark_gast_h($staark_gast_hotel ? 'Boka ditt rum direkt.' : 'Boka ett bord.', 3, 'gast-panel-title')
    . staark_gast_p($staark_gast_hotel ? 'Bästa pris och frukost ingår när du bokar hos oss.' : 'Lunch utan bokning, middag rekommenderar vi att boka.', 'gast-panel-note')
    . staark_gast_buttons([[$staark_gast_hotel ? 'Boka rum' : 'Boka bord', '/boka/']]),
    'gast-panel gast-contact-cta'
);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'contact',
    staark_gast_columns($staark_gast_left, $staark_gast_right, 'gast-contact-grid', '56%', true, 'gast-contact-info', 'gast-contact-side'),
    'kontakt'
);
