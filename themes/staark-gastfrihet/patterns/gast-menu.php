<?php
/**
 * Title: Restaurang — Meny
 * Slug: staark/gast-menu
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, meny, à la carte, priser, rätter
 * Viewport Width: 1400
 * Description: À la carte menu with categories, dishes, descriptions, prices and diet tags (V = vegetarisk, VG = vegansk, G = glutenfri, L = laktosfri).
 */

$staark_gast_menu = [
    'Förrätter' => [
        ['Toast Skagen', 'Handskalade räkor, pepparrot, dill och löjrom på smörstekt brioche.', '165 kr', ''],
        ['Rödbetor från Ekdala', 'Bakade rödbetor, getost, rostade hasselnötter och brynt smör.', '135 kr', 'V · G'],
        ['Soppa på rotselleri', 'Äppelsalsa, krutonger och brynt smör.', '115 kr', 'V'],
    ],
    'Varmrätter' => [
        ['Långkokt högrev', 'Rödvinssky, rostad jordärtskocka, syltad lök och potatispuré.', '295 kr', 'G'],
        ['Smörstekt torsk', 'Beurre blanc på lokal cider, dill och krispig potatis.', '285 kr', 'G'],
        ['Svamprisotto', 'Karl Johan, lagrad ost, persiljeolja och rostade frön.', '235 kr', 'V · G'],
        ['Veckans vegetariska', 'Fråga personalen – alltid säsongens bästa grönsaker.', '225 kr', 'VG'],
    ],
    'Desserter' => [
        ['Mörk choklad &amp; körsbär', 'Chokladmousse, körsbärskompott och vaniljgrädde.', '135 kr', ''],
        ['Brynt smörglass', 'Hjortronsylt och havresmuldeg.', '115 kr', 'V'],
        ['Ostar från trakten', 'Tre ostar, marmelad och knäckebröd.', '145 kr', 'V'],
    ],
    'Barn' => [
        ['Köttbullar', 'Potatismos, gräddsås, lingon och pressgurka.', '125 kr', ''],
        ['Pasta med tomatsås', 'Riven ost och basilika.', '95 kr', 'V'],
    ],
];

$staark_gast_cats = '';
foreach ($staark_gast_menu as $staark_gast_cat => $staark_gast_items) {
    $staark_gast_rows = '';
    foreach ($staark_gast_items as $staark_gast_item) {
        $staark_gast_tags = $staark_gast_item[3] !== '' ? ' <span class="gast-menu-tags">' . esc_html($staark_gast_item[3]) . '</span>' : '';
        $staark_gast_rows .= staark_gast_group(
            staark_gast_p('<span class="gast-menu-name">' . $staark_gast_item[0] . $staark_gast_tags . '</span><span class="gast-menu-price">' . esc_html($staark_gast_item[2]) . '</span>', 'gast-menu-row')
            . staark_gast_p(esc_html($staark_gast_item[1]), 'gast-menu-desc'),
            'gast-menu-item'
        );
    }

    $staark_gast_cats .= staark_gast_group(
        staark_gast_h(esc_html($staark_gast_cat), 3, 'gast-menu-cat-title') . $staark_gast_rows,
        'gast-menu-cat'
    );
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'menu',
    staark_gast_head('À la carte', 'Kvällens meny.', 'Serveras tisdag–lördag från 17:00. Berätta om allergier när du bokar eller för personalen så anpassar vi rätten.')
    . staark_gast_grid($staark_gast_cats, 2, 'gast-menu-grid')
    . staark_gast_p('<strong>V</strong> vegetarisk · <strong>VG</strong> vegansk · <strong>G</strong> glutenfri · <strong>L</strong> laktosfri. Priserna inkluderar moms. Byt exempelrätterna mot er egen meny.', 'gast-menu-note'),
    'meny'
);
