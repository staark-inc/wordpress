<?php
/**
 * Title: Bygg — Vanliga frågor
 * Slug: staark/bygg-faq
 * Categories: staark-bygg, staark-sections
 * Keywords: bygg, faq, frågor, rot-avdrag, bygglov
 * Viewport Width: 1400
 */

$staark_bygg_faq = [
    ['Hur fungerar ROT-avdraget?', 'Du betalar bara arbetskostnaden minus ROT-avdraget. Vi drar av det direkt på fakturan och ansöker om resten hos Skatteverket. Aktuell procentsats och maxbelopp per person hittar du hos Skatteverket.'],
    ['Kan vi få fast pris?', 'Ja. Efter platsbesöket får du en skriftlig offert med fast pris för det som ingår. Ändringar och tillval prisas separat och godkänns av dig innan vi utför dem.'],
    ['Hur snabbt kan ni börja?', 'Det beror på säsong och projektets storlek. I offerten anger vi ett startdatum och en tidsplan som vi stämmer av med dig veckovis.'],
    ['Behöver jag bygglov?', 'Tillbyggnader, attefallshus och fasadändringar kan kräva bygglov eller anmälan. Vi hjälper dig att kontrollera vad som gäller i din kommun och tar fram underlaget.'],
    ['Är ni försäkrade och vad gäller för garanti?', 'Vi har ansvarsförsäkring och är godkända för F-skatt. Jobbet omfattas av garanti enligt avtalet, för privatpersoner normalt ABS 18.'],
];
?>
<!-- wp:group {"align":"full","anchor":"fragor","className":"bygg-section bygg-faq","layout":{"type":"constrained"}} -->
<div id="fragor" class="wp-block-group alignfull bygg-section bygg-faq"><!-- wp:columns {"align":"wide","className":"bygg-faq-grid"} -->
<div class="wp-block-columns alignwide bygg-faq-grid"><!-- wp:column {"width":"38%","className":"bygg-faq-head"} -->
<div class="wp-block-column bygg-faq-head" style="flex-basis:38%"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Vanliga frågor</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Bra att veta innan du bygger.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Hittar du inte svaret? Skicka frågan tillsammans med din offertförfrågan så svarar vi personligen.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"62%","className":"bygg-faq-list"} -->
<div class="wp-block-column bygg-faq-list" style="flex-basis:62%"><?php foreach ($staark_bygg_faq as $staark_bygg_qa) : ?><!-- wp:details {"className":"bygg-faq-item"} -->
<details class="wp-block-details bygg-faq-item"><summary><?php echo esc_html($staark_bygg_qa[0]); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html($staark_bygg_qa[1]); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<?php endforeach; ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
