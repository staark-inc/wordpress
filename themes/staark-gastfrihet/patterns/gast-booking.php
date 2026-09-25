<?php
/**
 * Title: Gästfrihet — Boka bord / Boka rum
 * Slug: staark/gast-booking
 * Categories: staark-gastfrihet, staark-conversion
 * Keywords: boka, bokning, boka bord, boka rum, bokningsförfrågan
 * Viewport Width: 1400
 * Description: Booking section: external booking link (Appearance → Bokning) and a booking request through Staark Hub Forms. The hidden list feeds the form: time slots (restaurant) or room types (hotel).
 */

$staark_gast_hotel = staark_gast_is_hotel();
$staark_gast_mode = staark_gast_mode();

$staark_gast_options = $staark_gast_hotel
    ? ['Enkelrum', 'Dubbelrum', 'Svit']
    : ['11:30', '12:00', '12:30', '13:00', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'];

$staark_gast_steps = $staark_gast_hotel
    ? [
        '<strong>Skicka förfrågan</strong> med datum, antal gäster och rum.',
        '<strong>Vi bekräftar</strong> tillgänglighet och pris per e-post, oftast samma dag.',
        '<strong>Välkommen</strong> – incheckning från 15:00.',
    ]
    : [
        '<strong>Skicka förfrågan</strong> med datum, tid och antal gäster.',
        '<strong>Vi bekräftar</strong> per e-post eller sms – oftast inom ett par timmar.',
        '<strong>Välkommen</strong> – säg till om allergier när du kommer.',
    ];

/*
 * The intro and steps follow Appearance → Bokning: link and form, only the
 * link, or only the form (also when no link has been added yet).
 */
$staark_gast_booking = staark_gast_booking_settings();
$staark_gast_has_link = $staark_gast_booking['url'] !== '' && $staark_gast_booking['mode'] !== 'form';
$staark_gast_has_form = $staark_gast_booking['mode'] !== 'external';

if ($staark_gast_has_link && $staark_gast_has_form) {
    $staark_gast_intro = $staark_gast_hotel
        ? 'Boka direkt online och få besked på en gång, eller skicka en förfrågan så återkommer vi med bekräftelse och pris.'
        : 'Boka direkt online, eller skicka en förfrågan så bekräftar vi bordet.';
} elseif ($staark_gast_has_link) {
    $staark_gast_intro = $staark_gast_hotel
        ? 'Se lediga rum och priser och boka direkt online – du får bekräftelsen på en gång.'
        : 'Se lediga tider och boka direkt online – du får bekräftelsen på en gång.';
} else {
    $staark_gast_intro = $staark_gast_hotel
        ? 'Skicka en bokningsförfrågan så återkommer vi med bekräftelse och pris – oftast samma dag.'
        : 'Skicka en bokningsförfrågan så bekräftar vi bordet – oftast inom ett par timmar.';
}

if (! $staark_gast_hotel) {
    $staark_gast_intro .= ' Sällskap över 8 personer? Se <a href="/sallskap/">Sällskap &amp; event</a>.';
}

$staark_gast_info = staark_gast_p($staark_gast_hotel ? 'Boka rum' : 'Boka bord', 'gast-eyebrow')
    . staark_gast_h($staark_gast_hotel ? 'Boka din vistelse.' : 'Boka ditt bord.', 2, 'gast-title')
    . staark_gast_p($staark_gast_intro, 'gast-intro')
    . staark_gast_group(
        staark_gast_p('Boka online', 'gast-online-label')
        . staark_gast_p('[gast_booking_link]', 'gast-online-link')
        . staark_gast_p($staark_gast_hotel ? 'Se lediga rum och priser och få bekräftelse direkt.' : 'Se lediga tider och få bekräftelse direkt.', 'gast-online-note'),
        'gast-online'
    )
    . ($staark_gast_has_form ? staark_gast_list($staark_gast_steps, 'gast-next-steps', true) : '')
    . staark_gast_group(
        staark_gast_p('<span>Telefon</span> [gast_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data')
        . staark_gast_p('<span>E-post</span> [gast_contact field="email" link="1" hint="Lägg till e-post i Staark Hub → SEO"]', 'gast-contact-row gast-contact-row--data'),
        'gast-contact-card'
    );

$staark_gast_form = staark_gast_h('Bokningsförfrågan', 3, 'gast-form-title')
    . staark_gast_p(
        $staark_gast_hotel ? 'Vi svarar med bekräftelse och pris. Bokningen gäller när du fått vår bekräftelse.' : 'Bordet är bokat när du fått vår bekräftelse.',
        'gast-form-note'
    )
    . staark_gast_list(array_map('esc_html', $staark_gast_options), 'gast-booking-options')
    . (
        shortcode_exists('staark_contact_form')
            ? staark_gast_shortcode(sprintf(
                '[staark_contact_form title="Bokningsförfrågan" button="Skicka bokningsförfrågan" form_id="%s"]',
                $staark_gast_hotel ? 'gast-rum' : 'gast-bord'
            ))
            : staark_gast_p('Bokningsformuläret visas när Staark Hub är aktiverat. Fram till dess: ring eller mejla oss. [gast_contact field="email" link="1"]', 'gast-form-fallback')
    );

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'booking',
    staark_gast_columns($staark_gast_info, $staark_gast_form, 'gast-booking-grid', '44%', false, 'gast-booking-info', 'gast-booking-form'),
    'boka',
    'gast-booking--' . $staark_gast_mode
);
