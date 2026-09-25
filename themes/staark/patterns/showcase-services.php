<?php
/**
 * Title: Showcase — Services
 * Slug: staark/showcase-services
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'services',
    staark_sl_head('Våra tjänster', 'Allt du behöver för en framgångsrik webbplats.', 'Vi kombinerar modern design, WordPress och smarta verktyg för att hjälpa ditt företag att växa online.')
    . staark_sl_grid(
        staark_sl_card(['image' => 'assets/images/showcase/service-design.webp', 'alt' => 'Webbdesign på laptop', 'title' => 'Webbdesign &amp; utveckling', 'text' => 'Skräddarsydda WordPress-webbplatser som är snabba, snygga och enkla att uppdatera.', 'foot' => '<a href="/tjanster">Läs mer →</a>'])
        . staark_sl_card(['image' => 'assets/images/showcase/service-seo.webp', 'alt' => 'SEO och analys på mobil', 'title' => 'SEO &amp; synlighet', 'text' => 'En teknisk och innehållsmässig grund som hjälper rätt kunder att hitta ditt företag.', 'foot' => '<a href="/tjanster">Läs mer →</a>'])
        . staark_sl_card(['image' => 'assets/images/showcase/service-support.webp', 'alt' => 'Prestanda och förvaltning i dashboard', 'title' => 'Support &amp; förvaltning', 'text' => 'Uppdateringar, säkerhet och prestanda samlat i en enklare förvaltning efter lansering.', 'foot' => '<a href="/tjanster">Läs mer →</a>']),
        3
    ),
    'light',
    'tjanster'
);
