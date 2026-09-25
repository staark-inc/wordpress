<?php
/**
 * Title: Gästfrihet — Startsida (alla sektioner)
 * Slug: staark/gast-home
 * Categories: staark-gastfrihet
 * Inserter: no
 * Description: Homepage sections for the active preset (Restaurang or Hotell). Used by the front-page template before a static front page exists.
 */

echo staark_gast_pattern_blocks(staark_gast_home_sections()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
