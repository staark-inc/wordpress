<?php
/**
 * Title: Showcase — Hero
 * Slug: staark/showcase-hero
 * Categories: staark, staark-heroes
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'hero',
    staark_sl_split(
        staark_sl_p('Moderna WordPress-webbplatser', 'sl-eyebrow')
        . staark_sl_h('Webbplatser som ser dyra ut och får kunder att höra av sig.', 1, 'sl-display')
        . staark_sl_p('Vi bygger snabba, snygga och säljande webbplatser för små och medelstora företag i Sverige — med tydlig struktur, SEO-grund och personlig support.', 'sl-lead')
        . staark_sl_buttons([['Få en kostnadsfri offert', '/kontakt/'], ['Se våra projekt', '/projekt/', 'outline']])
        . staark_sl_list(['Mobile first', 'SEO-ready', 'Svensk support'], 'sl-checks sl-checks--inline'),
        staark_sl_image('assets/images/showcase/light-hero.webp', 'Exempel på en modern webbplats presenterad på laptop', 'sl-hero-image'),
        'sl-hero-grid',
        '54%'
    )
    . staark_sl_facts([
        ['Mobile first', 'Byggd för små skärmar först.'],
        ['SEO-ready', 'Teknisk grund från start.'],
        ['Modern design', 'Tydligt, snabbt och professionellt.'],
        ['Svensk support', 'När du faktiskt behöver hjälp.'],
    ], 'sl-facts sl-facts--bar alignwide'),
    'light'
);
