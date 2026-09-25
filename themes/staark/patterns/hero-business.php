<?php
/**
 * Title: Business — Hero
 * Slug: staark/hero-business
 * Categories: staark, staark-heroes
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'hero',
    staark_sl_split(
        staark_sl_p('Webbdesign · SEO · Support', 'sl-eyebrow')
        . staark_sl_h('Din webbplats ska göra jobbet innan du behöver sälja.', 1, 'sl-display')
        . staark_sl_p('Vi bygger snabba, tydliga och välgjorda hemsidor för svenska företag — med struktur, lokal SEO och support från början.', 'sl-lead')
        . staark_sl_buttons([['Få en kostnadsfri offert', '/kontakt'], ['Se hur vi jobbar', '#process', 'outline']]),
        staark_sl_group(
            staark_sl_p('Byggd för förtroende', 'sl-eyebrow')
            . staark_sl_h('Tydligt budskap. Rätt känsla. Färre hinder till kontakt.', 3, 'sl-panel-title')
            . staark_sl_list(['<strong>Mobile first</strong> — varje beslut börjar på den minsta skärmen.', '<strong>SEO-ready</strong> — teknisk grund och lokal struktur från start.', '<strong>Managed</strong> — support, säkerhet och förbättringar efter lansering.'], 'sl-checks'),
            'sl-panel sl-panel--dark'
        ),
        'sl-hero-grid',
        '56%'
    ),
    'light'
);
