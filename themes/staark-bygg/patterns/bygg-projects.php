<?php
/**
 * Title: Bygg — Projekt
 * Slug: staark/bygg-projects
 * Categories: staark-bygg, staark-social-proof
 * Keywords: bygg, projekt, referenser, portfolio
 * Viewport Width: 1400
 * Description: Example projects. Replace images, titles and facts with the company's real references before publishing.
 */

$staark_bygg_projects = [
    ['project-3', 'Tillbyggnad', 'Tillbyggnad med nytt allrum', '25 m² med stora glaspartier mot trädgården, från bygglov till nyckelfärdigt.', '10 veckor · Fast pris'],
    ['project-2', 'Badrum', 'Badrum från stomme', 'Nytt tätskikt, golvvärme och kakel i storformat i ett 60-talshus.', '4 veckor · ROT-avdrag'],
    ['project-1', 'Ombyggnad', 'Öppen planlösning', 'Bärande vägg ersatt med balk, nytt golv och kök i samma rum.', '6 veckor · Fast pris'],
    ['project-5', 'Altan', 'Altan i tryckimpregnerat', '40 m² trädäck i två nivåer med inbyggd trappa och belysning.', '2 veckor · ROT-avdrag'],
    ['project-4', 'Tak', 'Takbyte på villa', 'Nytt underlagstak, läkt och betongpannor inklusive plåtarbeten.', '3 veckor · Fast pris'],
    ['project-6', 'Fasad', 'Fasad och fönster', 'Ny stående panel, tilläggsisolering och fyra nya fönsterpartier.', '5 veckor · ROT-avdrag'],
];
?>
<!-- wp:group {"align":"full","anchor":"projekt","className":"bygg-section bygg-projects","layout":{"type":"constrained"}} -->
<div id="projekt" class="wp-block-group alignfull bygg-section bygg-projects"><!-- wp:group {"align":"wide","className":"bygg-section-head bygg-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide bygg-section-head bygg-section-head--split"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Utvalda projekt</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Jobb vi är stolta över.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Ett urval av våra senaste uppdrag. Varje projekt har haft fast pris, en egen kontaktperson och en slutbesiktning innan överlämning.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"bygg-project-grid","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide bygg-project-grid"><?php foreach ($staark_bygg_projects as $staark_bygg_project) : ?><!-- wp:group {"className":"bygg-project","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-project"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"bygg-project-image"} -->
<figure class="wp-block-image size-full bygg-project-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/' . $staark_bygg_project[0] . '.webp')); ?>" alt="<?php echo esc_attr(sprintf(__('%s – ersätt med en bild från projektet', 'staark-bygg'), $staark_bygg_project[2])); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"bygg-project-body","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-project-body"><!-- wp:paragraph {"className":"bygg-project-tag"} -->
<p class="bygg-project-tag"><?php echo esc_html($staark_bygg_project[1]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"bygg-project-title"} -->
<h3 class="wp-block-heading bygg-project-title"><?php echo esc_html($staark_bygg_project[2]); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-project-text"} -->
<p class="bygg-project-text"><?php echo esc_html($staark_bygg_project[3]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-project-meta"} -->
<p class="bygg-project-meta"><?php echo esc_html($staark_bygg_project[4]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
