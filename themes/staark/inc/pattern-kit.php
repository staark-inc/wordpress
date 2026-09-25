<?php
/**
 * S-Hub Light pattern kit.
 *
 * Small helpers that emit core block markup (group, columns, heading,
 * paragraph, list, buttons, image, quote, shortcode) with the `sl-*` class
 * system. Every S-Hub Light pattern is composed from these helpers, so all
 * sections share one structure, stay editable in the block editor and line
 * up on the same wide container as the header and footer.
 *
 * Text passed to the helpers is trusted theme copy and may contain inline
 * markup (<strong>, <em>, <a>); dynamic values must be escaped by the caller.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param array<string,mixed> $attrs
 */
function staark_sl_attrs(array $attrs): string
{
    return $attrs === [] ? '' : ' ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function staark_sl_classes(string ...$classes): string
{
    return trim(implode(' ', array_filter(array_map('trim', $classes))));
}

function staark_sl_p(string $html, string $class = ''): string
{
    $attrs = $class !== '' ? ['className' => $class] : [];
    $open = $class !== '' ? '<p class="' . esc_attr($class) . '">' : '<p>';

    return "<!-- wp:paragraph" . staark_sl_attrs($attrs) . " -->\n{$open}{$html}</p>\n<!-- /wp:paragraph -->\n";
}

function staark_sl_h(string $html, int $level = 2, string $class = ''): string
{
    $attrs = [];
    if ($level !== 2) {
        $attrs['level'] = $level;
    }
    if ($class !== '') {
        $attrs['className'] = $class;
    }

    $classes = staark_sl_classes('wp-block-heading', $class);

    return "<!-- wp:heading" . staark_sl_attrs($attrs) . " -->\n<h{$level} class=\"{$classes}\">{$html}</h{$level}>\n<!-- /wp:heading -->\n";
}

/**
 * @param list<string> $items
 */
function staark_sl_list(array $items, string $class = '', bool $ordered = false): string
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

    return "<!-- wp:list" . staark_sl_attrs($attrs) . " -->\n<{$tag} class=\"" . staark_sl_classes('wp-block-list', $class) . "\">{$inner}</{$tag}>\n<!-- /wp:list -->\n";
}

/**
 * @param list<array{0:string,1:string,2?:string}> $buttons label, href, style (fill|outline)
 */
function staark_sl_buttons(array $buttons, string $class = 'sl-actions'): string
{
    $inner = '';
    foreach ($buttons as $button) {
        $style = ($button[2] ?? 'fill') === 'outline' ? 'is-style-outline' : '';
        $attrs = $style !== '' ? ['className' => $style] : [];
        $inner .= "<!-- wp:button" . staark_sl_attrs($attrs) . " -->\n"
            . '<div class="' . staark_sl_classes('wp-block-button', $style) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url($button[1]) . '">' . $button[0] . "</a></div>\n"
            . "<!-- /wp:button -->\n";
    }

    return "<!-- wp:buttons" . staark_sl_attrs(['className' => $class]) . " -->\n<div class=\"" . staark_sl_classes('wp-block-buttons', $class) . "\">{$inner}</div>\n<!-- /wp:buttons -->\n";
}

function staark_sl_image(string $file, string $alt, string $class = ''): string
{
    $attrs = ['sizeSlug' => 'full', 'linkDestination' => 'none'];
    if ($class !== '') {
        $attrs['className'] = $class;
    }

    $src = str_starts_with($file, 'http') ? $file : get_theme_file_uri($file);

    return "<!-- wp:image" . staark_sl_attrs($attrs) . " -->\n"
        . '<figure class="' . staark_sl_classes('wp-block-image size-full', $class) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . "\"/></figure>\n"
        . "<!-- /wp:image -->\n";
}

function staark_sl_shortcode(string $shortcode): string
{
    return "<!-- wp:shortcode -->\n{$shortcode}\n<!-- /wp:shortcode -->\n";
}

/**
 * Generic group.
 *
 * @param array<string,mixed> $layout
 */
function staark_sl_group(string $inner, string $class, array $layout = ['type' => 'default'], string $align = '', string $anchor = ''): string
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

    $classes = staark_sl_classes('wp-block-group', $align !== '' ? 'align' . $align : '', $class);
    $id = $anchor !== '' ? ' id="' . esc_attr($anchor) . '"' : '';

    return "<!-- wp:group" . staark_sl_attrs($attrs) . " -->\n<div{$id} class=\"{$classes}\">{$inner}</div>\n<!-- /wp:group -->\n";
}

/**
 * Full-width section with a wide inner container.
 *
 * @param string $tone light|surface|dark|accent|white
 */
function staark_sl_section(string $name, string $inner, string $tone = 'light', string $anchor = ''): string
{
    return staark_sl_group(
        $inner,
        staark_sl_classes('sl-section', 'sl-' . $name, 'sl-tone-' . $tone),
        ['type' => 'constrained'],
        'full',
        $anchor
    );
}

/**
 * Section heading: eyebrow, title and intro. Split puts the intro beside the title.
 */
function staark_sl_head(string $eyebrow, string $title, string $intro = '', bool $split = true, string $extra = ''): string
{
    $inner = ($eyebrow !== '' ? staark_sl_p($eyebrow, 'sl-eyebrow') : '')
        . staark_sl_h($title, 2, 'sl-title')
        . ($intro !== '' ? staark_sl_p($intro, 'sl-intro') : '')
        . $extra;

    return staark_sl_group($inner, staark_sl_classes('sl-head', $split && $intro !== '' ? 'sl-head--split' : ''), ['type' => 'default'], 'wide');
}

