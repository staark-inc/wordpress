<?php
/**
 * Salon contact shortcodes.
 *
 * Contact details are entered once in Staark Hub (First Install / SEO) and
 * reused in the header, booking section, footer and mobile booking bar.
 *
 * [salong_contact field="phone" link="1" fallback="08-000 00 00"]
 * Fields: phone, email, address, street, city, name.
 * `hint` is shown instead of an empty value, but only to logged-in editors,
 * so a missing detail is noticed during setup without showing it to visitors.
 *
 * @package StaarkSalong
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string,string>
 */
function staark_salong_contact_details(): array
{
    static $details = null;

    if (is_array($details)) {
        return $details;
    }

    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $install = function_exists('staark_hub_first_install_state') ? staark_hub_first_install_state() : [];

    $pick = static function (string $key) use ($seo, $install): string {
        foreach ([$seo, $install] as $source) {
            if (isset($source[$key]) && is_scalar($source[$key]) && trim((string) $source[$key]) !== '') {
                return trim((string) $source[$key]);
            }
        }

        return '';
    };

    $details = [
        'name' => $pick('organization_name') !== '' ? $pick('organization_name') : (string) get_bloginfo('name'),
        'phone' => $pick('phone'),
        'email' => $pick('email'),
        'street' => $pick('street_address'),
        'postal_code' => $pick('postal_code'),
        'city' => $pick('locality'),
    ];

    return $details;
}

/**
 * @param array<string,string>|string $atts
 */
function staark_salong_contact_shortcode($atts = []): string
{
    $atts = shortcode_atts(
        [
            'field' => 'phone',
            'link' => '0',
            'fallback' => '',
            'label' => '',
            'hint' => '',
        ],
        is_array($atts) ? $atts : [],
        'salong_contact'
    );

    $details = staark_salong_contact_details();
    $field = sanitize_key((string) $atts['field']);
    $link = in_array(strtolower((string) $atts['link']), ['1', 'true', 'yes'], true);

    switch ($field) {
        case 'address':
            $city_line = trim($details['postal_code'] . ' ' . $details['city']);
            $value = implode(', ', array_filter([$details['street'], $city_line]));
            break;
        case 'street':
            $value = $details['street'];
            break;
        case 'city':
            $value = $details['city'];
            break;
        case 'email':
            $value = $details['email'];
            break;
        case 'name':
            $value = $details['name'];
            break;
        case 'phone':
        default:
            $field = 'phone';
            $value = $details['phone'];
            break;
    }

    if ($value === '') {
        $value = sanitize_text_field((string) $atts['fallback']);
    }

    if ($value === '') {
        $hint = sanitize_text_field((string) $atts['hint']);

        if ($hint !== '' && current_user_can('edit_pages')) {
            return '<span class="salong-contact salong-contact--missing">' . esc_html($hint) . '</span>';
        }

        return '';
    }

    $text = (string) $atts['label'] !== '' ? sanitize_text_field((string) $atts['label']) : $value;

    if (! $link) {
        return '<span class="salong-contact salong-contact--' . esc_attr($field) . '">' . esc_html($text) . '</span>';
    }

    if ($field === 'phone') {
        $href = 'tel:' . preg_replace('/[^0-9+]/', '', $value);
    } elseif ($field === 'email' && is_email($value)) {
        $href = 'mailto:' . $value;
    } elseif ($field === 'address' || $field === 'street' || $field === 'city') {
        $href = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($details['name'] . ' ' . $value);
    } else {
        $href = home_url('/');
    }

    $external = str_starts_with($href, 'https://');

    return sprintf(
        '<a class="salong-contact salong-contact--%1$s" href="%2$s"%3$s>%4$s</a>',
        esc_attr($field),
        esc_url($href, ['tel', 'mailto', 'https', 'http']),
        $external ? ' target="_blank" rel="noopener"' : '',
        esc_html($text)
    );
}

add_shortcode('salong_contact', 'staark_salong_contact_shortcode');

/*
 * Current year for footer copy without relying on the Hub.
 */
add_shortcode('salong_year', static fn (): string => esc_html(wp_date('Y')));
