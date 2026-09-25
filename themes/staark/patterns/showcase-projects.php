<?php
/**
 * Title: Showcase — Projects
 * Slug: staark/showcase-projects
 * Categories: staark, staark-social-proof
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'projects',
    staark_sl_head('Utvalda projekt', 'Webbplatser som gör skillnad.', 'Några visuella koncept som visar hur olika företag kan få ett tydligare och mer professionellt uttryck.', true, staark_sl_buttons([['Se alla projekt', '/projekt', 'outline']], 'sl-actions sl-head-actions'))
    . staark_sl_grid(
        staark_sl_card(['image' => 'assets/images/showcase/project-build.webp', 'alt' => 'Koncept för byggföretag', 'num' => 'Byggföretag · WordPress', 'title' => 'Nord Bygg · Koncept'], 'sl-card--project')
        . staark_sl_card(['image' => 'assets/images/showcase/project-salon.webp', 'alt' => 'Koncept för frisörsalong', 'num' => 'Frisörsalong · WordPress', 'title' => 'Studio Alma · Koncept'], 'sl-card--project')
        . staark_sl_card(['image' => 'assets/images/showcase/project-service.webp', 'alt' => 'Koncept för lokalt tjänsteföretag', 'num' => 'Lokalt tjänsteföretag · WordPress', 'title' => 'Rent &amp; Klart · Koncept'], 'sl-card--project'),
        3
    ),
    'dark',
    'projekt'
);
