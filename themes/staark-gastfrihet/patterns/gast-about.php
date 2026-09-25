<?php
/**
 * Title: Gästfrihet — Om oss
 * Slug: staark/gast-about
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, hotell, om oss, historia, värderingar
 * Viewport Width: 1400
 * Description: Story with image and three values. Copy follows the active preset.
 */

$staark_gast_hotel = staark_gast_is_hotel();

$staark_gast_copy = $staark_gast_hotel
    ? [
        'eyebrow' => 'Om hotellet',
        'title' => 'Ett personligt hotell med gamla anor.',
        'intro' => 'Huset byggdes som handelshus och har tagit emot resenärer i över hundra år. I dag driver vi det som ett familjeägt hotell – med moderna rum, en frukost vi är stolta över och tid för varje gäst.',
        'image' => 'assets/images/hotel-about.webp',
        'alt' => 'Illustration av hotellets fasad – ersätt med en bild av ert hotell',
        'values' => [
            '<strong>Personligt bemötande</strong>Vi tipsar om allt från promenader till middagsbord.',
            '<strong>Hållbart på riktigt</strong>Miljömärkt drift, lokala leverantörer och mindre svinn.',
            '<strong>Ett lugn att landa i</strong>Tysta rum, sköna sängar och mörkläggning i alla rum.',
        ],
        'button' => ['Se våra rum', '/rum/'],
    ]
    : [
        'eyebrow' => 'Om oss',
        'title' => 'Ett kvarterskök med stora ambitioner.',
        'intro' => 'Vi öppnade för att laga den mat vi själva vill äta: ärlig, säsongsbunden och full av smak. Råvarorna kommer från gårdar och fiskare vi känner, och menyn skrivs om när skörden skiftar.',
        'image' => 'assets/images/rest-about.webp',
        'alt' => 'Illustration av restaurangens bar – ersätt med en bild från er restaurang',
        'values' => [
            '<strong>Säsong först</strong>Vi lagar det som är bäst just nu – inte det som alltid finns.',
            '<strong>Nära producenter</strong>Kött, fisk och grönt från trakten, med namn på gården.',
            '<strong>Välkomnande</strong>Lika rätt för en snabb lunch som för en lång middag.',
        ],
        'button' => ['Boka bord', '/boka/'],
    ];

$staark_gast_text = staark_gast_p($staark_gast_copy['eyebrow'], 'gast-eyebrow')
    . staark_gast_h($staark_gast_copy['title'], 2, 'gast-title')
    . staark_gast_p($staark_gast_copy['intro'], 'gast-intro')
    . staark_gast_list($staark_gast_copy['values'], 'gast-values')
    . staark_gast_buttons([[$staark_gast_copy['button'][0], $staark_gast_copy['button'][1], 'outline']]);

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'about',
    staark_gast_columns(
        staark_gast_image($staark_gast_copy['image'], $staark_gast_copy['alt'], 'gast-media'),
        $staark_gast_text,
        'gast-about-grid',
        '48%',
        true,
        'gast-about-media',
        'gast-about-copy'
    ),
    'om-oss'
);
