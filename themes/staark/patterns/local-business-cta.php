<?php
/**
 * Title: Local Business — CTA
 * Slug: staark/local-business-cta
 * Categories: staark, staark-conversion
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'band',
    staark_sl_group(
        staark_sl_group(
            staark_sl_p('Redo att komma vidare?', 'sl-eyebrow')
            . staark_sl_h('Få ett tydligt nästa steg — utan förpliktelser.', 2, 'sl-title'),
            'sl-band-copy'
        )
        . staark_sl_buttons([['Begär offert', '/kontakt']]),
        'sl-band',
        ['type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'center'],
        'wide'
    ),
    'accent'
);
