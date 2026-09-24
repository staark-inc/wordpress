<?php
/**
 * Plugin Name: Staark Hub
 * Plugin URI: https://staarkinc.com
 * Description: Website management layer for sites built and maintained by Staark Inc.
 * Version: 0.5.4
 * Author: Staark Inc.
 * Author URI: https://staarkinc.com
 * Text Domain: staark-core
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_VERSION = '0.5.4';
const STAARK_HUB_SLUG = 'staark-hub';

/**
 * Return the current Staark Hub admin page slug.
 */
function staark_hub_current_page(): string
{
    return isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
}

/**
 * Check whether the current admin screen belongs to Staark Hub.
 */
function staark_hub_is_admin_page(): bool
{
    $page = staark_hub_current_page();

    return $page === STAARK_HUB_SLUG || strpos($page, 'staark-hub-') === 0;
}

/**
 * Inline Code2-style icon used by the WordPress admin menu.
 */
function staark_hub_menu_icon(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Pages that make up the first Staark Business Starter.
 * Existing pages are never overwritten by the installer.
 *
 * @return array<string, array{title:string, content:string}>
 */
function staark_hub_starter_pages(): array
{
    return [
        'tjanster' => [
            'title' => 'Tjänster',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Från första idé till en snabb, tydlig och lättskött webbplats. Välj det stöd som passar företaget idag och bygg vidare när behovet växer.</p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"staark/services-three"} /-->
<!-- wp:pattern {"slug":"staark/process-three"} /-->
<!-- wp:pattern {"slug":"staark/faq-business"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'om-oss' => [
            'title' => 'Om oss',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Vi bygger webbplatser med samma fokus som vi själva vill ha från en leverantör: tydlig kommunikation, stabil teknik och lösningar som faktiskt går att använda efter lansering.</p>
<!-- /wp:paragraph -->
<!-- wp:columns {"align":"wide","className":"staark-page-grid","style":{"spacing":{"blockGap":{"left":"24px"},"margin":{"top":"48px","bottom":"72px"}}}} -->
<div class="wp-block-columns alignwide staark-page-grid" style="margin-top:48px;margin-bottom:72px">
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Tydligt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Du ska förstå vad som byggs, varför det behövs och vad nästa steg är.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Praktiskt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Vi prioriterar sådant som gör webbplatsen snabbare, enklare och bättre för kunden.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Långsiktigt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">En bra grund ska kunna växa utan att hela webbplatsen måste byggas om från början.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:pattern {"slug":"staark/why-us-business"} /-->
<!-- wp:pattern {"slug":"staark/testimonials-business"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Berätta kort vad du behöver hjälp med. Vi börjar med behovet och tar tekniken därefter.</p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"staark/contact-business"} /-->
<!-- wp:pattern {"slug":"staark/faq-business"} /-->
BLOCKS,
        ],
        'integritetspolicy' => [
            'title' => 'Integritetspolicy',
            'content' => <<<'BLOCKS'
<!-- wp:group {"className":"staark-privacy","layout":{"type":"constrained"}} -->
<div class="wp-block-group staark-privacy">
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted"} --><p class="staark-page-lead has-muted-color has-text-color">Den här sidan är en startmall. Anpassa uppgifterna om personuppgiftsansvarig, kontaktvägar, formulär, analysverktyg och andra tjänster innan webbplatsen publiceras för kund.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Vilka uppgifter samlas in?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>När du kontaktar oss kan vi behandla uppgifter som namn, e-postadress, telefonnummer, företag och information som du själv lämnar i ett formulär eller meddelande.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Varför behandlas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Uppgifterna används för att besvara förfrågningar, hantera kundrelationer och leverera de tjänster som efterfrågas.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Hur länge sparas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Personuppgifter sparas inte längre än vad som behövs för ändamålet eller vad som krävs enligt tillämpliga regler och bokföringskrav.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Dina rättigheter</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Du kan kontakta företaget för frågor om dina personuppgifter eller för att begära tillgång, rättelse eller radering när det är tillämpligt.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Kontakt</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Ange företagets aktuella kontaktuppgifter här innan lansering.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
BLOCKS,
        ],
    ];
}

function staark_hub_installed_page_count(): int
{
    $count = 0;

    foreach (array_keys(staark_hub_starter_pages()) as $slug) {
        if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
            ++$count;
        }
    }

    return $count;
}

/**
 * Count pending WordPress, plugin and theme updates without forcing a remote check.
 *
 * @return array{core:int,plugins:int,themes:int,total:int}
 */
function staark_hub_pending_updates(): array
{
    $core_updates = get_site_transient('update_core');
    $plugin_updates = get_site_transient('update_plugins');
    $theme_updates = get_site_transient('update_themes');

    $core = 0;
    if (is_object($core_updates) && ! empty($core_updates->updates) && is_array($core_updates->updates)) {
        foreach ($core_updates->updates as $update) {
            if (is_object($update) && isset($update->response) && $update->response === 'upgrade') {
                ++$core;
            }
        }
    }

    $plugins = is_object($plugin_updates) && isset($plugin_updates->response) && is_array($plugin_updates->response)
        ? count($plugin_updates->response)
        : 0;
    $themes = is_object($theme_updates) && isset($theme_updates->response) && is_array($theme_updates->response)
        ? count($theme_updates->response)
        : 0;

    return [
        'core' => $core,
        'plugins' => $plugins,
        'themes' => $themes,
        'total' => $core + $plugins + $themes,
    ];
}

/**
 * Return an edit URL for the homepage. Block themes without a static front page
 * open the front-page template in the Site Editor.
 */
function staark_hub_homepage_edit_url(): string
{
    $front_page_id = (int) get_option('page_on_front');

    if ($front_page_id > 0) {
        $url = get_edit_post_link($front_page_id, 'raw');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $stylesheet = wp_get_theme()->get_stylesheet();
    $template_id = $stylesheet . '//front-page';

    return admin_url('site-editor.php?path=/wp_template/' . rawurlencode($template_id));
}

function staark_hub_published_page_count(): int
{
    $counts = wp_count_posts('page');

    return isset($counts->publish) ? (int) $counts->publish : 0;
}


/**
 * Branding defaults used before a client saves their own identity.
 *
 * @return array{brand_name:string,tagline:string,primary_color:string,ink_color:string,logo_id:int,site_icon_id:int}
 */
function staark_hub_branding_defaults(): array
{
    $brand_name = trim((string) get_bloginfo('name'));
    $tagline = trim((string) get_bloginfo('description'));

    if ($brand_name === '' || in_array(strtolower($brand_name), ['test blog', 'my wordpress', 'wordpress'], true)) {
        $brand_name = 'Staark Inc';
    }

    if ($tagline === '' || strtolower($tagline) === 'just another wordpress site') {
        $tagline = 'Moderna, snabba och genomtänkta webbplatser.';
    }

    return [
        'brand_name' => $brand_name,
        'tagline' => $tagline,
        'primary_color' => '#2563eb',
        'ink_color' => '#0f172a',
        'logo_id' => (int) get_theme_mod('custom_logo', 0),
        'site_icon_id' => (int) get_option('site_icon', 0),
    ];
}

/**
 * Return saved branding merged with safe defaults.
 *
 * @return array{brand_name:string,tagline:string,primary_color:string,ink_color:string,logo_id:int,site_icon_id:int}
 */
function staark_hub_branding(): array
{
    $defaults = staark_hub_branding_defaults();
    $saved = get_option('staark_hub_branding', []);

    if (! is_array($saved)) {
        $saved = [];
    }

    $branding = array_merge($defaults, $saved);
    $branding['brand_name'] = sanitize_text_field((string) $branding['brand_name']);
    $branding['tagline'] = sanitize_text_field((string) $branding['tagline']);
    $branding['primary_color'] = sanitize_hex_color((string) $branding['primary_color']) ?: $defaults['primary_color'];
    $branding['ink_color'] = sanitize_hex_color((string) $branding['ink_color']) ?: $defaults['ink_color'];
    $branding['logo_id'] = absint($branding['logo_id']);
    $branding['site_icon_id'] = absint($branding['site_icon_id']);

    return $branding;
}

function staark_hub_darken_hex(string $hex, int $amount = 28): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
        return '#1d4ed8';
    }

    $rgb = [];
    for ($i = 0; $i < 6; $i += 2) {
        $rgb[] = max(0, hexdec(substr($hex, $i, 2)) - $amount);
    }

    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

function staark_hub_branding_css(): string
{
    $branding = staark_hub_branding();
    $primary_dark = staark_hub_darken_hex($branding['primary_color']);

    return sprintf(
        ':root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;--wp--preset--color--dark:%3$s;}',
        $branding['primary_color'],
        $primary_dark,
        $branding['ink_color']
    );
}

/**
 * Frontend brand lockup used by the Staark block theme header and footer.
 */
function staark_hub_frontend_brand_shortcode(): string
{
    $branding = staark_hub_branding();
    $logo = '';

    if ($branding['logo_id'] > 0 && wp_attachment_is_image($branding['logo_id'])) {
        $logo = wp_get_attachment_image(
            $branding['logo_id'],
            'medium',
            false,
            [
                'class' => 'staark-brand__logo',
                'alt' => '',
                'loading' => 'eager',
                'decoding' => 'async',
            ]
        );
    }

    if ($logo === '') {
        $logo = '<svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>';
    }

    return sprintf(
        '<a class="staark-brand" aria-label="%1$s" href="%2$s">%3$s<strong>%4$s</strong><span aria-hidden="true"></span></a>',
        esc_attr(sprintf(__('%s home', 'staark-core'), $branding['brand_name'])),
        esc_url(home_url('/')),
        $logo,
        esc_html($branding['brand_name'])
    );
}

function staark_hub_frontend_tagline_shortcode(): string
{
    $branding = staark_hub_branding();

    return '<p class="staark-brand-tagline has-muted-color has-text-color">' . esc_html($branding['tagline']) . '</p>';
}

function staark_hub_frontend_copyright_shortcode(): string
{
    $branding = staark_hub_branding();

    return sprintf(
        '<p class="staark-brand-copyright has-text-align-center has-muted-color has-text-color has-sm-font-size">© %1$s %2$s. %3$s</p>',
        esc_html(wp_date('Y')),
        esc_html($branding['brand_name']),
        esc_html__('All rights reserved.', 'staark-core')
    );
}

add_shortcode('staark_brand', 'staark_hub_frontend_brand_shortcode');
add_shortcode('staark_brand_tagline', 'staark_hub_frontend_tagline_shortcode');
add_shortcode('staark_brand_copyright', 'staark_hub_frontend_copyright_shortcode');

add_action('wp_enqueue_scripts', static function (): void {
    wp_add_inline_style('staark-theme', staark_hub_branding_css());
}, 30);

add_action('enqueue_block_editor_assets', static function (): void {
    wp_add_inline_style('wp-edit-blocks', staark_hub_branding_css());
});

add_action('admin_menu', static function (): void {
    add_menu_page(
        __('Staark Website Hub', 'staark-core'),
        __('Staark Hub', 'staark-core'),
        'manage_options',
        STAARK_HUB_SLUG,
        'staark_hub_render_overview',
        staark_hub_menu_icon(),
        3
    );

    add_submenu_page(STAARK_HUB_SLUG, __('Overview', 'staark-core'), __('Overview', 'staark-core'), 'manage_options', STAARK_HUB_SLUG, 'staark_hub_render_overview');
    add_submenu_page(STAARK_HUB_SLUG, __('Website', 'staark-core'), __('Website', 'staark-core'), 'manage_options', 'staark-hub-website', 'staark_hub_render_website');
    add_submenu_page(STAARK_HUB_SLUG, __('Leads', 'staark-core'), __('Leads', 'staark-core'), 'manage_options', 'staark-hub-leads', 'staark_hub_render_leads');
    add_submenu_page(STAARK_HUB_SLUG, __('Support', 'staark-core'), __('Support', 'staark-core'), 'manage_options', 'staark-hub-support', 'staark_hub_render_support');
    add_submenu_page(STAARK_HUB_SLUG, __('Branding', 'staark-core'), __('Branding', 'staark-core'), 'manage_options', 'staark-hub-branding', 'staark_hub_render_branding');
    add_submenu_page(STAARK_HUB_SLUG, __('Connect', 'staark-core'), __('Connect to Staark', 'staark-core'), 'manage_options', 'staark-hub-connect', 'staark_hub_render_connect');
});

add_action('admin_enqueue_scripts', static function (string $hook_suffix): void {
    if (strpos($hook_suffix, 'staark-hub') === false) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-admin',
        plugin_dir_url(__FILE__) . 'assets/admin.css',
        [],
        STAARK_HUB_VERSION
    );

    if (staark_hub_current_page() === 'staark-hub-branding') {
        wp_enqueue_media();
        wp_enqueue_script(
            'staark-hub-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            [],
            STAARK_HUB_VERSION,
            true
        );
    }
});

