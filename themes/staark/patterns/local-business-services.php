<?php
/**
 * Title: Local Business — Services
 * Slug: staark/local-business-services
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'services',
    staark_sl_head('Våra tjänster', 'Praktisk hjälp, utan krångel.', 'Byt rubrikerna nedan mot företagets viktigaste tjänster. Strukturen är byggd för att kunden snabbt ska förstå vad ni gör och hur man går vidare.')
    . staark_sl_grid(
        staark_sl_card(['num' => '01', 'title' => 'Huvudtjänst', 'text' => 'Beskriv den vanligaste tjänsten kort och konkret, med fokus på kundens behov och resultatet.'])
        . staark_sl_card(['num' => '02', 'title' => 'Service &amp; underhåll', 'text' => 'En återkommande tjänst passar bra här — exempelvis underhåll, felsökning eller löpande hjälp.'])
        . staark_sl_card(['num' => '03', 'title' => 'Projekt &amp; offert', 'text' => 'För större jobb: förklara hur kunden får en bedömning, offert och tydlig plan innan start.']),
        3
    ),
    'surface',
    'tjanster'
);
