<?php
/**
 * Title: Showcase — Why us
 * Slug: staark/showcase-why
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'why',
    staark_sl_split(
        staark_sl_p('Varför välja Staark?', 'sl-eyebrow')
        . staark_sl_h('Mer än bara en webbplats.', 2, 'sl-title')
        . staark_sl_p('Vi kombinerar design, teknik och affärsförståelse. Målet är inte fler plugins — målet är en enklare webbplats som gör rätt saker riktigt bra.', 'sl-intro')
        . staark_sl_facts([
            ['Modern design', 'Tidlöst uttryck med fokus på tydlighet.'],
            ['Affärsdrivet', 'Designen ska hjälpa besökaren ta nästa steg.'],
            ['Personlig service', 'Direkt kontakt när något behöver lösas.'],
            ['Långsiktigt', 'Support och förbättringar även efter lansering.'],
        ], 'sl-facts sl-facts--grid'),
        staark_sl_image('assets/images/showcase/light-why.webp', 'Modern nordisk arkitektur', 'sl-media'),
        'sl-why-grid',
        '54%'
    ),
    'dark'
);
