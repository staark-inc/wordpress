<?php
/**
 * Title: Gästfrihet — Vanliga frågor
 * Slug: staark/gast-faq
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: faq, frågor, bokning, avbokning, allergier, incheckning
 * Viewport Width: 1400
 * Description: Frequently asked questions. Questions follow the active preset.
 */

$staark_gast_faq = staark_gast_pick(
    [
        ['Hur bokar jag bord?', 'Boka online via bokningslänken eller skicka en bokningsförfrågan här på sidan. Förfrågningar bekräftas av oss per e-post eller sms – bokningen gäller först när du fått bekräftelsen.'],
        ['Kan ni hantera allergier och specialkost?', 'Ja. Skriv allergier och önskemål i bokningen och berätta för personalen när du kommer, så anpassar vi rätterna.'],
        ['Hur avbokar jag?', 'Avboka senast 24 timmar innan genom att svara på bekräftelsen eller ringa oss. För sällskap gäller särskilda villkor.'],
        ['Tar ni emot walk-in?', 'Ja, i mån av plats. Lunch serveras utan bokning, men till middag rekommenderar vi att boka.'],
        ['Kan vi boka för ett större sällskap?', 'Absolut. För fler än 8 personer: välj "Fler än 10" i formuläret eller läs mer under Sällskap &amp; event.'],
    ],
    [
        ['När kan jag checka in och ut?', 'Incheckning från 15:00 och utcheckning senast 11:00. Tidig incheckning och sen utcheckning ordnar vi gärna i mån av plats.'],
        ['Hur fungerar en bokningsförfrågan?', 'Du skickar önskade datum och rum – vi återkommer med bekräftelse och pris per e-post. Vill du boka direkt och få besked på en gång, använd bokningslänken.'],
        ['Vad gäller vid avbokning?', 'Kostnadsfri avbokning fram till 18:00 dagen före ankomst, om inget annat anges för paket eller grupper.'],
        ['Ingår frukost?', 'Ja, frukostbuffén ingår i alla rumspriser och serveras 07:00–10:00 (helger till 11:00).'],
        ['Finns parkering?', 'Ja, egen parkering med laddplatser för elbil. Meddela gärna registreringsnummer vid incheckning.'],
    ]
);

$staark_gast_items = '';
foreach ($staark_gast_faq as $staark_gast_qa) {
    $staark_gast_items .= staark_gast_details(esc_html($staark_gast_qa[0]), $staark_gast_qa[1]);
}

$staark_gast_head = staark_gast_p('Vanliga frågor', 'gast-eyebrow')
    . staark_gast_h(staark_gast_pick('Bra att veta innan du kommer.', 'Frågor inför din vistelse.'), 2, 'gast-title')
    . staark_gast_p('Hittar du inte svaret? Ring eller mejla oss – vi svarar gärna.', 'gast-intro');

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'faq',
    staark_gast_columns($staark_gast_head, $staark_gast_items, 'gast-faq-grid', '38%', false, 'gast-faq-head', 'gast-faq-list'),
    'fragor'
);
