<?php
/**
 * Title: Business — CTA
 * Slug: staark/cta-business
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'band',
    staark_sl_group(
        staark_sl_group(
            staark_sl_p('Nästa steg', 'sl-eyebrow')
            . staark_sl_h('Har företaget vuxit ifrån sin nuvarande webbplats?', 2, 'sl-title')
            . staark_sl_p('Berätta vad som inte fungerar idag. Vi återkommer med ett tydligt nästa steg.', 'sl-intro'),
            'sl-band-copy'
        )
        . staark_sl_buttons([['Starta ett projekt', '/kontakt']]),
        'sl-band',
        ['type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'center'],
        'wide'
    ),
    'accent'
);
