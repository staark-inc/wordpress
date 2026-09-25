<?php
/**
 * Title: Hotell — Rum
 * Slug: staark/gast-rooms
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: hotell, rum, dubbelrum, svit, priser
 * Viewport Width: 1400
 * Description: Room types with image, description, features and price per night. Keep the room names in sync with the room list in the booking section.
 */

$staark_gast_rooms = [
    ['assets/images/room-1.webp', 'Enkelrum', 'Tyst och smart planerat för dig som reser i jobbet eller på egen hand.', ['14 m² · 120 cm säng', 'Skrivbord och snabb wifi', 'Dusch och hårtork'], '995 kr'],
    ['assets/images/room-2.webp', 'Dubbelrum', 'Rymligt med kontinentalsäng och utsikt mot parken eller innergården.', ['22 m² · 180 cm säng', 'Smart-TV och kaffebryggare', 'Dusch, morgonrock och tofflor'], '1 395 kr'],
    ['assets/images/room-3.webp', 'Svit', 'Separat sovrum, sittgrupp och badkar – för längre vistelser och speciella tillfällen.', ['38 m² · 180 cm säng', 'Soffgrupp och bäddsoffa', 'Badkar och Nespresso'], '2 295 kr'],
];

$staark_gast_cards = '';
foreach ($staark_gast_rooms as $staark_gast_room) {
    $staark_gast_cards .= staark_gast_group(
        staark_gast_image($staark_gast_room[0], 'Illustration av ' . strtolower($staark_gast_room[1]) . ' – ersätt med en bild av rummet', 'gast-card-media')
        . staark_gast_group(
            staark_gast_h(esc_html($staark_gast_room[1]), 3, 'gast-card-title')
            . staark_gast_p(esc_html($staark_gast_room[2]), 'gast-card-text')
            . staark_gast_list(array_map('esc_html', $staark_gast_room[3]), 'gast-card-list')
            . staark_gast_p('<span>Från</span> ' . esc_html($staark_gast_room[4]) . ' <span>/ natt inkl. frukost</span>', 'gast-card-price gast-card-price--room')
            . staark_gast_p('<a href="/boka/">Boka ' . esc_html(strtolower($staark_gast_room[1])) . ' →</a>', 'gast-card-link'),
            'gast-card-body'
        ),
        'gast-card gast-card--media'
    );
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'rooms',
    staark_gast_head('Rum &amp; sviter', 'Välj ditt rum.', 'Alla rum har egen dusch och toalett, bäddas med hotellkvalitet och städas dagligen. Frukost ingår alltid.')
    . staark_gast_grid($staark_gast_cards, 3, 'gast-card-grid'),
    'rum'
);
