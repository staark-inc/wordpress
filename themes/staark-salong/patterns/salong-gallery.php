<?php
/**
 * Title: Salong — Galleri
 * Slug: staark/salong-gallery
 * Categories: staark-salong, staark-sections
 * Keywords: frisör, galleri, bilder, instagram
 * Viewport Width: 1400
 */

$staark_salong_gallery_alt = __('Galleribild – ersätt med en bild på ert arbete', 'staark-salong');
?>
<!-- wp:group {"align":"full","anchor":"galleri","className":"salong-section salong-gallery-section","layout":{"type":"constrained"}} -->
<div id="galleri" class="wp-block-group alignfull salong-section salong-gallery-section"><!-- wp:group {"align":"wide","className":"salong-section-head salong-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide salong-section-head salong-section-head--split"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow">Galleri</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"salong-title"} -->
<h2 class="wp-block-heading salong-title">Senaste från stolen.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-intro"} -->
<p class="salong-intro">Ett urval av färger, klippningar och uppsättningar. Klicka på en bild för att se den större — fler bilder hittar du på vår Instagram.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:gallery {"columns":3,"linkTo":"none","sizeSlug":"full","align":"wide","className":"salong-gallery"} -->
<figure class="wp-block-gallery alignwide has-nested-images columns-3 is-cropped salong-gallery"><?php for ($staark_salong_i = 1; $staark_salong_i <= 6; $staark_salong_i++) : ?><!-- wp:image {"sizeSlug":"full","linkDestination":"none","lightbox":{"enabled":true}} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/gallery-' . $staark_salong_i . '.webp')); ?>" alt="<?php echo esc_attr($staark_salong_gallery_alt); ?>"/></figure>
<!-- /wp:image -->

<?php endfor; ?></figure>
<!-- /wp:gallery -->

<!-- wp:buttons {"align":"wide","className":"salong-gallery-actions","layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons alignwide salong-gallery-actions"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.instagram.com/" target="_blank" rel="noreferrer noopener">Följ oss på Instagram</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
