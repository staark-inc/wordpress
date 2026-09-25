<?php
/**
 * Title: Salong — Omdömen
 * Slug: staark/salong-reviews
 * Categories: staark-salong, staark-social-proof
 * Keywords: frisör, omdömen, recensioner
 * Viewport Width: 1400
 * Description: Replace the sample quotes with real customer reviews before publishing.
 */

$staark_salong_reviews = [
    ['Första gången någon frågat hur jag faktiskt stylar håret på morgonen. Klippningen håller formen fortfarande sex veckor senare.', 'Kund · Damklippning'],
    ['Jag ville ljusare men naturligt. Balayagen växer ut så mjukt att jag knappt märker när det är dags igen.', 'Kund · Balayage'],
    ['Lugnt, personligt och ett tydligt pris innan de började. Precis så som ett frisörbesök ska kännas.', 'Kund · Färg & klippning'],
];
?>
<!-- wp:group {"align":"full","className":"salong-section salong-reviews","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull salong-section salong-reviews"><!-- wp:group {"align":"wide","className":"salong-section-head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide salong-section-head"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow">Omdömen</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"salong-title"} -->
<h2 class="wp-block-heading salong-title">Det bästa kvittot är när kunderna kommer tillbaka.</h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"salong-review-grid","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"17rem"}} -->
<div class="wp-block-group alignwide salong-review-grid"><?php foreach ($staark_salong_reviews as $staark_salong_review) : ?><!-- wp:quote {"className":"salong-review"} -->
<blockquote class="wp-block-quote salong-review"><!-- wp:paragraph -->
<p><?php echo esc_html($staark_salong_review[0]); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html($staark_salong_review[1]); ?></cite></blockquote>
<!-- /wp:quote -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
