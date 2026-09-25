<?php
/**
 * Title: Bygg — Så jobbar vi
 * Slug: staark/bygg-process
 * Categories: staark-bygg, staark-sections
 * Keywords: bygg, process, arbetsgång, så går det till
 * Viewport Width: 1400
 */

$staark_bygg_steps = [
    ['1', 'Kontakt & platsbesök', 'Vi pratar igenom idén, kommer ut och mäter, och ger råd om material och lösningar.'],
    ['2', 'Offert med fast pris', 'Du får en skriftlig offert med vad som ingår, tidsplan och ROT-avdraget uträknat.'],
    ['3', 'Byggstart & utförande', 'Din kontaktperson stämmer av varje vecka. Arbetsplatsen skyddas och städas dagligen.'],
    ['4', 'Slutbesiktning & garanti', 'Vi går igenom jobbet tillsammans innan överlämning. Garanti enligt avtalet gäller.'],
];
?>
<!-- wp:group {"align":"full","anchor":"sa-jobbar-vi","className":"bygg-section bygg-process","layout":{"type":"constrained"}} -->
<div id="sa-jobbar-vi" class="wp-block-group alignfull bygg-section bygg-process"><!-- wp:group {"align":"wide","className":"bygg-section-head bygg-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide bygg-section-head bygg-section-head--split"><!-- wp:paragraph {"className":"bygg-eyebrow"} -->
<p class="bygg-eyebrow">Så jobbar vi</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bygg-title"} -->
<h2 class="wp-block-heading bygg-title">Från första samtal till nyckeln i handen.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-intro"} -->
<p class="bygg-intro">Inga överraskningar på vägen. Du vet alltid vad som händer nu, vad som händer härnäst och vem du ringer om något dyker upp.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"bygg-step-grid","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"13rem"}} -->
<div class="wp-block-group alignwide bygg-step-grid"><?php foreach ($staark_bygg_steps as $staark_bygg_step) : ?><!-- wp:group {"className":"bygg-step","layout":{"type":"default"}} -->
<div class="wp-block-group bygg-step"><!-- wp:paragraph {"className":"bygg-step-num"} -->
<p class="bygg-step-num"><?php echo esc_html($staark_bygg_step[0]); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"bygg-step-title"} -->
<h3 class="wp-block-heading bygg-step-title"><?php echo esc_html($staark_bygg_step[1]); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bygg-step-text"} -->
<p class="bygg-step-text"><?php echo esc_html($staark_bygg_step[2]); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
