<?php
/**
 * Title: Gästfrihet — Omdömen
 * Slug: staark/gast-reviews
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, hotell, omdömen, recensioner, gäster
 * Viewport Width: 1400
 * Description: Sample guest quotes marked as examples. Replace them with verified reviews before publishing.
 */

$staark_gast_reviews = staark_gast_pick(
    [
        'Bästa högreven jag ätit, och personalen gav riktigt bra vintips. Vi kommer tillbaka!',
        'Perfekt för lunchmöten – snabb service utan att det känns stressigt, och alltid ett bra vegetariskt alternativ.',
        'Vi firade 50-årsdag i den privata matsalen. Allt var genomtänkt, från menyn till placeringen.',
    ],
    [
        'Sängarna är fantastiska och frukosten är värd resan i sig. Personalen fixade sen utcheckning utan krångel.',
        'Lugnt, rent och centralt. Perfekt när jag reser i jobbet – snabb wifi och ett riktigt skrivbord.',
        'Spaweekenden var precis den paus vi behövde. Varmt bemötande från incheckning till utcheckning.',
    ]
);

$staark_gast_quotes = '';
foreach ($staark_gast_reviews as $staark_gast_review) {
    $staark_gast_quotes .= staark_gast_quote(esc_html($staark_gast_review), 'Exempelomdöme · ersätt före publicering');
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'reviews',
    staark_gast_head(
        'Gästernas ord',
        staark_gast_pick('Därför kommer de tillbaka.', 'Gäster som gärna kommer tillbaka.'),
        'Byt exempeltexterna mot verifierade omdömen – gärna med länk till Google, Tripadvisor eller Booking.com – innan sidan publiceras.'
    )
    . staark_gast_grid($staark_gast_quotes, 3, 'gast-review-grid')
);
