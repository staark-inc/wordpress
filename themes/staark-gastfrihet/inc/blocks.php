<?php
/**
 * Block markup helpers for the S-Hub Gästfrihet patterns.
 *
 * Every pattern is composed from these helpers, so sections share one
 * structure and stay valid, editable core blocks (group, columns, heading,
 * paragraph, list, buttons, image, quote, details, shortcode).
 *
 * Text passed in is trusted theme copy and may contain inline markup;
 * dynamic values must be escaped by the caller.
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param array<string,mixed> $attrs
 */
function staark_gast_attrs(array $attrs): string
{
    return $attrs === [] ? '' : ' ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function staark_gast_classes(string ...$classes): string
{
    return trim(implode(' ', array_filter(array_map('trim', $classes))));
}

function staark_gast_p(string $html, string $class = ''): string
{
    $attrs = $class !== '' ? ['className' => $class] : [];
    $open = $class !== '' ? '<p class="' . esc_attr($class) . '">' : '<p>';

    return "<!-- wp:paragraph" . staark_gast_attrs($attrs) . " -->\n{$open}{$html}</p>\n<!-- /wp:paragraph -->\n";
}

function staark_gast_h(string $html, int $level = 2, string $class = ''): string
{
    $attrs = [];
    if ($level !== 2) {
        $attrs['level'] = $level;
    }
    if ($class !== '') {
        $attrs['className'] = $class;
    }

    $classes = staark_gast_classes('wp-block-heading', $class);

    return "<!-- wp:heading" . staark_gast_attrs($attrs) . " -->\n<h{$level} class=\"{$classes}\">{$html}</h{$level}>\n<!-- /wp:heading -->\n";
}

/**
 * @param list<string> $items
 */
function staark_gast_list(array $items, string $class = '', bool $ordered = false): string
{
    $attrs = [];
    if ($ordered) {
        $attrs['ordered'] = true;
    }
    if ($class !== '') {
        $attrs['className'] = $class;
    }

    $tag = $ordered ? 'ol' : 'ul';
    $inner = '';
    foreach ($items as $item) {
        $inner .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->";
    }

    return "<!-- wp:list" . staark_gast_attrs($attrs) . " -->\n<{$tag} class=\"" . staark_gast_classes('wp-block-list', $class) . "\">{$inner}</{$tag}>\n<!-- /wp:list -->\n";
}

/**
 * @param list<array{0:string,1:string,2?:string}> $buttons label, href, style (fill|outline)
 */
function staark_gast_buttons(array $buttons, string $class = 'gast-actions'): string
{
    $inner = '';
    foreach ($buttons as $button) {
        $style = ($button[2] ?? 'fill') === 'outline' ? 'is-style-outline' : '';
        $attrs = $style !== '' ? ['className' => $style] : [];
        $inner .= "<!-- wp:button" . staark_gast_attrs($attrs) . " -->\n"
            . '<div class="' . staark_gast_classes('wp-block-button', $style) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url($button[1]) . '">' . $button[0] . "</a></div>\n"
            . "<!-- /wp:button -->\n";
    }

    return "<!-- wp:buttons" . staark_gast_attrs(['className' => $class]) . " -->\n<div class=\"" . staark_gast_classes('wp-block-buttons', $class) . "\">{$inner}</div>\n<!-- /wp:buttons -->\n";
}

function staark_gast_image(string $file, string $alt, string $class = ''): string
{
    $attrs = ['sizeSlug' => 'full', 'linkDestination' => 'none'];
    if ($class !== '') {
        $attrs['className'] = $class;
    }

    $src = str_starts_with($file, 'http') ? $file : get_theme_file_uri($file);

    return "<!-- wp:image" . staark_gast_attrs($attrs) . " -->\n"
        . '<figure class="' . staark_gast_classes('wp-block-image size-full', $class) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . "\"/></figure>\n"
        . "<!-- /wp:image -->\n";
}

function staark_gast_shortcode(string $shortcode): string
{
    return "<!-- wp:shortcode -->\n{$shortcode}\n<!-- /wp:shortcode -->\n";
}

/**
 * @param array<string,mixed> $layout
 */
function staark_gast_group(string $inner, string $class, array $layout = ['type' => 'default'], string $align = '', string $anchor = ''): string
{
    $attrs = [];
    if ($align !== '') {
        $attrs['align'] = $align;
    }
    if ($anchor !== '') {
        $attrs['anchor'] = $anchor;
    }
    $attrs['className'] = $class;
    $attrs['layout'] = $layout;

    $classes = staark_gast_classes('wp-block-group', $align !== '' ? 'align' . $align : '', $class);
    $id = $anchor !== '' ? ' id="' . esc_attr($anchor) . '"' : '';

    return "<!-- wp:group" . staark_gast_attrs($attrs) . " -->\n<div{$id} class=\"{$classes}\">{$inner}</div>\n<!-- /wp:group -->\n";
}

/**
 * Full-width section with a wide inner container.
 */
function staark_gast_section(string $name, string $inner, string $anchor = '', string $extra_class = ''): string
{
    return staark_gast_group(
        $inner,
        staark_gast_classes('gast-section', 'gast-' . $name, $extra_class),
        ['type' => 'constrained'],
        'full',
        $anchor
    );
}

/**
 * Section heading: eyebrow, title and intro (split puts the intro beside it).
 */
function staark_gast_head(string $eyebrow, string $title, string $intro = '', bool $split = true, string $extra = ''): string
{
    $inner = ($eyebrow !== '' ? staark_gast_p($eyebrow, 'gast-eyebrow') : '')
        . staark_gast_h($title, 2, 'gast-title')
        . ($intro !== '' ? staark_gast_p($intro, 'gast-intro') : '')
        . $extra;

    return staark_gast_group($inner, staark_gast_classes('gast-head', $split && $intro !== '' ? 'gast-head--split' : ''), ['type' => 'default'], 'wide');
}

/**
 * Grid with an explicit column count (CSS sets the columns for WP 6.x/7.x).
 */
function staark_gast_grid(string $inner, int $columns, string $class = ''): string
{
    return staark_gast_group(
        $inner,
        staark_gast_classes('gast-grid', 'gast-grid--' . $columns, $class),
        ['type' => 'grid', 'columnCount' => $columns, 'minimumColumnWidth' => '14rem'],
        'wide'
    );
}

/**
 * Two columns.
 */
function staark_gast_columns(string $left, string $right, string $class = '', string $left_width = '50%', bool $center = true, string $left_class = 'gast-col-main', string $right_class = 'gast-col-side'): string
{
    $right_width = (100 - (int) $left_width) . '%';
    $cols_attrs = ($center ? ['verticalAlignment' => 'center'] : []) + ['align' => 'wide', 'className' => staark_gast_classes('gast-columns', $class)];
    $cols_class = staark_gast_classes('wp-block-columns alignwide', $center ? 'are-vertically-aligned-center' : '', 'gast-columns', $class);

    $column = static function (string $inner, string $width, string $col_class) use ($center): string {
        $attrs = ($center ? ['verticalAlignment' => 'center'] : []) + ['width' => $width, 'className' => $col_class];
        $classes = staark_gast_classes('wp-block-column', $center ? 'is-vertically-aligned-center' : '', $col_class);

        return "<!-- wp:column" . staark_gast_attrs($attrs) . " -->\n<div class=\"{$classes}\" style=\"flex-basis:{$width}\">{$inner}</div>\n<!-- /wp:column -->\n";
    };

    return "<!-- wp:columns" . staark_gast_attrs($cols_attrs) . " -->\n<div class=\"{$cols_class}\">"
        . $column($left, $left_width, $left_class)
        . $column($right, $right_width, $right_class)
        . "</div>\n<!-- /wp:columns -->\n";
}

function staark_gast_details(string $summary, string $text, string $class = 'gast-faq-item'): string
{
    return "<!-- wp:details" . staark_gast_attrs(['className' => $class]) . " -->\n"
        . '<details class="' . staark_gast_classes('wp-block-details', $class) . "\"><summary>{$summary}</summary>"
        . staark_gast_p($text)
        . "</details>\n<!-- /wp:details -->\n";
}

function staark_gast_quote(string $text, string $cite, string $class = 'gast-review'): string
{
    return "<!-- wp:quote" . staark_gast_attrs(['className' => $class]) . " -->\n"
        . '<blockquote class="' . staark_gast_classes('wp-block-quote', $class) . '">'
        . staark_gast_p($text)
        . "<cite>{$cite}</cite></blockquote>\n<!-- /wp:quote -->\n";
}

/**
 * Pattern reference blocks.
 *
 * @param list<string> $patterns Pattern names without the staark/gast- prefix.
 */
function staark_gast_pattern_blocks(array $patterns): string
{
    $blocks = [];

    foreach ($patterns as $pattern) {
        $blocks[] = '<!-- wp:pattern {"slug":"' . STAARK_GAST_PATTERN_PREFIX . sanitize_key($pattern) . '"} /-->';
    }

    return implode("\n", $blocks);
}
