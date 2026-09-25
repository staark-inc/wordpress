<?php
/**
 * Title: Showcase — Contact
 * Slug: staark/showcase-contact
 * Categories: staark, staark-conversion
 * Inserter: yes
 */
?>
<!-- wp:group {"align":"full","className":"staark-showcase-contact","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull staark-showcase-contact">
  <!-- wp:html -->
  <div class="staark-showcase-wrap staark-showcase-contact-grid">
    <div>
      <p class="staark-showcase-eyebrow">Kontakt</p>
      <h2 class="staark-showcase-title">Berätta vad du vill förbättra.</h2>
      <p class="staark-showcase-copy">Ny webbplats, gammal webbplats eller bara en idé? Skicka några rader så tar vi nästa steg tillsammans.</p>
      <div class="wp-block-buttons staark-showcase-actions"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:contact@staarkinc.com">contact@staarkinc.com →</a></div></div>
    </div>
    <div class="staark-showcase-contact-list">
      <div class="staark-showcase-contact-item"><strong>01 · Kort introduktion</strong><span>Berätta om företaget och vad som inte fungerar idag.</span></div>
      <div class="staark-showcase-contact-item"><strong>02 · Förslag &amp; omfattning</strong><span>Vi föreslår upplägg, funktioner och nästa steg.</span></div>
      <div class="staark-showcase-contact-item"><strong>03 · Tydlig start</strong><span>När allt känns rätt sätter vi plan och produktion i gång.</span></div>
    </div>
  </div>
  <!-- /wp:html -->
  <?php if (shortcode_exists('staark_contact_form')) : ?>
  <!-- wp:group {"className":"staark-showcase-wrap staark-showcase-form","layout":{"type":"constrained"}} -->
  <div class="wp-block-group staark-showcase-wrap staark-showcase-form">
    <!-- wp:shortcode -->
    [staark_contact_form title="Kontakta oss" button="Skicka förfrågan" form_id="staark-home"]
    <!-- /wp:shortcode -->
  </div>
  <!-- /wp:group -->
  <?php endif; ?>
</div>
<!-- /wp:group -->