add_filter('admin_body_class', static function (string $classes): string {
    if (staark_hub_is_admin_page()) {
        $classes .= ' staark-hub-admin-page';
    }

    return $classes;
});

add_action('admin_post_staark_install_starter_pages', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_install_starter_pages');

    $created = 0;

    foreach (staark_hub_starter_pages() as $slug => $page) {
        if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
            continue;
        }

        $result = wp_insert_post(
            [
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => $page['content'],
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$created;
        }
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'staark-hub-website',
                'staark_installed' => $created,
            ],
            admin_url('admin.php')
        )
    );
    exit;
});

add_action('admin_post_staark_create_starter_page', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    $slug = isset($_POST['starter_slug']) ? sanitize_key(wp_unslash($_POST['starter_slug'])) : '';
    $pages = staark_hub_starter_pages();

    if ($slug === '' || ! isset($pages[$slug])) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=invalid'));
        exit;
    }

    check_admin_referer('staark_create_starter_page_' . $slug);

    if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=exists'));
        exit;
    }

    $page = $pages[$slug];
    $result = wp_insert_post(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => $slug,
            'post_content' => $page['content'],
        ],
        true
    );

    $status = is_wp_error($result) ? 'error' : 'created';
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=' . $status));
    exit;
});


add_action('admin_post_staark_save_branding', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_save_branding');

    $defaults = staark_hub_branding_defaults();
    $brand_name = isset($_POST['brand_name']) ? sanitize_text_field(wp_unslash($_POST['brand_name'])) : '';
    $tagline = isset($_POST['tagline']) ? sanitize_text_field(wp_unslash($_POST['tagline'])) : '';
    $primary_color = isset($_POST['primary_color']) ? sanitize_hex_color(wp_unslash($_POST['primary_color'])) : '';
    $ink_color = isset($_POST['ink_color']) ? sanitize_hex_color(wp_unslash($_POST['ink_color'])) : '';
    $logo_id = isset($_POST['logo_id']) ? absint($_POST['logo_id']) : 0;
    $site_icon_id = isset($_POST['site_icon_id']) ? absint($_POST['site_icon_id']) : 0;

    if ($brand_name === '') {
        $brand_name = $defaults['brand_name'];
    }

    if ($tagline === '') {
        $tagline = $defaults['tagline'];
    }

    if (! $primary_color) {
        $primary_color = $defaults['primary_color'];
    }

    if (! $ink_color) {
        $ink_color = $defaults['ink_color'];
    }

    if ($logo_id > 0 && ! wp_attachment_is_image($logo_id)) {
        $logo_id = 0;
    }

    if ($site_icon_id > 0 && ! wp_attachment_is_image($site_icon_id)) {
        $site_icon_id = 0;
    }

    $branding = [
        'brand_name' => $brand_name,
        'tagline' => $tagline,
        'primary_color' => $primary_color,
        'ink_color' => $ink_color,
        'logo_id' => $logo_id,
        'site_icon_id' => $site_icon_id,
    ];

    update_option('staark_hub_branding', $branding, false);
    update_option('blogname', $brand_name);
    update_option('blogdescription', $tagline);

    if ($logo_id > 0) {
        set_theme_mod('custom_logo', $logo_id);
    } else {
        remove_theme_mod('custom_logo');
    }

    if ($site_icon_id > 0) {
        update_option('site_icon', $site_icon_id);
    } else {
        delete_option('site_icon');
    }

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-branding&staark_branding=saved'));
    exit;
});

