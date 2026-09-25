<?php
/**
 * Title: Gästfrihet — Galleri
 * Slug: staark/gast-gallery
 * Categories: staark-gastfrihet, staark-sections
 * Keywords: restaurang, hotell, galleri, bilder, miljö
 * Viewport Width: 1400
 * Description: Six-image gallery. Replace the illustrations with your own photos.
 */

$staark_gast_images = staark_gast_pick(
    ['dish-4', 'rest-events', 'dish-2', 'rest-about', 'dish-5', 'dish-1'],
    ['room-2', 'hotel-spa', 'hotel-breakfast', 'hotel-about', 'room-3', 'room-1']
);

$staark_gast_items = '';
foreach ($staark_gast_images as $staark_gast_image) {
    $staark_gast_items .= staark_gast_image('assets/images/' . $staark_gast_image . '.webp', 'Illustration – ersätt med ett eget foto', 'gast-gallery-item');
}

echo staark_gast_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'gallery',
    staark_gast_head(
        'Galleri',
        staark_gast_pick('Från köket och matsalen.', 'Rum, frukost och stillhet.'),
        staark_gast_pick('Ljuset, borden och rätterna – så här ser en kväll hos oss ut.', 'Ta en titt innan du kommer. Följ oss gärna i sociala medier för fler bilder.')
    )
    . staark_gast_grid($staark_gast_items, 3, 'gast-gallery-grid'),
    'galleri'
);