/**
 * Grid of items with explicit column count (CSS sets the columns).
 */
function staark_sl_grid(string $inner, int $columns, string $class = ''): string
{
    return staark_sl_group(
        $inner,
        staark_sl_classes('sl-grid', 'sl-grid--' . $columns, $class),
        ['type' => 'grid', 'columnCount' => $columns, 'minimumColumnWidth' => '14rem'],
        'wide'
    );
}

/**
 * Two columns (copy + media).
 */
function staark_sl_split(string $left, string $right, string $class = '', string $left_width = '52%', bool $center = true): string
{
    $right_width = (100 - (int) $left_width) . '%';
    $valign = $center ? ['verticalAlignment' => 'center'] : [];
    $cols_attrs = $valign + ['align' => 'wide', 'className' => staark_sl_classes('sl-split', $class)];
    $cols_class = staark_sl_classes('wp-block-columns alignwide', $center ? 'are-vertically-aligned-center' : '', 'sl-split', $class);

    $column = static function (string $inner, string $width, string $col_class) use ($center): string {
        $attrs = ($center ? ['verticalAlignment' => 'center'] : []) + ['width' => $width, 'className' => $col_class];
        $classes = staark_sl_classes('wp-block-column', $center ? 'is-vertically-aligned-center' : '', $col_class);

        return "<!-- wp:column" . staark_sl_attrs($attrs) . " -->\n<div class=\"{$classes}\" style=\"flex-basis:{$width}\">{$inner}</div>\n<!-- /wp:column -->\n";
    };

    return "<!-- wp:columns" . staark_sl_attrs($cols_attrs) . " -->\n<div class=\"{$cols_class}\">"
        . $column($left, $left_width, 'sl-split-main')
        . $column($right, $right_width, 'sl-split-side')
        . "</div>\n<!-- /wp:columns -->\n";
}

/**
 * Card: optional image, number/tag, title, text, footer paragraph.
 *
 * @param array{image?:string,alt?:string,num?:string,title:string,text?:string,list?:list<string>,foot?:string} $card
 */
function staark_sl_card(array $card, string $class = ''): string
{
    $inner = '';
    if (! empty($card['image'])) {
        $inner .= staark_sl_image($card['image'], $card['alt'] ?? '', 'sl-card-media');
    }

    $body = '';
    if (! empty($card['num'])) {
        $body .= staark_sl_p($card['num'], 'sl-card-num');
    }
    $body .= staark_sl_h($card['title'], 3, 'sl-card-title');
    if (! empty($card['text'])) {
        $body .= staark_sl_p($card['text'], 'sl-card-text');
    }
    if (! empty($card['list'])) {
        $body .= staark_sl_list($card['list'], 'sl-checks sl-card-list');
    }
    if (! empty($card['foot'])) {
        $body .= staark_sl_p($card['foot'], 'sl-card-foot');
    }

    $inner .= staark_sl_group($body, 'sl-card-body');

    return staark_sl_group($inner, staark_sl_classes('sl-card', ! empty($card['image']) ? 'sl-card--media' : '', $class));
}

/**
 * Numbered steps.
 *
 * @param list<array{0:string,1:string}> $steps title, text
 */
function staark_sl_steps(array $steps, string $class = ''): string
{
    $inner = '';
    foreach ($steps as $i => $step) {
        $inner .= staark_sl_group(
            staark_sl_p(sprintf('%02d', $i + 1), 'sl-step-num')
            . staark_sl_h($step[0], 3, 'sl-step-title')
            . staark_sl_p($step[1], 'sl-step-text'),
            'sl-step'
        );
    }

    return staark_sl_group($inner, staark_sl_classes('sl-steps', $class));
}

/**
 * Quote card.
 */
function staark_sl_quote(string $text, string $cite, string $class = 'sl-quote'): string
{
    return "<!-- wp:quote" . staark_sl_attrs(['className' => $class]) . " -->\n"
        . '<blockquote class="' . staark_sl_classes('wp-block-quote', $class) . '">'
        . staark_sl_p($text)
        . "<cite>{$cite}</cite></blockquote>\n<!-- /wp:quote -->\n";
}

/**
 * Small "label + text" items (trust bar, benefits, facts).
 *
 * @param list<array{0:string,1:string}> $items
 */
function staark_sl_facts(array $items, string $class = 'sl-facts'): string
{
    $inner = '';
    foreach ($items as $item) {
        $inner .= staark_sl_group(
            staark_sl_p($item[0], 'sl-fact-title') . staark_sl_p($item[1], 'sl-fact-text'),
            'sl-fact'
        );
    }

    return staark_sl_group($inner, $class);
}

/**
 * Staark Hub contact form (only when the Hub is active).
 */
function staark_sl_form(string $form_id, string $button = 'Skicka förfrågan'): string
{
    if (! shortcode_exists('staark_contact_form')) {
        return staark_sl_p(
            sprintf('Kontaktformuläret visas när Staark Hub är aktiverat. Mejla oss under tiden: [staark_contact field="email" link="1"]'),
            'sl-form-fallback'
        );
    }

    return staark_sl_shortcode(
        sprintf('[staark_contact_form button="%s" form_id="%s"]', esc_attr($button), esc_attr($form_id))
    );
}