function staark_hub_brand_markup(): void
{
    ?>
    <div class="staark-hub-brand" aria-label="Staark Inc.">
        <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m18 16 4-4-4-4"></path>
            <path d="m6 8-4 4 4 4"></path>
            <path d="m14.5 4-5 16"></path>
        </svg>
        <strong>Staark Inc</strong><span aria-hidden="true"></span>
    </div>
    <?php
}

function staark_hub_nav(): void
{
    $current = staark_hub_current_page();
    $items = [
        STAARK_HUB_SLUG => 'Overview',
        'staark-hub-website' => 'Website',
        'staark-hub-leads' => 'Leads',
        'staark-hub-support' => 'Support',
        'staark-hub-branding' => 'Branding',
        'staark-hub-connect' => 'Connect',
    ];
    ?>
    <nav class="staark-hub-nav" aria-label="Staark Hub sections">
        <?php foreach ($items as $slug => $label) : ?>
            <a class="staark-hub-nav-link<?php echo $current === $slug ? ' is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"<?php echo $current === $slug ? ' aria-current="page"' : ''; ?>>
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function staark_hub_header(string $section): void
{
    ?>
    <header class="staark-hub-header">
        <?php staark_hub_brand_markup(); ?>
        <div class="staark-hub-title-row">
            <div>
                <p class="staark-hub-kicker"><?php echo esc_html($section); ?></p>
                <h1>Staark Website Hub</h1>
                <p class="staark-hub-subtitle">Managed by Staark Inc.</p>
            </div>
            <div class="staark-hub-header-meta">
                <span class="staark-hub-status"><i></i> Website online</span>
                <div class="staark-hub-header-actions">
                    <a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">Open website ↗</a>
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('site-editor.php')); ?>">Site Editor</a>
                </div>
            </div>
        </div>
        <?php staark_hub_nav(); ?>
    </header>
    <?php
}

