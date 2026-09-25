<?php
/**
 * Title: Business — Process
 * Slug: staark/process-three
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'process',
    staark_sl_head('Process', 'Från första samtalet till en sida som är redo att jobba.', 'Tydliga steg, rak kommunikation och inga mysterier bakom kulisserna.')
    . staark_sl_steps([
        ['Förstå', 'Vi går igenom mål, kunder, innehåll och vad webbplatsen faktiskt behöver åstadkomma.'],
        ['Forma &amp; bygga', 'Budskap, design och teknik byggs ihop till en snabb, tydlig och lättskött helhet.'],
        ['Lansera &amp; förvalta', 'Vi kvalitetssäkrar, lanserar och fortsätter hjälpa till med support, säkerhet och förbättringar.'],
    ], 'sl-steps--row alignwide'),
    'surface',
    'process'
);
