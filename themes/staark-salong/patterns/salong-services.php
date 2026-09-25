<?php
/**
 * Title: Salong — Tjänster
 * Slug: staark/salong-services
 * Categories: staark-salong, staark-sections
 * Keywords: frisör, tjänster, klippning, färg
 * Viewport Width: 1400
 */

$staark_salong_services = [
    ['01', 'Klippning', 'Dam, herr och barn. Vi börjar alltid med hur du vill att håret ska kännas — inte bara se ut.', 'Från 295 kr'],
    ['02', 'Färg & slingor', 'Toning, helfärg, folieslingor och balayage i varma, naturliga toner som växer ut snyggt.', 'Från 795 kr'],
    ['03', 'Behandlingar', 'Inpackningar, bond-behandlingar och keratin för hår som behöver återhämtning.', 'Från 295 kr'],
    ['04', 'Uppsättning & styling', 'Fest, examen eller bröllop — med provuppsättning när det behövs.', 'Från 395 kr'],
];
?>
<!-- wp:group {"align":"full","anchor":"tjanster","className":"salong-section salong-services","layout":{"type":"constrained"}} -->
<div id="tjanster" class="wp-block-group alignfull salong-section salong-services"><!-- wp:group {"align":"wide","className":"salong-section-head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide salong-section-head"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow">Tjänster</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"salong-title"} -->
<h2 class="wp-block-heading salong-title">Allt från en snabb putsning till en helt ny look.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-intro"} -->
<p class="salong-intro">Varje besök börjar med en kort konsultation, så att vi är överens om resultat, tid och pris innan vi sätter igång.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"salong-service-grid","layout":{"type":"grid","minimumColumnWidth":"15rem"}} -->
<div class="wp-block-group alignwide salong-service-grid"><?php foreach ($staark_salong_services as $staark_salong_service) : ?><!-- wp:group {"className":"salong-card","layout":{"type":"default"}} -->
<div class="wp-block-group salong-card"><!-- wp:paragraph {"className":"salong-card-num"} -->
<p class="salong-card-num"><?php echo esc_html($staark_salong_service[0]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"salong-card-title"} -->
<h3 class="wp-block-heading salong-card-title"><?php echo esc_html($staark_salong_service[1]); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-card-text"} -->
<p class="salong-card-text"><?php echo esc_html($staark_salong_service[2]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"salong-card-price"} -->
<p class="salong-card-price"><?php echo esc_html($staark_salong_service[3]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
