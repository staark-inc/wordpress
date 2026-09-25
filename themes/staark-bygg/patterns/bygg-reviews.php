<?php
/**
 * Title: Bygg — Omdömen
 * Slug: staark/bygg-reviews
 * Categories: staark-bygg, staark-social-proof
 * Keywords: bygg, omdömen, recensioner, kunder
 * Viewport Width: 1400
 * Description: Sample quotes marked as examples. Replace them with verified customer reviews before publishing.
 */

$staark_bygg_reviews = [
    'Tydlig offert, inga tillägg på slutet och ett badrum som blev precis som vi ritat upp. De städade efter sig varje dag.',
    'Vi fick en kontaktperson som svarade samma dag hela vägen. Tillbyggnaden blev klar en vecka före utlovat datum.',
    'Ordning och reda från platsbesök till slutbesiktning. Vi anlitar dem gärna igen för nästa projekt.',
];
?>
<!-- wp:group {"align":"full","className":"bygg-section bygg-reviews","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull bygg-section bygg-reviews"><!-- wp:group {"align":"wide","className":"bygg-section-head bygg-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide bygg-section-head bygg-section-head--split"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Kundernas ord</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Förtroende byggs ett jobb i taget.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Byt exempeltexterna mot verifierade omdömen från era kunder — gärna med länk till Google eller Reco — innan sidan publiceras.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"bygg-review-grid","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide bygg-review-grid"><?php foreach ($staark_bygg_reviews as $staark_bygg_review) : ?><!-- wp:quote {"className":"bygg-review"} -->
<blockquote class="wp-block-quote bygg-review"><!-- wp:paragraph -->
<p><?php echo esc_html($staark_bygg_review); ?></p>
<!-- /wp:paragraph --><cite>Exempelomdöme · ersätt före publicering</cite></blockquote>
<!-- /wp:quote -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
