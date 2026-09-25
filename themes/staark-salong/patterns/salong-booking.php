<?php
/**
 * Title: Salong — Boka tid
 * Slug: staark/salong-booking
 * Categories: staark-salong, staark-conversion
 * Keywords: frisör, boka, bokning, tidsbokning, kontakt, öppettider
 * Viewport Width: 1400
 * Description: Booking request section. The hidden "Tjänster i bokningsformuläret" list feeds the service picker in the form.
 */
?>
<!-- wp:group {"align":"full","anchor":"boka","className":"salong-section salong-booking","layout":{"type":"constrained"}} -->
<div id="boka" class="wp-block-group alignfull salong-section salong-booking"><!-- wp:columns {"align":"wide","className":"salong-booking-grid"} -->
<div class="wp-block-columns alignwide salong-booking-grid"><!-- wp:column {"width":"42%","className":"salong-booking-info"} -->
<div class="wp-block-column salong-booking-info" style="flex-basis:42%"><!-- wp:paragraph {"className":"salong-eyebrow"} -->
<p class="salong-eyebrow">Boka tid</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"salong-title"} -->
<h2 class="wp-block-heading salong-title">Välj tjänst och dag — vi bekräftar din tid.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"salong-intro"} -->
<p class="salong-intro">Skicka en bokningsförfrågan så återkommer vi med en bekräftad tid, oftast samma arbetsdag. Har du bråttom? Ring oss direkt.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"salong-hours","layout":{"type":"default"}} -->
<div class="wp-block-group salong-hours"><!-- wp:heading {"level":3,"className":"salong-hours-title"} -->
<h3 class="wp-block-heading salong-hours-title">Öppettider</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"salong-hours-list"} -->
<ul class="wp-block-list salong-hours-list"><!-- wp:list-item -->
<li>Måndag–fredag <strong>10.00–19.00</strong></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Lördag <strong>10.00–16.00</strong></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Söndag <strong>Stängt</strong></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"salong-visit","layout":{"type":"default"}} -->
<div class="wp-block-group salong-visit"><!-- wp:paragraph {"className":"salong-visit-row salong-visit-row--contact"} -->
<p class="salong-visit-row salong-visit-row--contact"><span>Telefon</span> [salong_contact field="phone" link="1" hint="Lägg till telefonnummer i Staark Hub → SEO"]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"salong-visit-row salong-visit-row--contact"} -->
<p class="salong-visit-row salong-visit-row--contact"><span>Adress</span> [salong_contact field="address" link="1" hint="Lägg till adress i Staark Hub → SEO"]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"salong-visit-row"} -->
<p class="salong-visit-row"><span>Avbokning</span> Senast 24 timmar innan, annars debiteras halva priset.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"58%","className":"salong-booking-form"} -->
<div class="wp-block-column salong-booking-form" style="flex-basis:58%"><!-- wp:heading {"level":3,"className":"salong-booking-form-title"} -->
<h3 class="wp-block-heading salong-booking-form-title">Bokningsförfrågan</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"salong-booking-services"} -->
<ul class="wp-block-list salong-booking-services"><!-- wp:list-item -->
<li>Damklippning</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Herrklippning</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Barnklippning</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Färg / utväxt</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Slingor / balayage</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Behandling</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Uppsättning / styling</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Konsultation</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<?php if (shortcode_exists('staark_contact_form')) : ?>
<!-- wp:shortcode -->
[staark_contact_form title="Bokningsförfrågan" button="Skicka bokningsförfrågan" form_id="salong-bokning"]
<!-- /wp:shortcode -->
<?php else : ?>
<!-- wp:paragraph {"className":"salong-booking-fallback"} -->
<p class="salong-booking-fallback">Bokningsformuläret visas när Staark Hub är aktiverat. Fram till dess: ring eller mejla oss så hittar vi en tid. [salong_contact field="email" link="1"]</p>
<!-- /wp:paragraph -->
<?php endif; ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
