<?php
/**
 * Title: Business — CTA (light)
 * Slug: staark/cta-light
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'band',
    staark_sl_group(
        staark_sl_group(
            staark_sl_p('Redo att komma vidare?', 'sl-eyebrow')
            . staark_sl_h('Berätta vad du behöver — vi återkommer med ett tydligt nästa steg.', 2, 'sl-title'),
            'sl-band-copy'
        )
        . staark_sl_buttons([['Kontakta oss', '/kontakt/']]),
        'sl-band',
        ['type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'center'],
        'wide'
    ),
    'surface'
);
