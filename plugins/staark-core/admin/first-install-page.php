<?php
/**
 * Staark First Install admin screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', static function (): void {
    add_submenu_page(
        STAARK_HUB_SLUG,
        __('First Site Install', 'staark-core'),
        __('First Install', 'staark-core'),
        'manage_options',
        'staark-hub-first-install',
        'staark_hub_render_first_install'
    );
}, 45);

add_action('admin_enqueue_scripts', static function (string $hook_suffix): void {
    if (strpos($hook_suffix, 'staark-hub-first-install') === false) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-first-install',
        staark_hub_runtime_url('assets/first-install.css'),
        ['staark-hub-admin'],
        is_file(STAARK_HUB_PLUGIN_DIR . 'assets/first-install.css')
            ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/first-install.css')
            : STAARK_HUB_VERSION
    );
});

add_action('admin_post_staark_first_install_apply', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
        wp_die(
            esc_html__('This action requires POST.', 'staark-core'),
            '',
            ['response' => 405]
        );
    }

    check_admin_referer('staark_first_install_apply');

    $input = [
        'business_name' => isset($_POST['business_name']) ? wp_unslash($_POST['business_name']) : '',
        'tagline' => isset($_POST['tagline']) ? wp_unslash($_POST['tagline']) : '',
        'industry' => isset($_POST['industry']) ? wp_unslash($_POST['industry']) : '',
        'phone' => isset($_POST['phone']) ? wp_unslash($_POST['phone']) : '',
        'email' => isset($_POST['email']) ? wp_unslash($_POST['email']) : '',
        'street_address' => isset($_POST['street_address']) ? wp_unslash($_POST['street_address']) : '',
        'locality' => isset($_POST['locality']) ? wp_unslash($_POST['locality']) : '',
        'region' => isset($_POST['region']) ? wp_unslash($_POST['region']) : '',
        'postal_code' => isset($_POST['postal_code']) ? wp_unslash($_POST['postal_code']) : '',
        'country' => isset($_POST['country']) ? wp_unslash($_POST['country']) : 'SE',
        'locale' => isset($_POST['locale']) ? wp_unslash($_POST['locale']) : get_locale(),
        'preset_id' => isset($_POST['preset_id']) ? wp_unslash($_POST['preset_id']) : 'scandinavian',
        'create_pages' => isset($_POST['create_pages']) ? '1' : '',
        'set_front_page' => isset($_POST['set_front_page']) ? '1' : '',
        'pretty_permalinks' => isset($_POST['pretty_permalinks']) ? '1' : '',
    ];

    $result = staark_hub_first_install_apply($input);

    $args = [
        'page' => 'staark-hub-first-install',
    ];

    if (! empty($result['ok'])) {
        $args['staark_bootstrap'] = 'success';
        $args['created'] = (int) ($result['created'] ?? 0);
        $args['skipped'] = (int) ($result['skipped'] ?? 0);
    } else {
        $args['staark_bootstrap'] = 'error';
        $args['message'] = rawurlencode((string) ($result['error'] ?? __('Bootstrap failed.', 'staark-core')));
    }

    wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
    exit;
});

function staark_hub_render_first_install(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $state = staark_hub_first_install_state();
    $readiness = staark_hub_first_install_readiness();
    $branding = function_exists('staark_hub_branding') ? staark_hub_branding() : [];
    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $forms = function_exists('staark_hub_forms_settings') ? staark_hub_forms_settings() : [];

    $business_name = (string) ($state['business_name'] ?? $branding['brand_name'] ?? get_bloginfo('name'));
    $tagline = (string) ($state['tagline'] ?? $branding['tagline'] ?? get_bloginfo('description'));
    $industry = (string) ($state['industry'] ?? '');
    $phone = (string) ($state['phone'] ?? $seo['phone'] ?? '');
    $email = (string) ($state['email'] ?? $forms['recipient_email'] ?? get_option('admin_email'));
    $street_address = (string) ($state['street_address'] ?? $seo['street_address'] ?? '');
    $locality = (string) ($state['locality'] ?? $seo['locality'] ?? '');
    $region = (string) ($state['region'] ?? $seo['region'] ?? '');
    $postal_code = (string) ($state['postal_code'] ?? $seo['postal_code'] ?? '');
    $country = (string) ($state['country'] ?? $seo['country'] ?? 'SE');

    $registry = function_exists('staark_theme_system_registry') ? staark_theme_system_registry() : [];
    $active_preset = function_exists('staark_theme_system_active_preset_id')
        ? staark_theme_system_active_preset_id()
        : 'scandinavian';
    $locales = staark_hub_first_install_available_locales();

    $notice = isset($_GET['staark_bootstrap']) ? sanitize_key(wp_unslash($_GET['staark_bootstrap'])) : '';
    ?>
    <div class="wrap staark-first-install">
        <div class="staark-first-install__hero">
            <div>
                <p class="staark-first-install__eyebrow"><?php echo esc_html__('WP-6.4.2 · Safe bootstrap', 'staark-core'); ?></p>
                <h1><?php echo esc_html__('First Site Install', 'staark-core'); ?></h1>
                <p><?php echo esc_html__('Turn a fresh WordPress install into a usable Staark starter site without repeating the same setup by hand.', 'staark-core'); ?></p>
            </div>
            <?php if (! empty($state['completed'])) : ?>
                <span class="staark-first-install__badge"><?php echo esc_html__('Previously completed', 'staark-core'); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($notice === 'success') : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    printf(
                        esc_html__('Bootstrap complete. %1$d page(s) created, %2$d existing page(s) preserved.', 'staark-core'),
                        isset($_GET['created']) ? absint($_GET['created']) : 0,
                        isset($_GET['skipped']) ? absint($_GET['skipped']) : 0
                    );
                    ?>
                </p>
            </div>
        <?php elseif ($notice === 'error') : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html(isset($_GET['message']) ? rawurldecode(sanitize_text_field(wp_unslash($_GET['message']))) : __('Bootstrap failed.', 'staark-core')); ?></p>
            </div>
        <?php endif; ?>

        <div class="staark-first-install__readiness">
            <?php foreach ($readiness as $item) : ?>
                <div class="staark-first-install__check <?php echo $item['ok'] ? 'is-ok' : 'is-review'; ?>">
                    <strong><?php echo esc_html($item['label']); ?></strong>
                    <span><?php echo esc_html($item['detail']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="staark-first-install__grid">
            <form class="staark-first-install__card" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="staark_first_install_apply">
                <?php wp_nonce_field('staark_first_install_apply'); ?>

                <h2><?php echo esc_html__('1. Business identity', 'staark-core'); ?></h2>

                <div class="staark-first-install__fields">
                    <label>
                        <span><?php echo esc_html__('Business name', 'staark-core'); ?></span>
                        <input type="text" name="business_name" value="<?php echo esc_attr($business_name); ?>" required maxlength="160">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Industry', 'staark-core'); ?></span>
                        <input type="text" name="industry" value="<?php echo esc_attr($industry); ?>" placeholder="Bygg, frisör, städ, service..." maxlength="160">
                    </label>
                    <label class="is-wide">
                        <span><?php echo esc_html__('Tagline / short description', 'staark-core'); ?></span>
                        <input type="text" name="tagline" value="<?php echo esc_attr($tagline); ?>" maxlength="220">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Contact email', 'staark-core'); ?></span>
                        <input type="email" name="email" value="<?php echo esc_attr($email); ?>" required maxlength="190">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Phone', 'staark-core'); ?></span>
                        <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" maxlength="80">
                    </label>
                </div>

                <h2><?php echo esc_html__('2. Local business details', 'staark-core'); ?></h2>

                <div class="staark-first-install__fields">
                    <label class="is-wide">
                        <span><?php echo esc_html__('Street address', 'staark-core'); ?></span>
                        <input type="text" name="street_address" value="<?php echo esc_attr($street_address); ?>" maxlength="190">
                    </label>
                    <label>
                        <span><?php echo esc_html__('City', 'staark-core'); ?></span>
                        <input type="text" name="locality" value="<?php echo esc_attr($locality); ?>" maxlength="120">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Region', 'staark-core'); ?></span>
                        <input type="text" name="region" value="<?php echo esc_attr($region); ?>" placeholder="Jönköpings län" maxlength="120">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Postal code', 'staark-core'); ?></span>
                        <input type="text" name="postal_code" value="<?php echo esc_attr($postal_code); ?>" maxlength="40">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Country code', 'staark-core'); ?></span>
                        <input type="text" name="country" value="<?php echo esc_attr($country); ?>" maxlength="2">
                    </label>
                </div>

                <h2><?php echo esc_html__('3. Design & WordPress setup', 'staark-core'); ?></h2>

                <div class="staark-first-install__fields">
                    <label>
                        <span><?php echo esc_html__('Theme preset', 'staark-core'); ?></span>
                        <select name="preset_id">
                            <?php if ($registry === []) : ?>
                                <option value="scandinavian"><?php echo esc_html__('Scandinavian', 'staark-core'); ?></option>
                            <?php else : ?>
                                <?php foreach ($registry as $id => $preset) : ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected($active_preset, $id); ?>>
                                        <?php echo esc_html((string) ($preset['name'] ?? $id)); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </label>

                    <label>
                        <span><?php echo esc_html__('Site language', 'staark-core'); ?></span>
                        <select name="locale">
                            <?php foreach ($locales as $locale => $label) : ?>
                                <option value="<?php echo esc_attr($locale); ?>" <?php selected(get_locale(), $locale); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small><?php echo esc_html__('Only already-installed WordPress languages are shown.', 'staark-core'); ?></small>
                    </label>
                </div>

                <div class="staark-first-install__options">
                    <label>
                        <input type="checkbox" name="create_pages" value="1" checked>
                        <span><?php echo esc_html__('Create missing starter pages', 'staark-core'); ?></span>
                    </label>
                    <label>
                        <input type="checkbox" name="set_front_page" value="1" checked>
                        <span><?php echo esc_html__('Set Home as the static front page', 'staark-core'); ?></span>
                    </label>
                    <label>
                        <input type="checkbox" name="pretty_permalinks" value="1" checked>
                        <span><?php echo esc_html__('Use /post-name/ permalinks', 'staark-core'); ?></span>
                    </label>
                </div>

                <div class="staark-first-install__warning">
                    <strong><?php echo esc_html__('Safe by default', 'staark-core'); ?></strong>
                    <p><?php echo esc_html__('Existing pages with the same slugs are preserved and never overwritten. Re-running the bootstrap updates identity, SEO, Forms and selected site settings, but does not replace authored page content.', 'staark-core'); ?></p>
                </div>

                <?php
                submit_button(
                    ! empty($state['completed'])
                        ? __('Run safe bootstrap again', 'staark-core')
                        : __('Build starter site', 'staark-core'),
                    'primary large'
                );
                ?>
            </form>

            <aside class="staark-first-install__card staark-first-install__aside">
                <h2><?php echo esc_html__('What gets configured', 'staark-core'); ?></h2>
                <ul>
                    <li><strong>Branding</strong><span>Business name + tagline</span></li>
                    <li><strong>Theme</strong><span>Selected Staark preset</span></li>
                    <li><strong>Pages</strong><span>Home, Tjänster, Om oss, Kontakt, Integritetspolicy</span></li>
                    <li><strong>Homepage</strong><span>Static front page</span></li>
                    <li><strong>Forms</strong><span>Contact recipient + privacy link</span></li>
                    <li><strong>SEO</strong><span>LocalBusiness identity + address</span></li>
                    <li><strong>WordPress</strong><span>Pretty permalinks</span></li>
                </ul>

                <div class="staark-first-install__next">
                    <strong><?php echo esc_html__('After bootstrap', 'staark-core'); ?></strong>
                    <p><?php echo esc_html__('Replace starter copy, add real images and reviews, verify the privacy text, test the contact form, then run Staark SEO / Performance readiness before launch.', 'staark-core'); ?></p>
                </div>

                <?php if (! empty($state['front_page_id'])) : ?>
                    <?php $edit_url = get_edit_post_link((int) $state['front_page_id'], 'raw'); ?>
                    <?php if (is_string($edit_url) && $edit_url !== '') : ?>
                        <a class="button button-secondary" href="<?php echo esc_url($edit_url); ?>">
                            <?php echo esc_html__('Edit generated homepage', 'staark-core'); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
    <?php
}
