<?php
/**
 * Title: Local Business — Trust Bar
 * Slug: staark/local-business-proof
 * Categories: staark, staark-social-proof
 * Inserter: yes
 */

// S-Hub Light pattern — composed with inc/pattern-kit.php.
echo staark_sl_section(
    'proof',
    staark_sl_facts([
        ['Lokalt förankrade', 'Vi finns nära våra kunder och känner området.'],
        ['Tydlig offert', 'Du vet vad som ingår innan arbetet börjar.'],
        ['Personlig kontakt', 'Du får en riktig kontaktväg när frågor uppstår.'],
        ['Trygg leverans', 'Fokus på kvalitet, ordning och uppföljning.'],
    ], 'sl-facts sl-facts--bar alignwide'),
    'light'
);
