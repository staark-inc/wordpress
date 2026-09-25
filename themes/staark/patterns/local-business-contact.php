<?php
/**
 * Title: Local Business — Contact
 * Slug: staark/local-business-contact
 * Categories: staark, staark-conversion
 * Inserter: yes
 */
?>
<!-- wp:group {"align":"full","className":"staark-local-section staark-local-contact","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull staark-local-section staark-local-contact">
  <div class="staark-local-shell staark-local-contact-grid">
    <!-- wp:html -->
    <div class="staark-local-contact-copy">
      <p class="staark-local-kicker">Kontakta oss</p>
      <h2>Beskriv jobbet. Vi återkommer med nästa steg.</h2>
      <p>Formuläret är kopplat till Staark Forms och sparar förfrågan lokalt även om e-postleveransen tillfälligt skulle misslyckas.</p>
      <div class="staark-local-contact-facts">
        <div class="staark-local-contact-fact"><strong>Återkoppling</strong><span>Normalt inom en arbetsdag</span></div>
        <div class="staark-local-contact-fact"><strong>Offert</strong><span>Tydligt upplägg innan start</span></div>
        <div class="staark-local-contact-fact"><strong>Område</strong><span>Anpassa till företagets ort och serviceområde</span></div>
      </div>
    </div>
    <!-- /wp:html -->

    <!-- wp:group {"className":"staark-local-form-card","layout":{"type":"constrained"}} -->
    <div class="wp-block-group staark-local-form-card">
      <!-- wp:shortcode -->
      [staark_contact_form form_id="local-business-contact" button="Skicka förfrågan"]
      <!-- /wp:shortcode -->
    </div>
    <!-- /wp:group -->
  </div>
</div>
<!-- /wp:group -->