function staark_hub_render_overview(): void
{
    $theme = wp_get_theme();
    $starter_pages = staark_hub_starter_pages();
    $starter_installed = staark_hub_installed_page_count();
    $published_pages = staark_hub_published_page_count();
    $updates = staark_hub_pending_updates();
    $using_https = function_exists('wp_is_using_https') ? wp_is_using_https() : str_starts_with(home_url('/'), 'https://');
    $pretty_permalinks = (string) get_option('permalink_structure') !== '';
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Overview'); ?>

        <div class="staark-hub-grid staark-hub-grid--four">
            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Website</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Active</span></div>
                <strong class="staark-hub-metric"><?php echo esc_html($theme->get('Name') ?: 'WordPress'); ?></strong>
                <p>Theme <?php echo esc_html($theme->get('Version') ?: '—'); ?> · WP <?php echo esc_html(get_bloginfo('version')); ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Pages</span><span class="staark-hub-mini-status <?php echo $starter_installed === count($starter_pages) ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--warn'; ?>"><?php echo $starter_installed === count($starter_pages) ? 'Starter ready' : 'Needs setup'; ?></span></div>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $published_pages); ?></strong>
                <p><?php echo esc_html($starter_installed . '/' . count($starter_pages)); ?> Staark starter pages installed.</p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Updates</span><span class="staark-hub-mini-status <?php echo $updates['total'] === 0 ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--warn'; ?>"><?php echo $updates['total'] === 0 ? 'Up to date' : 'Review'; ?></span></div>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $updates['total']); ?> pending</strong>
                <p><?php echo esc_html(sprintf('Core %d · Plugins %d · Themes %d', $updates['core'], $updates['plugins'], $updates['themes'])); ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Staark connection</span><span class="staark-hub-mini-status staark-hub-mini-status--muted">Not connected</span></div>
                <strong class="staark-hub-metric">Local</strong>
                <p>API connection is prepared for the next integration layer.</p>
            </section>
        </div>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label">Quick actions</span>
                    <h2>Get to the useful stuff fast</h2>
                    <p>Common client tasks without hunting through the WordPress sidebar.</p>
                </div>
                <a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">View website ↗</a>
            </div>

            <div class="staark-hub-quick-grid">
                <a class="staark-hub-quick-action" href="<?php echo esc_url(staark_hub_homepage_edit_url()); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">⌂</span>
                    <span><strong>Edit homepage</strong><small>Open the homepage content or template</small></span>
                </a>
                <a class="staark-hub-quick-action" href="<?php echo esc_url(admin_url('site-editor.php')); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">✦</span>
                    <span><strong>Site Editor</strong><small>Header, footer, templates and styles</small></span>
                </a>
                <a class="staark-hub-quick-action" href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">＋</span>
                    <span><strong>New page</strong><small>Create another client page</small></span>
                </a>
                <a class="staark-hub-quick-action" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">▤</span>
                    <span><strong>All pages</strong><small>Edit, publish or review page content</small></span>
                </a>
                <a class="staark-hub-quick-action" href="<?php echo esc_url(admin_url('upload.php')); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">▧</span>
                    <span><strong>Media</strong><small>Images, files and uploads</small></span>
                </a>
                <a class="staark-hub-quick-action" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-website')); ?>">
                    <span class="staark-hub-quick-icon" aria-hidden="true">S</span>
                    <span><strong>Website setup</strong><small>Starter pages and Staark website tools</small></span>
                </a>
            </div>
        </section>

        <div class="staark-hub-grid staark-hub-grid--split">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">Website status</span>
                        <h2>Core setup</h2>
                    </div>
                    <a class="button" href="<?php echo esc_url(admin_url('site-health.php')); ?>">Site Health</a>
                </div>

                <div class="staark-hub-health-list">
                    <div class="staark-hub-health-row">
                        <span><i class="staark-hub-health-dot <?php echo $using_https ? 'is-ok' : 'is-warn'; ?>"></i><strong>HTTPS</strong></span>
                        <span class="staark-hub-health-value"><?php echo $using_https ? 'Active' : 'Needs attention'; ?></span>
                    </div>
                    <div class="staark-hub-health-row">
                        <span><i class="staark-hub-health-dot <?php echo $pretty_permalinks ? 'is-ok' : 'is-warn'; ?>"></i><strong>Permalinks</strong></span>
                        <span class="staark-hub-health-value"><?php echo $pretty_permalinks ? 'Configured' : 'Plain URLs'; ?></span>
                    </div>
                    <div class="staark-hub-health-row">
                        <span><i class="staark-hub-health-dot <?php echo $starter_installed === count($starter_pages) ? 'is-ok' : 'is-warn'; ?>"></i><strong>Starter pages</strong></span>
                        <span class="staark-hub-health-value"><?php echo esc_html($starter_installed . '/' . count($starter_pages)); ?> installed</span>
                    </div>
                    <div class="staark-hub-health-row">
                        <span><i class="staark-hub-health-dot <?php echo $updates['total'] === 0 ? 'is-ok' : 'is-warn'; ?>"></i><strong>Updates</strong></span>
                        <span class="staark-hub-health-value"><?php echo esc_html($updates['total'] === 0 ? 'Up to date' : $updates['total'] . ' pending'); ?></span>
                    </div>
                </div>
            </section>

            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Maintenance</span>
                <h2>Keep WordPress tidy</h2>
                <p>Review updates and health checks here now. Security, SEO and performance modules will get their own Staark controls in the dedicated 05.x patches.</p>
                <div class="staark-hub-maintenance-stats">
                    <span><strong><?php echo esc_html((string) $updates['plugins']); ?></strong><small>Plugin updates</small></span>
                    <span><strong><?php echo esc_html((string) $updates['themes']); ?></strong><small>Theme updates</small></span>
                    <span><strong><?php echo esc_html((string) $updates['core']); ?></strong><small>Core updates</small></span>
                </div>
                <div class="staark-hub-actions">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('update-core.php')); ?>">Review updates</a>
                    <a class="button" href="<?php echo esc_url(admin_url('site-health.php')); ?>">Open Site Health</a>
                </div>
            </section>
        </div>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label">Staark workspace</span>
                    <h2>Website modules</h2>
                    <p>The client-facing WordPress stays simple while Staark Hub grows around the workflow.</p>
                </div>
            </div>

            <div class="staark-hub-tool-grid">
                <a class="staark-hub-tool-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-website')); ?>">
                    <span class="staark-hub-tool-icon" aria-hidden="true">01</span>
                    <span><strong>Website</strong><small>Starter pages and website setup</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a class="staark-hub-tool-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-leads')); ?>">
                    <span class="staark-hub-tool-icon" aria-hidden="true">02</span>
                    <span><strong>Leads</strong><small>Prepared for Staark Hub sync</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a class="staark-hub-tool-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-branding')); ?>">
                    <span class="staark-hub-tool-icon" aria-hidden="true">03</span>
                    <span><strong>Branding</strong><small>Client-facing website settings</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a class="staark-hub-tool-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-support')); ?>">
                    <span class="staark-hub-tool-icon" aria-hidden="true">04</span>
                    <span><strong>Support</strong><small>Environment details and Staark support</small></span>
                    <b aria-hidden="true">→</b>
                </a>
            </div>
        </section>

        <div class="staark-hub-grid staark-hub-grid--split">
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Current setup</span>
                <h2>Business Starter</h2>
                <p>The reusable theme, pattern library and starter pages are installed locally. Client content stays editable in WordPress while Staark owns the underlying system.</p>
                <div class="staark-hub-actions">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-website')); ?>">Website setup</a>
                    <a class="button" href="<?php echo esc_url(staark_hub_homepage_edit_url()); ?>">Edit homepage</a>
                </div>
            </section>

            <section class="staark-hub-card staark-hub-card--dark">
                <span class="staark-hub-card-label">Next layer</span>
                <h2>Connect to Staark</h2>
                <p>Leads, support requests and site status will later connect directly to the main Staark Hub.</p>
                <div class="staark-hub-actions">
                    <a class="button staark-hub-dark-button" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-connect')); ?>">Connection settings</a>
                </div>
                <span class="staark-hub-pill">Staark API · planned</span>
            </section>
        </div>
    </div>
    <?php
}

