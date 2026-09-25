<?php
/**
 * Title: Local Business — Contact
 * Slug: staark/local-business-contact
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'contact',
    staark_sl_split(
        staark_sl_p('Kontakta oss', 'sl-eyebrow')
        . staark_sl_h('Beskriv jobbet. Vi återkommer med nästa steg.', 2, 'sl-title')
        . staark_sl_p('Formuläret sparar din förfrågan direkt hos oss, även om e-posten tillfälligt skulle krångla.', 'sl-intro')
        . staark_sl_facts([
            ['Återkoppling', 'Normalt inom en arbetsdag.'],
            ['Offert', 'Tydligt upplägg innan start.'],
            ['Område', 'Anpassa till företagets ort och serviceområde.'],
        ], 'sl-facts sl-facts--list'),
        staark_sl_group(staark_sl_h('Skicka en förfrågan', 3, 'sl-form-title') . staark_sl_form('local-business-contact', 'Skicka förfrågan'), 'sl-form-card'),
        'sl-contact-grid',
        '46%',
        false
    ),
    'light',
    'kontakt'
);
