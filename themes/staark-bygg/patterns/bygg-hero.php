<?php
/**
 * Title: Bygg — Hero
 * Slug: staark/bygg-hero
 * Categories: staark-bygg, staark-heroes
 * Keywords: bygg, hantverkare, snickare, hero, offert
 * Viewport Width: 1400
 */

$staark_bygg_proof = [
    ['Fast pris', 'Du vet totalkostnaden innan vi börjar.'],
    ['Egen kontaktperson', 'En person som svarar genom hela projektet.'],
    ['Avtal enligt ABS 18', 'Tydliga villkor, tidsplan och garanti.'],
    ['Ordning på bygget', 'Skyddat, städat och avstämt varje dag.'],
];
?>
<!-- wp:group {"align":"full","className":"bygg-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull bygg-hero"><!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"bygg-hero-grid"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center bygg-hero-grid"><!-- wp:column {"verticalAlignment":"center","width":"56%","className":"bygg-hero-copy"} -->
<div class="wp-block-column is-vertically-aligned-center bygg-hero-copy" style="flex-basis:56%"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Bygg &amp; hantverk · Kostnadsfri offert</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"bygg-display"} -->
<h1 class="wp-block-heading bygg-display">Vi bygger det du har tänkt dig — och håller tidsplanen.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-lead"} -->
<p class="bygg-lead">Renovering, tillbyggnad, kök och badrum för villaägare och bostadsrättsföreningar. Fast pris, egen kontaktperson och ROT-avdraget dras direkt på fakturan.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"bygg-actions"} -->
<div class="wp-block-buttons bygg-actions"><!-- wp:button {"className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="/offert/">Begär kostnadsfri offert</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/projekt/">Se våra projekt</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:list {"className":"bygg-checks"} -->
<ul class="wp-block-list bygg-checks"><!-- wp:list-item -->
<li>Godkänd för F-skatt</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Ansvarsförsäkring</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>ROT-avdrag på fakturan</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"44%","className":"bygg-hero-media"} -->
<div class="wp-block-column is-vertically-aligned-center bygg-hero-media" style="flex-basis:44%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"bygg-hero-image"} -->
<figure class="wp-block-image size-full bygg-hero-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/hero.webp')); ?>" alt="<?php echo esc_attr__('Ritning av ett hus – ersätt med en bild från ett av era projekt', 'staark-bygg'); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"bygg-hero-card","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-hero-card"><!-- wp:paragraph {"className":"bygg-hero-card-label"} -->
<p class="bygg-hero-card-label">Så får du din offert</p>
<!-- /wp:paragraph -->

<!-- wp:list {"ordered":true,"className":"bygg-hero-steps"} -->
<ol class="wp-block-list bygg-hero-steps"><!-- wp:list-item -->
<li><strong>Beskriv jobbet</strong> i formuläret eller per telefon</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Platsbesök</strong> – vi mäter och går igenom önskemål</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Fast pris</strong> med tidsplan, inom en vecka</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","className":"bygg-proof","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"12rem"}} -->
<div class="wp-block-group alignwide bygg-proof"><?php foreach ($staark_bygg_proof as $staark_bygg_item) : ?><!-- wp:group {"className":"bygg-proof-item","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-proof-item"><!-- wp:paragraph {"className":"bygg-proof-title"} -->
<p class="bygg-proof-title"><?php echo esc_html($staark_bygg_item[0]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-proof-text"} -->
<p class="bygg-proof-text"><?php echo esc_html($staark_bygg_item[1]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
