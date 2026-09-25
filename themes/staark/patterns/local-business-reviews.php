<?php
/**
 * Title: Local Business — Reviews
 * Slug: staark/local-business-reviews
 * Categories: staark, staark-social-proof
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'reviews',
    staark_sl_head('Kundernas ord', 'Förtroende byggs ett jobb i taget.', 'Byt exempeltexterna mot verifierade omdömen från kunden innan publicering.')
    . staark_sl_grid(
        staark_sl_quote('Snabb återkoppling, tydlig kommunikation och ett resultat som blev precis som vi hade tänkt oss.', 'Exempelomdöme · ersätt före publicering', 'sl-quote sl-quote--stars')
        . staark_sl_quote('Vi fick hjälp hela vägen och visste hela tiden vad nästa steg var. Väldigt smidigt.', 'Exempelomdöme · ersätt före publicering', 'sl-quote sl-quote--stars')
        . staark_sl_quote('Professionellt bemötande och bra ordning från första kontakt till färdigt arbete.', 'Exempelomdöme · ersätt före publicering', 'sl-quote sl-quote--stars'),
        3
    ),
    'dark'
);
