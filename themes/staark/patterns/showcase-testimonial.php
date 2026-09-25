<?php
/**
 * Title: Showcase — Testimonial
 * Slug: staark/showcase-testimonial
 * Categories: staark, staark-social-proof
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'testimonial',
    staark_sl_split(
        staark_sl_p('Vad våra kunder säger', 'sl-eyebrow')
        . staark_sl_h('Riktiga företag. Riktiga resultat.', 2, 'sl-title')
        . staark_sl_quote('Här placerar vi ett verifierat kundomdöme när sajten går live — med kundens namn, företag och ett konkret resultat.', '<strong>Demo-innehåll</strong> · ersätt med verifierat kundcitat före publicering', 'sl-quote sl-quote--feature'),
        staark_sl_image('assets/images/showcase/light-testimonial.webp', 'Ljus modern företagsmiljö', 'sl-media'),
        'sl-testimonial-grid',
        '56%'
    ),
    'surface'
);