function staark_hub_render_website(): void
{
    $pages = staark_hub_starter_pages();
    $installed = staark_hub_installed_page_count();
    $published_pages = staark_hub_published_page_count();
    $created = isset($_GET['staark_installed']) ? absint($_GET['staark_installed']) : null;
    $single_status = isset($_GET['staark_page']) ? sanitize_key(wp_unslash($_GET['staark_page'])) : '';
    $theme = wp_get_theme();
    $is_block_theme = function_exists('wp_is_block_theme') ? wp_is_block_theme() : false;
    $front_page_id = (int) get_option('page_on_front');
    $front_mode = get_option('show_on_front') === 'page' && $front_page_id > 0 ? 'Static page' : 'Theme front page';
    $permalink = (string) get_option('permalink_structure');
    $timezone = (string) get_option('timezone_string');
    if ($timezone === '') {
        $timezone = 'UTC' . get_option('gmt_offset');
    }
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Website'); ?>

        <?php if ($created !== null) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf('%d starter page(s) created. Existing pages were left untouched.', $created)); ?></p></div>
        <?php endif; ?>
        <?php if ($single_status === 'created') : ?>
            <div class="notice notice-success is-dismissible"><p>Starter page created successfully.</p></div>
        <?php elseif ($single_status === 'exists') : ?>
            <div class="notice notice-info is-dismissible"><p>That starter page already exists, so nothing was overwritten.</p></div>
        <?php elseif ($single_status === 'error' || $single_status === 'invalid') : ?>
            <div class="notice notice-error is-dismissible"><p>The starter page could not be created. Review the request and try again.</p></div>
        <?php endif; ?>

        <div class="staark-hub-grid staark-hub-grid--four staark-hub-website-summary">
            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Homepage</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Ready</span></div>
                <strong class="staark-hub-metric">Front page</strong>
                <p><?php echo esc_html($front_mode); ?> · <a href="<?php echo esc_url(staark_hub_homepage_edit_url()); ?>">Edit homepage</a></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Pages</span><span class="staark-hub-mini-status <?php echo $installed === count($pages) ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--warn'; ?>"><?php echo $installed === count($pages) ? 'Starter ready' : 'Needs setup'; ?></span></div>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $published_pages); ?> published</strong>
                <p><?php echo esc_html($installed . '/' . count($pages)); ?> Staark starter pages installed.</p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Theme</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Active</span></div>
                <strong class="staark-hub-metric"><?php echo esc_html($theme->get('Name') ?: 'WordPress'); ?></strong>
                <p>Version <?php echo esc_html($theme->get('Version') ?: '—'); ?> · <?php echo $is_block_theme ? 'Block theme' : 'Classic theme'; ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Site</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Online</span></div>
                <strong class="staark-hub-metric"><?php echo esc_html(get_bloginfo('language')); ?></strong>
                <p><?php echo esc_html($timezone); ?> · <?php echo $permalink !== '' ? 'Pretty URLs' : 'Plain URLs'; ?></p>
            </section>
        </div>

        <section class="staark-hub-card staark-hub-website-pages">
            <div class="staark-hub-card-heading">
                <div>
                    <span class="staark-hub-card-label">Business Starter</span>
                    <h2>Website pages</h2>
                    <p>See what is installed, jump straight into editing, or create only the page that is missing. Existing content is never overwritten.</p>
                </div>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="staark_install_starter_pages">
                    <?php wp_nonce_field('staark_install_starter_pages'); ?>
                    <?php submit_button($installed === count($pages) ? 'Check / repair pages' : 'Install missing pages', 'primary', 'submit', false); ?>
                </form>
            </div>

            <div class="staark-hub-page-table" role="table" aria-label="Staark starter pages">
                <div class="staark-hub-page-table-head" role="row">
                    <span role="columnheader">Page</span>
                    <span role="columnheader">Status</span>
                    <span role="columnheader">Updated</span>
                    <span role="columnheader">Actions</span>
                </div>

                <?php foreach ($pages as $slug => $page) : ?>
                    <?php $existing = get_page_by_path($slug, OBJECT, 'page'); ?>
                    <div class="staark-hub-page-table-row" role="row">
                        <div class="staark-hub-page-name" role="cell">
                            <strong><?php echo esc_html($page['title']); ?></strong>
                            <code>/<?php echo esc_html($slug); ?></code>
                        </div>

                        <div role="cell">
                            <?php if ($existing instanceof WP_Post) : ?>
                                <span class="staark-hub-badge staark-hub-badge--ok"><?php echo esc_html(ucfirst($existing->post_status)); ?></span>
                            <?php else : ?>
                                <span class="staark-hub-badge staark-hub-badge--warn">Missing</span>
                            <?php endif; ?>
                        </div>

                        <div class="staark-hub-page-updated" role="cell">
                            <?php echo $existing instanceof WP_Post ? esc_html(get_the_modified_date('Y-m-d H:i', $existing)) : '—'; ?>
                        </div>

                        <div class="staark-hub-page-actions" role="cell">
                            <?php if ($existing instanceof WP_Post) : ?>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($existing->ID, 'raw')); ?>">Edit</a>
                                <a class="button button-small" href="<?php echo esc_url(get_permalink($existing)); ?>" target="_blank" rel="noopener">View ↗</a>
                            <?php else : ?>
                                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                    <input type="hidden" name="action" value="staark_create_starter_page">
                                    <input type="hidden" name="starter_slug" value="<?php echo esc_attr($slug); ?>">
                                    <?php wp_nonce_field('staark_create_starter_page_' . $slug); ?>
                                    <button type="submit" class="button button-small button-primary">Create page</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="staark-hub-grid staark-hub-grid--split">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">Site settings</span>
                        <h2>Identity & routing</h2>
                    </div>
                    <a class="button" href="<?php echo esc_url(admin_url('options-general.php')); ?>">WordPress settings</a>
                </div>
                <dl class="staark-hub-details staark-hub-details--website">
                    <div><dt>Site title</dt><dd><?php echo esc_html(get_bloginfo('name') ?: '—'); ?></dd></div>
                    <div><dt>Tagline</dt><dd><?php echo esc_html(get_bloginfo('description') ?: 'Not set'); ?></dd></div>
                    <div><dt>Homepage</dt><dd><?php echo esc_html($front_mode); ?></dd></div>
                    <div><dt>Permalinks</dt><dd><?php echo esc_html($permalink !== '' ? $permalink : 'Plain'); ?></dd></div>
                    <div><dt>Language</dt><dd><?php echo esc_html(get_bloginfo('language')); ?></dd></div>
                    <div><dt>Timezone</dt><dd><?php echo esc_html($timezone); ?></dd></div>
                </dl>
            </section>

            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">Theme & structure</span>
                        <h2>Staark editor</h2>
                    </div>
                    <span class="staark-hub-mini-status staark-hub-mini-status--ok"><?php echo $is_block_theme ? 'Block editor' : 'Classic'; ?></span>
                </div>
                <dl class="staark-hub-details staark-hub-details--website">
                    <div><dt>Theme</dt><dd><?php echo esc_html($theme->get('Name') ?: '—'); ?></dd></div>
                    <div><dt>Version</dt><dd><?php echo esc_html($theme->get('Version') ?: '—'); ?></dd></div>
                    <div><dt>Templates</dt><dd><?php echo $is_block_theme ? 'Site Editor' : 'Theme files'; ?></dd></div>
                    <div><dt>Patterns</dt><dd>Staark Pattern Library</dd></div>
                </dl>
                <div class="staark-hub-actions staark-hub-website-tools">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('site-editor.php')); ?>">Open Site Editor</a>
                    <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">All pages</a>
                    <a class="button" href="<?php echo esc_url(admin_url('themes.php')); ?>">Themes</a>
                </div>
            </section>
        </div>

        <p class="staark-hub-note"><strong>Privacy page:</strong> the included copy is a starter template. Review company details, forms, analytics, cookies and third-party services before a client site goes live.</p>
    </div>
    <?php
}

