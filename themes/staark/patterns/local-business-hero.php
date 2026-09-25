<?php
/**
 * Title: Local Business — Hero
 * Slug: staark/local-business-hero
 * Categories: staark, staark-heroes
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'hero',
    staark_sl_split(
        staark_sl_p('Lokalt företag · Personlig service', 'sl-eyebrow')
        . staark_sl_h('Pålitlig hjälp när du behöver den.', 1, 'sl-display')
        . staark_sl_p('Vi hjälper privatpersoner och företag med tydliga offerter, snabb återkoppling och ett arbete vi kan stå för.', 'sl-lead')
        . staark_sl_buttons([['Begär kostnadsfri offert', '/kontakt'], ['Se våra tjänster', '#tjanster', 'outline']])
        . staark_sl_list(['Snabb återkoppling', 'Tydliga priser', 'Lokal service'], 'sl-checks sl-checks--inline'),
        staark_sl_group(
            staark_sl_p('Behöver du hjälp?', 'sl-eyebrow')
            . staark_sl_h('Berätta vad du behöver så tar vi nästa steg tillsammans.', 3, 'sl-panel-title')
            . staark_sl_p('Skicka en kort förfrågan. Vi återkommer normalt inom en arbetsdag.', 'sl-panel-text')
            . staark_sl_p('[staark_contact field="phone" link="1"]', 'sl-panel-phone')
            . staark_sl_buttons([['Kontakta oss idag', '/kontakt']], 'sl-actions sl-actions--panel'),
            'sl-panel sl-panel--dark'
        ),
        'sl-hero-grid',
        '56%'
    ),
    'light'
);
