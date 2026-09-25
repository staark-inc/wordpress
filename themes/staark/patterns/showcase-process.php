<?php
/**
 * Title: Showcase — Process
 * Slug: staark/showcase-process
 * Categories: staark, staark-sections
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'process',
    staark_sl_split(
        staark_sl_p('Så fungerar det', 'sl-eyebrow')
        . staark_sl_h('Från idé till färdig webbplats.', 2, 'sl-title')
        . staark_sl_steps([
            ['Vi pratar om dina mål', 'Vi lär känna företaget, kunderna och vad webbplatsen behöver uppnå.'],
            ['Design &amp; utveckling', 'Vi formar en modern, skräddarsydd lösning och håller processen tydlig.'],
            ['Lansering &amp; förvaltning', 'Webbplatsen går live med en stabil grund för SEO, support och fortsatt utveckling.'],
        ], 'sl-steps--list'),
        staark_sl_image('assets/images/showcase/process.webp', 'Modern arbetsplats med laptop', 'sl-media'),
        'sl-process-grid',
        '50%'
    ),
    'light'
);
