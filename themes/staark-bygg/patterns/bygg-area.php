<?php
/**
 * Title: Bygg — Arbetsområde
 * Slug: staark/bygg-area
 * Categories: staark-bygg, staark-sections
 * Keywords: bygg, arbetsområde, ort, område, lokal
 * Viewport Width: 1400
 * Description: Service area. Edit the list of towns to match where the company takes jobs.
 */

$staark_bygg_towns = ['Värnamo', 'Gislaved', 'Gnosjö', 'Vaggeryd', 'Skillingaryd', 'Smålandsstenar', 'Ljungby', 'Jönköping'];
?>
<!-- wp:group {"align":"full","anchor":"omrade","className":"bygg-section bygg-area","layout":{"type":"constrained"}} -->
<div id="omrade" class="wp-block-group alignfull bygg-section bygg-area"><!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"bygg-area-grid"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center bygg-area-grid"><!-- wp:column {"verticalAlignment":"center","width":"50%","className":"bygg-area-copy"} -->
<div class="wp-block-column is-vertically-aligned-center bygg-area-copy" style="flex-basis:50%"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Arbetsområde</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Lokalt byggföretag — nära dig.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Vi utgår från [bygg_contact field="city" fallback="vår ort"] och tar uppdrag inom ungefär fem mil. Korta resor betyder att vi är på plats när det behövs, även efter att jobbet är klart.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"bygg-towns"} -->
<ul class="wp-block-list bygg-towns"><?php foreach ($staark_bygg_towns as $staark_bygg_town) : ?><!-- wp:list-item -->
<li><?php echo esc_html($staark_bygg_town); ?></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"className":"bygg-area-note"} -->
<p class="bygg-area-note">Bor du utanför området? <a href="/offert/">Hör av dig ändå</a> — för större projekt reser vi längre.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"bygg-area-media"} -->
<div class="wp-block-column is-vertically-aligned-center bygg-area-media" style="flex-basis:50%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"bygg-area-image"} -->
<figure class="wp-block-image size-full bygg-area-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/area.webp')); ?>" alt="<?php echo esc_attr__('Karta över arbetsområdet – ersätt med en karta över er region', 'staark-bygg'); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
