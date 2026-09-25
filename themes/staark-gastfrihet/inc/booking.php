<?php
/**
 * Booking settings (Appearance → Bokning) and the booking link shortcode.
 *
 * Restaurants and hotels usually take bookings in an external system
 * (TheFork, Quandoo, Bokabord, Booking.com, SiteMinder …). The URL is set per
 * site here and shown as a "Boka online" button. The booking request form
 * (Staark Hub Forms) can be shown next to it or on its own.
 *
 * [gast_booking_link label="" class=""]
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_GAST_BOOKING_OPTION = 'staark_gast_booking';

/**
 * @return array{url:string,provider:string,mode:string}
 */
function staark_gast_booking_defaults(): array
{
    return [
        'url' => '',
        'provider' => '',
        'mode' => 'both',
    ];
}

/**
 * @return array{url:string,provider:string,mode:string}
 */
function staark_gast_booking_settings(): array
{
    $saved = get_option(STAARK_GAST_BOOKING_OPTION, []);

    return staark_gast_booking_sanitize(is_array($saved) ? $saved : []);
}

/**
 * @param mixed $input
 * @return array{url:string,provider:string,mode:string}
 */
function staark_gast_booking_sanitize($input): array
{
    $input = is_array($input) ? $input : [];
    $settings = staark_gast_booking_defaults();

    $url = isset($input['url']) ? esc_url_raw(trim((string) $input['url']), ['https', 'http']) : '';
    $settings['url'] = $url;
    $settings['provider'] = isset($input['provider']) ? mb_substr(sanitize_text_field((string) $input['provider']), 0, 40) : '';

    $mode = isset($input['mode']) ? sanitize_key((string) $input['mode']) : 'both';
    $settings['mode'] = in_array($mode, ['both', 'external', 'form'], true) ? $mode : 'both';

    // "External only" without a link would leave no way to book.
    if ($settings['mode'] === 'external' && $settings['url'] === '') {
        $settings['mode'] = 'both';
    }

    return $settings;
}

add_action('admin_init', static function (): void {
    register_setting('staark_gast_booking', STAARK_GAST_BOOKING_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'staark_gast_booking_sanitize',
        'default' => staark_gast_booking_defaults(),
        'show_in_rest' => false,
    ]);
});

add_action('admin_menu', static function (): void {
    add_theme_page(
        __('Bokning', 'staark-gastfrihet'),
        __('Bokning', 'staark-gastfrihet'),
        'edit_theme_options',
        'staark-gast-booking',
        'staark_gast_booking_page'
    );
});

