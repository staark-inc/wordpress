<?php
/**
 * Title: Salong — Hero
 * Slug: staark/salong-hero
 * Categories: staark-salong, staark-heroes
 * Keywords: frisör, salong, hero, boka
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"align":"full","className":"salong-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull salong-hero"><!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"salong-hero-grid"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center salong-hero-grid"><!-- wp:column {"verticalAlignment":"center","width":"55%","className":"salong-hero-copy"} -->
<div class="wp-block-column is-vertically-aligned-center salong-hero-copy" style="flex-basis:55%"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow"><?php echo esc_html_x('Frisörsalong · Klippning, färg & vård', 'hero eyebrow', 'staark-salong'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"salong-display"} -->
<h1 class="wp-block-heading salong-display">Hår som känns som <em>du</em> — bara lite bättre.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-lead"} -->
<p class="salong-lead">Vi tar oss tid att lyssna, ger ärliga råd och klipper för hur ditt hår faktiskt beter sig i vardagen. Boka online när det passar dig.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"salong-actions"} -->
<div class="wp-block-buttons salong-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#boka">Boka tid</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#priser">Se prislistan</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:list {"className":"salong-hero-facts"} -->
<ul class="wp-block-list salong-hero-facts"><!-- wp:list-item -->
<li><strong>Konsultation</strong> ingår i varje besök</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Professionella</strong> produkter</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Tydliga priser</strong> innan vi börjar</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%","className":"salong-hero-media"} -->
<div class="wp-block-column is-vertically-aligned-center salong-hero-media" style="flex-basis:45%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"salong-hero-image"} -->
<figure class="wp-block-image size-full salong-hero-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/hero.webp')); ?>" alt="<?php echo esc_attr__('Stämningsbild från salongen – ersätt med egen bild', 'staark-salong'); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"salong-hero-badge"} -->
<p class="salong-hero-badge"><strong>Boka online</strong>Förfrågan dygnet runt</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