function staark_hub_render_placeholder(string $section, string $title, string $copy, array $items = []): void
{
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header($section); ?>
        <section class="staark-hub-card staark-hub-empty-state">
            <div class="staark-hub-empty-visual" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v18"></path><path d="M3 12h18"></path>
                </svg>
            </div>
            <div class="staark-hub-empty-copy">
                <span class="staark-hub-card-label">Coming next</span>
                <h2><?php echo esc_html($title); ?></h2>
                <p><?php echo esc_html($copy); ?></p>
                <?php if ($items !== []) : ?>
                    <div class="staark-hub-feature-list">
                        <?php foreach ($items as $item) : ?>
                            <span><i aria-hidden="true">✓</i><?php echo esc_html($item); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <span class="staark-hub-pill">Planned</span>
            </div>
        </section>
    </div>
    <?php
}

function staark_hub_render_leads(): void
{
    staark_hub_render_placeholder(
        'Leads',
        'Website leads in one place',
        'Form submissions will flow from WordPress into the main Staark Hub with useful context attached automatically.',
        ['Name, email and phone', 'Source page and campaign', 'Staark Hub lead sync']
    );
}

function staark_hub_render_branding(): void
{
    $branding = staark_hub_branding();
    $logo_url = $branding['logo_id'] > 0 ? wp_get_attachment_image_url($branding['logo_id'], 'medium') : false;
    $icon_url = $branding['site_icon_id'] > 0 ? wp_get_attachment_image_url($branding['site_icon_id'], 'thumbnail') : false;
    $saved = isset($_GET['staark_branding']) && sanitize_key(wp_unslash($_GET['staark_branding'])) === 'saved';
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Branding'); ?>

        <?php if ($saved) : ?>
            <div class="notice notice-success is-dismissible"><p>Branding saved. The frontend identity and theme colors are now updated.</p></div>
        <?php endif; ?>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-brand-form">
            <input type="hidden" name="action" value="staark_save_branding">
            <?php wp_nonce_field('staark_save_branding'); ?>

            <div class="staark-hub-grid staark-hub-grid--split staark-hub-brand-layout">
                <div class="staark-hub-brand-editor">
                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Brand identity</span>
                                <h2>Client-facing identity</h2>
                                <p>Change the website brand without touching theme files. Staark Hub stays Staark-branded in wp-admin.</p>
                            </div>
                            <span class="staark-hub-mini-status staark-hub-mini-status--ok">Live</span>
                        </div>

                        <div class="staark-hub-form-grid">
                            <label class="staark-hub-field">
                                <span>Brand name</span>
                                <input type="text" name="brand_name" value="<?php echo esc_attr($branding['brand_name']); ?>" maxlength="80" data-staark-brand-name>
                                <small>Used in the frontend brand lockup, WordPress site title and footer copyright.</small>
                            </label>

                            <label class="staark-hub-field">
                                <span>Tagline</span>
                                <input type="text" name="tagline" value="<?php echo esc_attr($branding['tagline']); ?>" maxlength="160" data-staark-brand-tagline>
                                <small>Short positioning line shown in the footer and saved as the WordPress tagline.</small>
                            </label>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Visual system</span>
                                <h2>Colors</h2>
                                <p>The primary color drives buttons, links and accents. Ink controls the main dark text color.</p>
                            </div>
                        </div>

                        <div class="staark-hub-color-grid">
                            <label class="staark-hub-color-field">
                                <span>Primary</span>
                                <span class="staark-hub-color-control">
                                    <input type="color" name="primary_color" value="<?php echo esc_attr($branding['primary_color']); ?>" data-staark-brand-primary>
                                    <code data-staark-color-value="primary"><?php echo esc_html($branding['primary_color']); ?></code>
                                </span>
                            </label>

                            <label class="staark-hub-color-field">
                                <span>Ink</span>
                                <span class="staark-hub-color-control">
                                    <input type="color" name="ink_color" value="<?php echo esc_attr($branding['ink_color']); ?>" data-staark-brand-ink>
                                    <code data-staark-color-value="ink"><?php echo esc_html($branding['ink_color']); ?></code>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Assets</span>
                                <h2>Logo & site icon</h2>
                                <p>If no logo is selected, the Staark-style Code2 mark is used as a clean fallback on the frontend.</p>
                            </div>
                        </div>

                        <div class="staark-hub-asset-grid">
                            <div class="staark-hub-asset-field">
                                <div class="staark-hub-asset-label"><strong>Logo mark</strong><span>Header & footer</span></div>
                                <div class="staark-hub-asset-preview" id="staark-logo-preview">
                                    <?php if ($logo_url) : ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                    <?php else : ?>
                                        <div class="staark-hub-asset-fallback" aria-hidden="true">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="staark_brand_logo_id" name="logo_id" value="<?php echo esc_attr((string) $branding['logo_id']); ?>">
                                <div class="staark-hub-asset-actions">
                                    <button type="button" class="button" data-staark-media-button data-target="#staark_brand_logo_id" data-preview="#staark-logo-preview" data-title="Choose brand logo">Choose logo</button>
                                    <button type="button" class="button-link-delete" data-staark-media-remove data-target="#staark_brand_logo_id" data-preview="#staark-logo-preview">Remove</button>
                                </div>
                            </div>

                            <div class="staark-hub-asset-field">
                                <div class="staark-hub-asset-label"><strong>Site icon</strong><span>Browser tab & app icon</span></div>
                                <div class="staark-hub-asset-preview staark-hub-asset-preview--icon" id="staark-icon-preview">
                                    <?php if ($icon_url) : ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="">
                                    <?php else : ?>
                                        <div class="staark-hub-asset-placeholder">512 × 512</div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="staark_brand_site_icon_id" name="site_icon_id" value="<?php echo esc_attr((string) $branding['site_icon_id']); ?>">
                                <div class="staark-hub-asset-actions">
                                    <button type="button" class="button" data-staark-media-button data-target="#staark_brand_site_icon_id" data-preview="#staark-icon-preview" data-title="Choose site icon">Choose icon</button>
                                    <button type="button" class="button-link-delete" data-staark-media-remove data-target="#staark_brand_site_icon_id" data-preview="#staark-icon-preview">Remove</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="staark-hub-brand-savebar">
                        <div>
                            <strong>Ready to apply?</strong>
                            <span>These settings affect the public website, not the Staark Hub admin branding.</span>
                        </div>
                        <?php submit_button('Save branding', 'primary', 'submit', false); ?>
                    </div>
                </div>

                <aside class="staark-hub-brand-sidebar">
                    <section class="staark-hub-card staark-hub-brand-preview-card">
                        <span class="staark-hub-card-label">Live preview</span>
                        <h2>Brand snapshot</h2>
                        <div class="staark-hub-brand-preview" data-staark-brand-preview style="--brand-primary:<?php echo esc_attr($branding['primary_color']); ?>;--brand-ink:<?php echo esc_attr($branding['ink_color']); ?>;">
                            <div class="staark-hub-brand-preview-browser"><i></i><i></i><i></i><span><?php echo esc_html(wp_parse_url(home_url('/'), PHP_URL_HOST) ?: 'website'); ?></span></div>
                            <div class="staark-hub-brand-preview-nav">
                                <div class="staark-hub-brand-preview-lockup">
                                    <span class="staark-hub-brand-preview-mark" data-staark-preview-mark>
                                        <?php if ($logo_url) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                        <?php else : ?>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>
                                        <?php endif; ?>
                                    </span>
                                    <strong data-staark-preview-name><?php echo esc_html($branding['brand_name']); ?></strong><i></i>
                                </div>
                                <span class="staark-hub-brand-preview-button">Kontakt</span>
                            </div>
                            <div class="staark-hub-brand-preview-body">
                                <span class="staark-hub-brand-preview-kicker">MODERN WEBB</span>
                                <h3 data-staark-preview-name><?php echo esc_html($branding['brand_name']); ?></h3>
                                <p data-staark-preview-tagline><?php echo esc_html($branding['tagline']); ?></p>
                                <span class="staark-hub-brand-preview-cta">Få offert</span>
                            </div>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Applied automatically</span>
                        <h2>One save, multiple surfaces</h2>
                        <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                            <span><i aria-hidden="true">✓</i>Header and footer brand lockup</span>
                            <span><i aria-hidden="true">✓</i>WordPress site title and tagline</span>
                            <span><i aria-hidden="true">✓</i>Theme primary and ink colors</span>
                            <span><i aria-hidden="true">✓</i>Browser site icon</span>
                        </div>
                        <p class="staark-hub-brand-note">Staark Hub itself keeps the Staark Inc. identity so clients always know who manages the website.</p>
                    </section>
                </aside>
            </div>
        </form>
    </div>
    <?php
}

