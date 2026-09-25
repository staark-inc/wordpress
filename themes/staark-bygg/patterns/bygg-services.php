<?php
/**
 * Title: Bygg — Tjänster
 * Slug: staark/bygg-services
 * Categories: staark-bygg, staark-sections
 * Keywords: bygg, tjänster, renovering, badrum, kök, tillbyggnad
 * Viewport Width: 1400
 */

$staark_bygg_services = [
    ['01', 'Renovering & ombyggnad', 'Från en uppfräschning till en total ombyggnad där planlösningen ändras.', ['Rivning och bortforsling', 'Väggar, golv och innertak', 'Samordning av el och VVS']],
    ['02', 'Kök', 'Nytt kök med stomme, el, ventilation och montage — ett projekt, en kontakt.', ['Montering av stomme och luckor', 'Bänkskivor och stänkskydd', 'Anpassning av el och fläkt']],
    ['03', 'Badrum & våtrum', 'Tätskikt och våtrumsarbete enligt gällande branschregler, med dokumentation.', ['Rivning till stomme', 'Tätskikt, kakel och klinker', 'Dokumentation till försäkringsbolag']],
    ['04', 'Tillbyggnad & attefall', 'Mer yta utan att flytta — vi hjälper även till med bygglov och ritningar.', ['Grund, stomme och tak', 'Bygglov och anmälan', 'Nyckelfärdig leverans']],
    ['05', 'Tak & fasad', 'Takbyte, fasadrenovering och tilläggsisolering som skyddar huset i många år.', ['Takbyte och takomläggning', 'Fasadpanel och målning', 'Fönster- och dörrbyten']],
    ['06', 'Altan & snickeri', 'Altaner, trädäck, förråd och snickeri inomhus, byggt för svenskt klimat.', ['Altaner och trädäck', 'Förråd och carport', 'Trappor och inredning']],
];
?>
<!-- wp:group {"align":"full","anchor":"tjanster","className":"bygg-section bygg-services","layout":{"type":"constrained"}} -->
<div id="tjanster" class="wp-block-group alignfull bygg-section bygg-services"><!-- wp:group {"align":"wide","className":"bygg-section-head bygg-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide bygg-section-head bygg-section-head--split"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Våra tjänster</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Ett byggföretag för hela huset.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Vi tar hand om hela kedjan — från rivning och bygglov till slutstädning — och samordnar el, VVS och andra yrkesgrupper åt dig.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"bygg-service-grid","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide bygg-service-grid"><?php foreach ($staark_bygg_services as $staark_bygg_service) : ?><!-- wp:group {"className":"bygg-card","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-card"><!-- wp:paragraph {"className":"bygg-card-num"} -->
<p class="bygg-card-num"><?php echo esc_html($staark_bygg_service[0]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"bygg-card-title"} -->
<h3 class="wp-block-heading bygg-card-title"><?php echo esc_html($staark_bygg_service[1]); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-card-text"} -->
<p class="bygg-card-text"><?php echo esc_html($staark_bygg_service[2]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"bygg-card-list"} -->
<ul class="wp-block-list bygg-card-list"><?php foreach ($staark_bygg_service[3] as $staark_bygg_point) : ?><!-- wp:list-item -->
<li><?php echo esc_html($staark_bygg_point); ?></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
