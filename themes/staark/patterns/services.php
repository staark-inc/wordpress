<?php
/**
 * Title: Business — Services
 * Slug: staark/services-three
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'services',
    staark_sl_head('Tjänster', 'Det viktigaste, gjort ordentligt.', 'Ingen onödig plugin-stapel. Ingen design för designens skull. Bara en stark digital grund som hjälper företaget framåt.')
    . staark_sl_grid(
        staark_sl_card(['num' => '01', 'title' => 'Webbdesign &amp; utveckling', 'text' => 'Tydlig struktur, skarp typografi och ett gränssnitt som känns genomtänkt på mobil, surfplatta och dator.', 'foot' => '<a href="/kontakt">Utforska tjänsten →</a>'])
        . staark_sl_card(['num' => '02', 'title' => 'SEO &amp; prestanda', 'text' => 'Lokal synlighet, teknisk SEO och snabb laddning byggs in i grunden — inte som en eftertanke.', 'foot' => '<a href="/kontakt">Utforska tjänsten →</a>'])
        . staark_sl_card(['num' => '03', 'title' => 'Support &amp; förvaltning', 'text' => 'Uppdateringar, säkerhet och förbättringar efter lansering så att sidan fortsätter vara en tillgång.', 'foot' => '<a href="/kontakt">Prata med oss →</a>']),
        3
    ),
    'light',
    'tjanster'
);