function staark_hub_render_connect(): void
{
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Connect'); ?>

        <div class="staark-hub-grid staark-hub-grid--split">
            <section class="staark-hub-card">
                <div class="staark-hub-connection-heading">
                    <div>
                        <span class="staark-hub-card-label">Connection status</span>
                        <h2>Connect this website to Staark</h2>
                    </div>
                    <span class="staark-hub-mini-status staark-hub-mini-status--muted">Not connected</span>
                </div>
                <p>The connection layer will authenticate this WordPress installation with the main Staark Hub. No API credentials are required in this development build yet.</p>
                <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                    <span><i aria-hidden="true">✓</i>Lead and form submission sync</span>
                    <span><i aria-hidden="true">✓</i>Support requests and website context</span>
                    <span><i aria-hidden="true">✓</i>Health status and managed updates</span>
                </div>
                <div class="staark-hub-actions">
                    <button type="button" class="button button-primary" disabled>Connect to Staark</button>
                    <span class="staark-hub-action-note">Available when the API layer is enabled.</span>
                </div>
            </section>

            <section class="staark-hub-card staark-hub-card--dark">
                <span class="staark-hub-card-label">Architecture</span>
                <h2>One website, one connection</h2>
                <p>WordPress stays simple for content editing while Staark Hub handles the wider client workflow around leads, support and maintenance.</p>
                <span class="staark-hub-pill">Staark API · planned</span>
            </section>
        </div>
    </div>
    <?php
}

function staark_hub_render_support(): void
{
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Support'); ?>

        <div class="staark-hub-grid staark-hub-grid--two">
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Need help?</span>
                <h2>Staark support</h2>
                <p>For changes, maintenance or technical issues, contact Staark Inc. and include the website address plus a short description of what you need.</p>
                <div class="staark-hub-actions">
                    <a class="button button-primary" href="mailto:hello@staarkinc.com">Email Staark</a>
                    <a class="button" href="https://staarkinc.com/kontakt" target="_blank" rel="noopener">Contact page ↗</a>
                </div>
            </section>

            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Environment</span>
                <dl class="staark-hub-details">
                    <div><dt>Site URL</dt><dd><?php echo esc_html(home_url('/')); ?></dd></div>
                    <div><dt>WordPress</dt><dd><?php echo esc_html(get_bloginfo('version')); ?></dd></div>
                    <div><dt>PHP</dt><dd><?php echo esc_html(PHP_VERSION); ?></dd></div>
                    <div><dt>Staark Hub</dt><dd><?php echo esc_html(STAARK_HUB_VERSION); ?></dd></div>
                </dl>
            </section>
        </div>
    </div>
    <?php
}
