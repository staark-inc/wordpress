<?php
/**
 * Title: Business — Why us
 * Slug: staark/why-us-business
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'why',
    staark_sl_head('Varför Staark', 'Vi bygger hellre rätt från början än säljer fler plugins senare.', 'Design, SEO, säkerhet och prestanda hör ihop. Därför behandlar vi dem som delar av samma produkt.')
    . staark_sl_grid(
        staark_sl_card(['num' => 'A', 'title' => 'Enklare stack', 'text' => 'Färre beroenden, mindre underhåll och tydligare ansvar när något behöver ändras.'])
        . staark_sl_card(['num' => 'B', 'title' => 'Byggt för företag', 'text' => 'Tydligt innehåll, lokal synlighet och kontaktvägar som fungerar i verkligheten.'])
        . staark_sl_card(['num' => 'C', 'title' => 'Någon stannar kvar', 'text' => 'Efter lansering finns samma ekosystem kvar för support, säkerhet och förbättringar.']),
        3
    ),
    'dark'
);
