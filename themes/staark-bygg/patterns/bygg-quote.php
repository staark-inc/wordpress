<?php
/**
 * Title: Bygg — Begär offert
 * Slug: staark/bygg-quote
 * Categories: staark-bygg, staark-conversion
 * Keywords: bygg, offert, offertförfrågan, kontakt
 * Viewport Width: 1400
 * Description: Quote request section. The hidden "Jobbtyper i offertformuläret" list feeds the job type picker in the form.
 */

$staark_bygg_job_types = ['Renovering / ombyggnad', 'Kök', 'Badrum / våtrum', 'Tillbyggnad / attefall', 'Tak', 'Fasad / fönster', 'Altan / trädäck', 'Snickeri', 'Annat'];
?>
<!-- wp:group {"align":"full","anchor":"offert","className":"bygg-section bygg-quote","layout":{"type":"constrained"}} -->
<div id="offert" class="wp-block-group alignfull bygg-section bygg-quote"><!-- wp:columns {"align":"wide","className":"bygg-quote-grid"} -->
<div class="wp-block-columns alignwide bygg-quote-grid"><!-- wp:column {"width":"42%","className":"bygg-quote-info"} -->
<div class="wp-block-column bygg-quote-info" style="flex-basis:42%"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Begär offert</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Berätta om jobbet — du får ett fast pris.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Offerten är kostnadsfri och utan förpliktelser. Ju mer du berättar, desto snabbare kan vi ge ett tydligt besked.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"ordered":true,"className":"bygg-next-steps"} -->
<ol class="wp-block-list bygg-next-steps"><!-- wp:list-item -->
<li><strong>Vi ringer upp</strong> inom en arbetsdag och ställer några frågor.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Platsbesök</strong> när det passar dig — vi mäter och ger råd.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Skriftlig offert</strong> med fast pris, tidsplan och ROT-avdrag.</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list -->

<!-- wp:group {"className":"bygg-contact-card","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-contact-card"><!-- wp:paragraph {"className":"bygg-contact-row bygg-contact-row--data"} -->
<p class="bygg-contact-row bygg-contact-row--data"><span>Telefon</span> [bygg_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-contact-row bygg-contact-row--data"} -->
<p class="bygg-contact-row bygg-contact-row--data"><span>E-post</span> [bygg_contact field="email" link="1" hint="Lägg till e-post i Staark Hub → SEO"]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-contact-row"} -->
<p class="bygg-contact-row"><span>Bilder</span> Svara gärna på vårt mejl med foton och mått — då blir offerten mer träffsäker.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"58%","className":"bygg-quote-form"} -->
<div class="wp-block-column bygg-quote-form" style="flex-basis:58%"><!-- wp:heading {"level":3,"className":"bygg-quote-form-title"} -->
<h3 class="wp-block-heading bygg-quote-form-title">Offertförfrågan</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"bygg-quote-types"} -->
<ul class="wp-block-list bygg-quote-types"><?php foreach ($staark_bygg_job_types as $staark_bygg_type) : ?><!-- wp:list-item -->
<li><?php echo esc_html($staark_bygg_type); ?></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->

<?php if (shortcode_exists('staark_contact_form')) : ?>
<!-- wp:shortcode -->
[staark_contact_form title="Offertförfrågan" button="Skicka offertförfrågan" form_id="bygg-offert"]
<!-- /wp:shortcode -->
<?php else : ?>
<!-- wp:paragraph {"className":"bygg-quote-fallback"} -->
<p class="bygg-quote-fallback">Offertformuläret visas när Staark Hub är aktiverat. Fram till dess: ring eller mejla oss. [bygg_contact field="email" link="1"]</p>
<!-- /wp:paragraph -->
<?php endif; ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
