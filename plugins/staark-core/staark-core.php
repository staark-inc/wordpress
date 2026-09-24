<?php
/**
 * Plugin Name: Staark Hub
 * Plugin URI: https://staarkinc.com
 * Description: Website management layer for sites built and maintained by Staark Inc.
 * Version: 0.5.0
 * Author: Staark Inc.
 * Author URI: https://staarkinc.com
 * Text Domain: staark-core
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_VERSION = '0.5.0';
const STAARK_HUB_SLUG = 'staark-hub';

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
            <span class="staark-hub-status"><i></i> Website online</span>
        </div>
    </header>
    <?php
}

function staark_hub_render_overview(): void
{
    $theme = wp_get_theme();
    $pages = staark_hub_starter_pages();
    $installed = staark_hub_installed_page_count();
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Overview'); ?>

        <div class="staark-hub-grid staark-hub-grid--four">
            <section class="staark-hub-card staark-hub-stat-card">
                <span class="staark-hub-card-label">Website</span>
                <strong class="staark-hub-metric"><?php echo esc_html($theme->get('Name') ?: 'WordPress'); ?></strong>
                <p>Theme <?php echo esc_html($theme->get('Version') ?: '—'); ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <span class="staark-hub-card-label">Starter pages</span>
                <strong class="staark-hub-metric"><?php echo esc_html($installed . '/' . count($pages)); ?></strong>
                <p><?php echo esc_html($installed === count($pages) ? 'Business Starter is ready.' : 'Pages still need attention.'); ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <span class="staark-hub-card-label">Environment</span>
                <strong class="staark-hub-metric">WP <?php echo esc_html(get_bloginfo('version')); ?></strong>
                <p>PHP <?php echo esc_html(PHP_VERSION); ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <span class="staark-hub-card-label">Staark connection</span>
                <strong class="staark-hub-metric">Local</strong>
                <p>API connection is the next integration layer.</p>
            </section>
        </div>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label">Workspace</span>
                    <h2>Manage the website</h2>
                    <p>Common website tasks and Staark tools in one place.</p>
                </div>
                <a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">View website ↗</a>
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
                <p>The reusable theme, pattern library and starter pages are installed locally. Keep client content editable in WordPress while Staark owns the underlying system.</p>
                <div class="staark-hub-actions">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-website')); ?>">Website setup</a>
                    <a class="button" href="<?php echo esc_url(admin_url('site-editor.php')); ?>">Open Site Editor</a>
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
    $created = isset($_GET['staark_installed']) ? absint($_GET['staark_installed']) : null;
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Website'); ?>

        <?php if ($created !== null) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf('%d starter page(s) created. Existing pages were left untouched.', $created)); ?></p></div>
        <?php endif; ?>

        <section class="staark-hub-card">
            <div class="staark-hub-card-heading">
                <div>
                    <span class="staark-hub-card-label">Business Starter</span>
                    <h2>Starter pages</h2>
                    <p><?php echo esc_html($installed . ' of ' . count($pages)); ?> pages are installed. Running the installer again never overwrites existing page content.</p>
                </div>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="staark_install_starter_pages">
                    <?php wp_nonce_field('staark_install_starter_pages'); ?>
                    <?php submit_button($installed === count($pages) ? 'Check / repair pages' : 'Install starter pages', 'primary', 'submit', false); ?>
                </form>
            </div>

            <div class="staark-hub-page-list">
                <?php foreach ($pages as $slug => $page) : ?>
                    <?php $existing = get_page_by_path($slug, OBJECT, 'page'); ?>
                    <div class="staark-hub-page-row">
                        <div>
                            <strong><?php echo esc_html($page['title']); ?></strong>
                            <code>/<?php echo esc_html($slug); ?></code>
                        </div>
                        <?php if ($existing instanceof WP_Post) : ?>
                            <div class="staark-hub-page-actions">
                                <span class="staark-hub-badge staark-hub-badge--ok">Ready</span>
                                <a href="<?php echo esc_url(get_edit_post_link($existing->ID)); ?>">Edit</a>
                                <a href="<?php echo esc_url(get_permalink($existing)); ?>" target="_blank" rel="noopener">View ↗</a>
                            </div>
                        <?php else : ?>
                            <span class="staark-hub-badge">Missing</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <p class="staark-hub-note"><strong>Privacy page:</strong> the included copy is a starter template. Review company details, forms, analytics, cookies and third-party services before a client site goes live.</p>
    </div>
    <?php
}

function staark_hub_render_placeholder(string $section, string $title, string $copy): void
{
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header($section); ?>
        <section class="staark-hub-card staark-hub-coming-soon">
            <span class="staark-hub-card-label">Next layer</span>
            <h2><?php echo esc_html($title); ?></h2>
            <p><?php echo esc_html($copy); ?></p>
            <span class="staark-hub-pill">Planned</span>
        </section>
    </div>
    <?php
}

function staark_hub_render_leads(): void
{
    staark_hub_render_placeholder('Leads', 'Website leads in one place', 'Form submissions will later flow from WordPress into the main Staark Hub with source, page and campaign context.');
}

function staark_hub_render_branding(): void
{
    staark_hub_render_placeholder('Branding', 'Client branding controls', 'Logo, colors and client-facing defaults will be managed here without editing theme files by hand.');
}

function staark_hub_render_connect(): void
{
    staark_hub_render_placeholder('Connect', 'Connect to Staark', 'This site will later authenticate with the Staark API for leads, support, health status and managed updates.');
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
