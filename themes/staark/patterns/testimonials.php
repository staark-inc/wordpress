<?php
/**
 * Title: Business — Testimonials
 * Slug: staark/testimonials-business
 * Categories: staark, staark-social-proof
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'reviews',
    staark_sl_head('Kundupplevelse', 'Bra samarbete märks långt före lanseringen.', 'Ersätt exemplen med verifierade kundomdömen när respektive webbplats går live.')
    . staark_sl_grid(
        staark_sl_quote('Vi fick en tydligare webbplats och kunderna hittar rätt mycket snabbare.', 'Exempelomdöme · ersätt före publicering')
        . staark_sl_quote('Processen var enkel, snabb och vi visste hela tiden vad nästa steg var.', 'Exempelomdöme · ersätt före publicering')
        . staark_sl_quote('Sidan känns äntligen som företaget vi faktiskt är idag.', 'Exempelomdöme · ersätt före publicering'),
        3
    ),
    'light'
);
