<?php
/**
 * Title: Showcase — CTA
 * Slug: staark/showcase-cta
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'cta',
    staark_sl_group(
        staark_sl_group(
            staark_sl_p('Redo att ta nästa steg?', 'sl-eyebrow')
            . staark_sl_h('Låt oss skapa en webbplats som får ditt företag att växa.', 2, 'sl-title')
            . staark_sl_buttons([['Få en kostnadsfri offert', '/kontakt/'], ['Kontakta oss', '/kontakt/', 'outline']]),
            'sl-cta-copy'
        )
        . staark_sl_image('assets/images/showcase/light-cta.webp', '', 'sl-cta-image'),
        'sl-cta-card',
        ['type' => 'default'],
        'wide'
    ),
    'light'
);
