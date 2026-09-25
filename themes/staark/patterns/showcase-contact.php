<?php
/**
 * Title: Showcase — Contact
 * Slug: staark/showcase-contact
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'contact',
    staark_sl_split(
        staark_sl_p('Kontakt', 'sl-eyebrow')
        . staark_sl_h('Berätta vad du vill förbättra.', 2, 'sl-title')
        . staark_sl_p('Ny webbplats, gammal webbplats eller bara en idé? Skicka några rader så tar vi nästa steg tillsammans.', 'sl-intro')
        . staark_sl_steps([
            ['Kort introduktion', 'Berätta om företaget och vad som inte fungerar idag.'],
            ['Förslag &amp; omfattning', 'Vi föreslår upplägg, funktioner och nästa steg.'],
            ['Tydlig start', 'När allt känns rätt sätter vi plan och produktion i gång.'],
        ], 'sl-steps--list sl-steps--compact'),
        staark_sl_group(staark_sl_h('Kontakta oss', 3, 'sl-form-title') . staark_sl_form('staark-home', 'Skicka förfrågan'), 'sl-form-card'),
        'sl-contact-grid',
        '46%',
        false
    ),
    'surface',
    'kontakt'
);
