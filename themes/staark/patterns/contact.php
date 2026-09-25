<?php
/**
 * Title: Business — Contact
 * Slug: staark/contact-business
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'contact',
    staark_sl_split(
        staark_sl_p('Kontakt', 'sl-eyebrow')
        . staark_sl_h('Berätta vad du vill förbättra.', 2, 'sl-title')
        . staark_sl_p('Du behöver inget färdigt underlag. Skriv vad företaget behöver hjälp med, vad som känns gammalt eller vad som inte fungerar idag.', 'sl-intro')
        . staark_sl_steps([
            ['Kort behov', 'Skriv vad du vill förbättra.'],
            ['Tydligt förslag', 'Vi återkommer med upplägg och nästa steg.'],
            ['Bygg &amp; lansering', 'När allt känns rätt börjar vi bygga.'],
        ], 'sl-steps--list sl-steps--compact')
        . staark_sl_p('Hellre mejl? [staark_contact field="email" link="1" fallback="Använd formuläret"]', 'sl-contact-line'),
        staark_sl_group(staark_sl_h('Skicka en förfrågan', 3, 'sl-form-title') . staark_sl_form('contact', 'Skicka förfrågan'), 'sl-form-card'),
        'sl-contact-grid',
        '46%',
        false
    ),
    'surface',
    'kontakt'
);