function staark_gast_booking_page(): void
{
    if (! current_user_can('edit_theme_options')) {
        return;
    }

    $settings = staark_gast_booking_settings();
    $hotel = staark_gast_is_hotel();
    $name = STAARK_GAST_BOOKING_OPTION;
    $examples = $hotel ? 'Booking.com, SiteMinder, Mews, Bokun' : 'TheFork, Quandoo, Bokabord, Waiteraid';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Bokning', 'staark-gastfrihet'); ?></h1>
        <p>
            <?php
            echo esc_html(
                $hotel
                    ? __('How guests book a room. Use your booking engine link, the booking request form (Staark Hub → Forms), or both.', 'staark-gastfrihet')
                    : __('How guests book a table. Use your booking system link, the booking request form (Staark Hub → Forms), or both.', 'staark-gastfrihet')
            );
            ?>
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('staark_gast_booking'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="gast-booking-url"><?php echo esc_html__('Booking link', 'staark-gastfrihet'); ?></label></th>
                    <td>
                        <input id="gast-booking-url" class="regular-text code" type="url" name="<?php echo esc_attr($name); ?>[url]" value="<?php echo esc_attr($settings['url']); ?>" placeholder="https://">
                        <p class="description"><?php echo esc_html(sprintf(__('Public booking page, for example from %s. Opens in a new tab.', 'staark-gastfrihet'), $examples)); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gast-booking-provider"><?php echo esc_html__('Provider name', 'staark-gastfrihet'); ?></label></th>
                    <td>
                        <input id="gast-booking-provider" class="regular-text" type="text" maxlength="40" name="<?php echo esc_attr($name); ?>[provider]" value="<?php echo esc_attr($settings['provider']); ?>">
                        <p class="description"><?php echo esc_html__('Optional. Shown on the button as "Boka via …". Leave empty for "Boka online".', 'staark-gastfrihet'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Show on the booking page', 'staark-gastfrihet'); ?></th>
                    <td>
                        <fieldset>
                            <?php
                            $modes = [
                                'both' => __('Booking link and booking request form', 'staark-gastfrihet'),
                                'external' => __('Only the booking link', 'staark-gastfrihet'),
                                'form' => __('Only the booking request form', 'staark-gastfrihet'),
                            ];
                            foreach ($modes as $value => $label) :
                                ?>
                                <label style="display:block;margin-bottom:6px">
                                    <input type="radio" name="<?php echo esc_attr($name); ?>[mode]" value="<?php echo esc_attr($value); ?>" <?php checked($settings['mode'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php echo esc_html__('Booking requests arrive in Staark Hub → Forms and are not confirmed automatically — reply to confirm the booking.', 'staark-gastfrihet'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * @param array<string,string>|string $atts
 */
function staark_gast_booking_link_shortcode($atts = []): string
{
    $atts = shortcode_atts(
        [
            'label' => '',
            'class' => '',
            'hint' => '1',
        ],
        is_array($atts) ? $atts : [],
        'gast_booking_link'
    );

    $settings = staark_gast_booking_settings();

    if ($settings['url'] === '' || $settings['mode'] === 'form') {
        if ($settings['url'] === '' && $atts['hint'] === '1' && current_user_can('edit_theme_options')) {
            return '<span class="gast-booking-hint">' . esc_html__('Lägg till bokningslänk under Utseende → Bokning', 'staark-gastfrihet') . '</span>';
        }

        return '';
    }

    $label = sanitize_text_field((string) $atts['label']);
    if ($label === '') {
        $label = $settings['provider'] !== ''
            /* translators: %s: booking provider, e.g. TheFork. */
            ? sprintf(__('Boka via %s', 'staark-gastfrihet'), $settings['provider'])
            : __('Boka online', 'staark-gastfrihet');
    }

    return sprintf(
        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener">%3$s<span class="gast-booking-link-icon" aria-hidden="true">↗</span></a>',
        esc_attr(staark_gast_classes('gast-booking-link wp-element-button', sanitize_html_class((string) $atts['class']))),
        esc_url($settings['url']),
        esc_html($label)
    );
}

add_shortcode('gast_booking_link', 'staark_gast_booking_link_shortcode');

/*
 * Point new sites to the booking settings (dismissible, per user).
 */
add_action('admin_notices', static function (): void {
    if (! current_user_can('edit_theme_options') || staark_gast_booking_settings()['url'] !== '') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (! $screen || ! in_array($screen->id, ['themes', 'dashboard'], true)) {
        return;
    }

    if (get_user_meta(get_current_user_id(), 'staark_gast_booking_notice_dismissed', true)) {
        return;
    }

    printf(
        '<div class="notice notice-info"><p>%1$s <a href="%2$s">%3$s</a> · <a href="%4$s">%5$s</a></p></div>',
        esc_html__('S-Hub Gästfrihet: add your booking link (TheFork, Booking.com …) so guests can book online.', 'staark-gastfrihet'),
        esc_url(admin_url('themes.php?page=staark-gast-booking')),
        esc_html__('Set up booking', 'staark-gastfrihet'),
        esc_url(wp_nonce_url(admin_url('admin-post.php?action=staark_gast_dismiss_booking_notice'), 'staark_gast_dismiss_booking_notice')),
        esc_html__('Dismiss', 'staark-gastfrihet')
    );
});

add_action('admin_post_staark_gast_dismiss_booking_notice', static function (): void {
    check_admin_referer('staark_gast_dismiss_booking_notice');
    update_user_meta(get_current_user_id(), 'staark_gast_booking_notice_dismissed', 1);
    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
});
