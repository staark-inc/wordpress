<?php
/**
 * Title: Bygg — Kontakt
 * Slug: staark/bygg-contact
 * Categories: staark-bygg, staark-sections
 * Keywords: bygg, kontakt, telefon, e-post, adress
 * Viewport Width: 1400
 * Description: Direct contact section with phone, email, address and quote CTA.
 */
?>

<!-- wp:group {"align":"full","anchor":"kontakt","className":"bygg-section bygg-contact-page","layout":{"type":"constrained"}} -->
<div id="kontakt" class="wp-block-group alignfull bygg-section bygg-contact-page">

<!-- wp:columns {"align":"wide","verticalAlignment":"center","className":"bygg-quote-grid"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center bygg-quote-grid">

<!-- wp:column {"verticalAlignment":"center","width":"52%","className":"bygg-quote-info"} -->
<div class="wp-block-column is-vertically-aligned-center bygg-quote-info" style="flex-basis:52%">

<!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Kontakt</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Prata med oss om ditt projekt.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Har du frågor, vill boka ett platsbesök eller bara diskutera en idé? Ring eller mejla oss så återkommer vi så snart vi kan.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"bygg-contact-card","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-contact-card">

<!-- wp:paragraph {"className":"bygg-contact-row bygg-contact-row--data"} -->
<p class="bygg-contact-row bygg-contact-row--data">
<span>Telefon</span>
[bygg_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-contact-row bygg-contact-row--data"} -->
<p class="bygg-contact-row bygg-contact-row--data">
<span>E-post</span>
[bygg_contact field="email" link="1" hint="Lägg till e-post i Staark Hub → SEO"]
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bygg-contact-row bygg-contact-row--data"} -->
<p class="bygg-contact-row bygg-contact-row--data">
<span>Adress</span>
[bygg_contact field="address" link="1" hint="Lägg till adress i Staark Hub → SEO"]
</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->


<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%">

<!-- wp:group {"className":"bygg-contact-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group bygg-contact-card">

<!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Behöver du ett pris?</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Begär en kostnadsfri offert.</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Beskriv jobbet så återkommer vi med nästa steg. Offerten är kostnadsfri och utan förpliktelser.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">

<!-- wp:button -->
<div class="wp-block-button">
<a class="wp-block-button__link wp-element-button" href="/offert/">Begär offert</a>
</div>
<!-- /wp:button -->

</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
