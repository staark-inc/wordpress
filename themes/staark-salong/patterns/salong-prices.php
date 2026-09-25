<?php
/**
 * Title: Salong — Prislista
 * Slug: staark/salong-prices
 * Categories: staark-salong, staark-sections
 * Keywords: frisör, priser, prislista
 * Viewport Width: 1400
 */

$staark_salong_prices = [
    'Klippning' => [
        ['Damklippning, kort hår', 'Tvätt och fön ingår · 45 min', '595 kr'],
        ['Damklippning, långt hår', 'Tvätt och fön ingår · 60 min', '695 kr'],
        ['Herrklippning', 'Inkl. tvätt · 30 min', '450 kr'],
        ['Barnklippning', 'Upp till 12 år · 30 min', '295 kr'],
        ['Skäggtrim', 'Form, kontur och olja · 20 min', '250 kr'],
    ],
    'Färg & slingor' => [
        ['Toning / glossing', 'Glans och nyans · 45 min', '795 kr'],
        ['Färg utväxt', 'Upp till 3 cm · 90 min', 'från 995 kr'],
        ['Helfärg', 'Inkl. klippning · 2 h', 'från 1 395 kr'],
        ['Folieslingor', 'Inkl. toning · 2,5 h', 'från 1 595 kr'],
        ['Balayage', 'Frihandsljusning · 3 h', 'från 1 995 kr'],
    ],
    'Behandlingar' => [
        ['Inpackning', 'Fukt och glans · 20 min', '295 kr'],
        ['Bond-behandling', 'Reparerar kemiskt behandlat hår', '395 kr'],
        ['Keratinbehandling', 'Mindre frizz i upp till 3 månader', 'från 2 495 kr'],
    ],
    'Styling' => [
        ['Tvätt & fön', 'Slätt eller volym · 30 min', '395 kr'],
        ['Uppsättning', 'Fest och examen · 45 min', 'från 895 kr'],
        ['Brudpaket', 'Provuppsättning ingår', 'från 2 995 kr'],
    ],
];
?>
<!-- wp:group {"align":"full","anchor":"priser","className":"salong-section salong-prices","layout":{"type":"constrained"}} -->
<div id="priser" class="wp-block-group alignfull salong-section salong-prices"><!-- wp:group {"align":"wide","className":"salong-section-head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide salong-section-head"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow">Prislista</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"salong-title"} -->
<h2 class="wp-block-heading salong-title">Tydliga priser, inga överraskningar.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-intro"} -->
<p class="salong-intro">Priserna är vägledande och beror på hårets längd och tjocklek. Vi bekräftar alltid slutpriset vid konsultationen innan vi börjar.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"salong-price-grid","layout":{"type":"grid","columnCount":2,"minimumColumnWidth":"20rem"}} -->
<div class="wp-block-group alignwide salong-price-grid"><?php foreach ($staark_salong_prices as $staark_salong_category => $staark_salong_rows) : ?><!-- wp:group {"className":"salong-price-group","layout":{"type":"default"}} -->
<div class="wp-block-group salong-price-group"><!-- wp:heading {"level":3,"className":"salong-price-heading"} -->
<h3 class="wp-block-heading salong-price-heading"><?php echo esc_html($staark_salong_category); ?></h3>
<!-- /wp:heading -->

<?php foreach ($staark_salong_rows as $staark_salong_row) : ?><!-- wp:group {"className":"salong-price-row","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
<div class="wp-block-group salong-price-row"><!-- wp:group {"className":"salong-price-label","layout":{"type":"default"}} -->
<div class="wp-block-group salong-price-label"><!-- wp:paragraph {"className":"salong-price-name"} -->
<p class="salong-price-name"><?php echo esc_html($staark_salong_row[0]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"salong-price-meta"} -->
<p class="salong-price-meta"><?php echo esc_html($staark_salong_row[1]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"salong-price-value"} -->
<p class="salong-price-value"><?php echo esc_html($staark_salong_row[2]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","className":"salong-price-foot"} -->
<p class="has-text-align-center salong-price-foot">Osäker på vad du behöver? <a href="/boka/">Boka en kostnadsfri konsultation →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
