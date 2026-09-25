<?php
/**
 * Title: Gästfrihet — Hero
 * Slug: staark/gast-hero
 * Categories: staark-gastfrihet, staark-heroes
 * Keywords: restaurang, hotell, hero, boka bord, boka rum
 * Viewport Width: 1400
 * Description: Opening section. Copy, image and buttons follow the active preset (Restaurang or Hotell).
 */

$staark_gast_hotel = staark_gast_is_hotel();

$staark_gast_copy = $staark_gast_hotel
    ? [
        'eyebrow' => 'Hotell · Mitt i stan, nära naturen',
        'title' => 'Sov gott. Vakna <em>utvilad</em>.',
        'lead' => 'Ljusa rum, en frukost värd att gå upp för och personal som minns ditt namn. Boka direkt hos oss för bästa pris och flexibla villkor.',
        'buttons' => [['Boka rum', '/boka/'], ['Se våra rum', '/rum/', 'outline']],
        'image' => 'assets/images/hotel-hero.webp',
        'alt' => 'Illustration av ett hotellrum – ersätt med en bild från ert hotell',
        'card_label' => 'Bra att veta',
        'card' => ['<strong>Incheckning</strong> från 15:00', '<strong>Utcheckning</strong> senast 11:00', '<strong>Frukost</strong> ingår alltid'],
        'facts' => [
            ['Bästa pris direkt', 'Boka här – inga mellanhänder.'],
            ['Fri avbokning', 'Fram till 18:00 dagen före ankomst.'],
            ['Frukost ingår', 'Lokala råvaror, bakat på morgonen.'],
            ['Gratis wifi', 'Snabbt i alla rum och allmänna ytor.'],
        ],
    ]
    : [
        'eyebrow' => 'Restaurang · Säsong & hantverk',
        'title' => 'Mat att <em>minnas</em>, kväll efter kväll.',
        'lead' => 'Säsongens råvaror från gårdar i närheten, lagade med omsorg och serverade utan krusiduller. Välkommen på lunch, middag eller ett glas i baren.',
        'buttons' => [['Boka bord', '/boka/'], ['Se menyn', '/meny/', 'outline']],
        'image' => 'assets/images/rest-hero.webp',
        'alt' => 'Illustration av ett dukat bord – ersätt med en bild från er restaurang',
        'card_label' => 'Öppettider',
        'card' => ['<strong>Lunch</strong> mån–fre 11:00–14:00', '<strong>Middag</strong> tis–lör från 17:00', '<strong>Bar</strong> fre–lör till 01:00'],
        'facts' => [
            ['Säsongens meny', 'Byts efter vad gårdarna skördar.'],
            ['Lokala råvaror', 'Kött, fisk och grönt från trakten.'],
            ['Naturvin & alkoholfritt', 'Lika mycket omsorg i glaset.'],
            ['Sällskap välkomna', 'Privat matsal för upp till 24.'],
        ],
    ];

$staark_gast_facts = '';
foreach ($staark_gast_copy['facts'] as $staark_gast_fact) {
    $staark_gast_facts .= staark_gast_group(
        staark_gast_p(esc_html($staark_gast_fact[0]), 'gast-fact-title') . staark_gast_p(esc_html($staark_gast_fact[1]), 'gast-fact-text'),
        'gast-fact'
    );
}

$staark_gast_copy_col = staark_gast_p(esc_html($staark_gast_copy['eyebrow']), 'gast-eyebrow')
    . staark_gast_h($staark_gast_copy['title'], 1, 'gast-display')
    . staark_gast_p(esc_html($staark_gast_copy['lead']), 'gast-lead')
    . staark_gast_buttons($staark_gast_copy['buttons'])
    . staark_gast_p('Eller ring oss: [gast_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]', 'gast-hero-call');

$staark_gast_media = staark_gast_image($staark_gast_copy['image'], $staark_gast_copy['alt'], 'gast-hero-image')
    . staark_gast_group(
        staark_gast_p(esc_html($staark_gast_copy['card_label']), 'gast-hero-card-label')
        . staark_gast_list($staark_gast_copy['card'], 'gast-hero-card-list'),
        'gast-hero-card'
    );

echo staark_gast_group( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    staark_gast_columns($staark_gast_copy_col, $staark_gast_media, 'gast-hero-grid', '54%', true, 'gast-hero-copy', 'gast-hero-media')
    . staark_gast_grid($staark_gast_facts, 4, 'gast-facts'),
    'gast-hero',
    ['type' => 'constrained'],
    'full'
);
